<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Skill;
use App\Models\Event;
use App\Models\Task;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\TaskApplication;
use App\Models\ChatbotResponse;
use App\Models\OrgConversation;
use App\Support\ActivityReport;
use App\Support\CertificatePdf;
use App\Support\OrgNotifier;
use App\Support\ScheduleClock;
use App\Support\AuditTrail;
use App\Support\PortalNotifier;
use App\Support\ReportFiles;
use App\Support\VolunteerAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VolunteerController extends Controller
{
    /**
     * Display the Volunteer Dashboard.
     */
    public function dashboard()
    {
        $volunteer = Auth::user();
        $volunteer->load('primaryOrganization');
        $mySkills = $volunteer->skills;

        // Get all available skills for registration dropdown
        $mySkillIds = $mySkills->pluck('id')->toArray();
        $availableSkills = Skill::whereNotIn('id', $mySkillIds)->get();

        // Get volunteer's task assignments (tasks they are assigned to)
        $assignments = Assignment::where('user_id', $volunteer->id)
            ->with([
                'event' => fn ($query) => $query->withTrashed()->with('organization'),
                'task' => fn ($query) => $query->withTrashed()->with('skills'),
            ])
            ->latest()
            ->get()
            ->filter(fn (Assignment $assignment) => $assignment->task && $assignment->event)
            ->values();
        $activeAssignments = $assignments->whereIn('status', ['pending', 'approved', 'submitted', 'rejected']);
        $completedAssignments = $assignments->where('status', 'completed');

        // Get issued certificates
        $certificates = Certificate::where('user_id', $volunteer->id)
            ->with('event')
            ->orderBy('issued_at', 'desc')
            ->get();
        $certificatesByEvent = $certificates->keyBy('event_id');
        $volunteerRewards = \App\Models\VolunteerReward::where('user_id', $volunteer->id)->get();
        $taskRewardsByTask = $volunteerRewards->where('reward_scope', 'task')->keyBy('task_id');
        $eventRewardsByEvent = $volunteerRewards->where('reward_scope', 'event')->keyBy('event_id');

        $assignmentStats = [
            'active' => $assignments->where('status', 'approved')->count(),
            'submitted' => $assignments->where('status', 'submitted')->count(),
            'revision' => $assignments->where('status', 'rejected')->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
            'hours' => $assignments->where('status', 'completed')->sum('hours_logged'),
        ];

        $now = Carbon::now();
        $section = (string) request()->query('section', 'dashboard');
        $sections = ['dashboard', 'events', 'tasks', 'availability', 'notifications', 'certificates', 'skills', 'chatbot', 'messages', 'settings'];
        if (!in_array($section, $sections, true)) {
            $section = 'dashboard';
        }

        $assignments->each(function (Assignment $assignment) {
            $event = $assignment->event;
            $assignment->schedule_phase = $event
                ? ScheduleClock::phase($event->start_time, $event->end_time, $event->status)
                : 'past';
        });

        $openDuties = $assignments->whereIn('status', ['pending', 'approved', 'submitted', 'rejected'])->values();

        $presentDuties = $openDuties
            ->filter(fn (Assignment $assignment) => $assignment->schedule_phase !== 'past')
            ->sortBy(fn (Assignment $assignment) => $assignment->task->due_date ?? $assignment->event->start_time)
            ->values();
        $pastDuties = $openDuties
            ->filter(fn (Assignment $assignment) => $assignment->schedule_phase === 'past')
            ->sortByDesc(fn (Assignment $assignment) => $assignment->event->end_time ?? $assignment->event->start_time)
            ->values();

        $weekNow = ScheduleClock::now();
        $weekStart = $weekNow->copy()->startOfWeek();
        $weekEnd = $weekNow->copy()->endOfWeek();
        $dueThisWeek = $presentDuties->filter(function (Assignment $assignment) use ($weekStart, $weekEnd) {
            $due = ScheduleClock::at($assignment->task->due_date ?? $assignment->event->start_time);

            return $due && $due->between($weekStart, $weekEnd);
        })->count();

        $upcomingEvents = $assignments
            ->filter(function (Assignment $assignment) {
                $event = $assignment->event;

                return in_array($assignment->status, ['approved', 'submitted'], true)
                    && $event
                    && !in_array($event->status, ['cancelled', 'draft'], true)
                    && ScheduleClock::phase($event->start_time, $event->end_time, $event->status) !== 'past';
            })
            ->map(fn (Assignment $assignment) => $assignment->event)
            ->unique('id')
            ->sortBy(fn (Event $event) => (ScheduleClock::phase($event->start_time, $event->end_time, $event->status) === 'now' ? '0' : '1').$event->start_time?->format('Y-m-d H:i:s'))
            ->values();

        $myEvents = $assignments
            ->groupBy('event_id')
            ->map(function ($group) {
                $event = $group->first()->event;
                if (!$event) {
                    return null;
                }

                $phase = ScheduleClock::phase($event->start_time, $event->end_time, $event->status);
                $event->schedule_phase = $phase;
                $event->duty_titles = $group
                    ->map(fn (Assignment $assignment) => $assignment->task->title ?? null)
                    ->filter()
                    ->unique()
                    ->values();
                $event->participation = $group
                    ->map(fn (Assignment $assignment) => \App\Support\VolunteerDuty::label(
                        $assignment->status,
                        $assignment->schedule_phase,
                        $assignment->feedback
                    ))
                    ->unique()
                    ->join(', ');
                $event->event_bucket = $event->status === 'cancelled'
                    ? 'cancelled'
                    : ($phase === 'now' ? 'ongoing' : ($phase === 'upcoming' ? 'upcoming' : 'completed'));

                return $event;
            })
            ->filter()
            ->sortBy(fn (Event $event) => ($event->schedule_phase === 'past' ? '1' : '0').$event->start_time?->format('Y-m-d H:i:s'))
            ->values();

        $eventSearch = trim((string) request('q', ''));
        $eventState = (string) request('state', 'all');
        $eventFrom = (string) request('from', '');
        $eventTo = (string) request('to', '');
        $eventList = $myEvents->filter(function (Event $event) use ($eventSearch, $eventState, $eventFrom, $eventTo) {
            if ($eventSearch !== '') {
                $haystack = mb_strtolower(trim($event->title.' '.$event->description.' '.$event->location));
                if (!str_contains($haystack, mb_strtolower($eventSearch))) {
                    return false;
                }
            }
            if (!in_array($eventState, ['', 'all'], true) && $event->event_bucket !== $eventState) {
                return false;
            }
            $day = $event->start_time?->format('Y-m-d');
            if ($eventFrom !== '' && $day && $day < $eventFrom) {
                return false;
            }
            if ($eventTo !== '' && $day && $day > $eventTo) {
                return false;
            }

            return true;
        })->values();

        $nextEvent = $upcomingEvents->first(fn (Event $event) => ScheduleClock::phase($event->start_time, $event->end_time, $event->status) === 'upcoming')
            ?? $upcomingEvents->first();
        $certificatesThisYear = $certificates->filter(
            fn (Certificate $certificate) => $certificate->issued_at && $certificate->issued_at->year === $now->year
        )->count();

        $noticesQuery = $volunteer->notifications()->latest();
        if ($section === 'notifications' && request('filter') === 'unread') {
            $noticesQuery->whereNull('read_at');
        }
        $noticeTotal = (clone $noticesQuery)->count();
        $notices = $noticesQuery->limit($section === 'notifications' ? 20 : 5)->get();
        $unreadNoticeCount = $volunteer->unreadNotifications()->count();

        $organizationIds = $volunteer->organization_id
            ? collect([(int) $volunteer->organization_id])
            : $assignments->map(fn (Assignment $assignment) => (int) $assignment->event->organization_id)->filter()->unique()->values();

        $threads = in_array($section, ['messages', 'chatbot'], true)
            ? ChatbotResponse::where('user_id', $volunteer->id)
                ->when($organizationIds->isNotEmpty(), fn ($query) => $query->whereIn('organization_id', $organizationIds))
                ->latest()
                ->limit(40)
                ->get()
            : collect();

        if ($section === 'messages') {
            $unreadThreadIds = $threads->filter(fn (ChatbotResponse $thread) => $thread->admin_reply && !$thread->read_at)->pluck('id');
            if ($unreadThreadIds->isNotEmpty()) {
                ChatbotResponse::where('user_id', $volunteer->id)->whereIn('id', $unreadThreadIds)->update(['read_at' => now()]);
            }
        }

        $assignedTaskIds = $assignments->pluck('task_id')->filter()->toArray();
        $openTasks = Task::whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->whereNotIn('id', $assignedTaskIds)
            ->whereDoesntHave('assignments', function ($query) {
                $query->whereIn('status', ['pending', 'approved', 'submitted', 'completed']);
            })
            ->with(['event', 'skills', 'applications' => function ($query) use ($volunteer) {
                $query->where('user_id', $volunteer->id);
            }])
            ->whereHas('event', function ($query) use ($organizationIds) {
                $query->where('status', 'published')
                    ->where('end_time', '>=', ScheduleClock::now()->format('Y-m-d H:i:s'));
                if ($organizationIds->isEmpty()) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('organization_id', $organizationIds);
                }
            })
            ->orderBy('due_date')
            ->get()
            ->map(function ($task) use ($mySkillIds) {
                $requiredSkillIds = $task->skills->pluck('id')->toArray();
                $matchingSkillIds = array_intersect($requiredSkillIds, $mySkillIds);
                $task->match_score = count($requiredSkillIds) > 0
                    ? round((count($matchingSkillIds) / count($requiredSkillIds)) * 100)
                    : 100;
                $task->matched_skill_count = count($matchingSkillIds);
                return $task;
            });

        // AI Recommended Skills to Learn:
        // Skills needed in upcoming events/tasks that this volunteer doesn't have yet
        $recommendedSkills = [];
        $upcomingTasks = Task::whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->with(['skills', 'event'])
            ->whereHas('event', function ($query) use ($organizationIds) {
                $query->where('start_time', '>', ScheduleClock::now()->format('Y-m-d H:i:s'))
                    ->where('status', 'published');
                if ($organizationIds->isEmpty()) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('organization_id', $organizationIds);
                }
            })
            ->get();

        $neededSkillWeights = [];
        foreach ($upcomingTasks as $task) {
            if (!$task->event) {
                continue;
            }
            foreach ($task->skills as $skill) {
                if (!in_array($skill->id, $mySkillIds)) {
                    if (!isset($neededSkillWeights[$skill->id])) {
                        $neededSkillWeights[$skill->id] = [
                            'skill' => $skill,
                            'count' => 0,
                            'event_title' => $task->event->title
                        ];
                    }
                    $neededSkillWeights[$skill->id]['count']++;
                }
            }
        }

        uasort($neededSkillWeights, fn($a, $b) => $b['count'] <=> $a['count']);
        $recommendedSkills = array_slice($neededSkillWeights, 0, 3);

        $pendingResponses = $openDuties
            ->filter(fn (Assignment $assignment) => $assignment->status === 'pending' && $assignment->schedule_phase !== 'past')
            ->values();
        $activeTasks = $openDuties
            ->filter(fn (Assignment $assignment) => in_array($assignment->status, ['approved', 'rejected'], true) && $assignment->schedule_phase !== 'past')
            ->values();
        $verifiedHours = (float) $assignments->where('status', 'completed')->sum('hours_logged');
        $recentActivity = $assignments->sortByDesc(fn (Assignment $assignment) => $assignment->updated_at?->timestamp ?? 0)->take(6)->values();
        $recommendedOpportunities = $openTasks->sortByDesc('match_score')->take(4)->values();
        $taskBoard = $assignments
            ->whereIn('status', ['pending', 'approved', 'submitted', 'rejected', 'cancelled'])
            ->sortBy(fn (Assignment $assignment) => ($assignment->schedule_phase === 'past' ? '1' : '0').($assignment->status === 'pending' ? '0' : '1').($assignment->event->start_time?->format('Y-m-d H:i:s')))
            ->values();

        return view('volunteer.dashboard', compact(
            'volunteer',
            'mySkills',
            'availableSkills',
            'assignments',
            'activeAssignments',
            'completedAssignments',
            'certificates',
            'certificatesByEvent',
            'taskRewardsByTask',
            'eventRewardsByEvent',
            'assignmentStats',
            'section',
            'openDuties',
            'presentDuties',
            'pastDuties',
            'dueThisWeek',
            'upcomingEvents',
            'myEvents',
            'eventList',
            'eventSearch',
            'eventState',
            'eventFrom',
            'eventTo',
            'nextEvent',
            'certificatesThisYear',
            'notices',
            'noticeTotal',
            'unreadNoticeCount',
            'threads',
            'openTasks',
            'recommendedSkills',
            'pendingResponses',
            'activeTasks',
            'verifiedHours',
            'recentActivity',
            'recommendedOpportunities',
            'taskBoard'
        ));
    }

    /**
     * Apply for an open organization task.
     */
    public function applyForTask(Request $request, Task $task)
    {
        $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $task->load('event');
        $volunteer = Auth::user();
        if (!$task->event || $task->event->status !== 'published' || !$task->event->end_time || ScheduleClock::phase($task->event->start_time, $task->event->end_time, $task->event->status) === 'past') {
            AuditTrail::record([
                'action' => 'Task application denied',
                'action_type' => 'other',
                'module' => 'Tasks',
                'description' => 'Could not apply for "'.$task->title.'" because the event is not open.',
                'status' => 'failed',
                'subject' => $task,
                'subject_label' => $task->title,
            ]);
            abort(422);
        }

        if ($volunteer->organization_id && (int) $task->event->organization_id !== (int) $volunteer->organization_id) {
            abort(403);
        }

        $alreadyAssigned = Assignment::where('user_id', Auth::id())
            ->where('task_id', $task->id)
            ->exists();

        if ($alreadyAssigned) {
            AuditTrail::record([
                'action' => 'Task application denied',
                'action_type' => 'other',
                'module' => 'Tasks',
                'description' => 'Did not apply again for "'.$task->title.'" because it is already assigned.',
                'status' => 'failed',
                'subject' => $task,
                'subject_label' => $task->title,
            ]);

            return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
                ->with('error', 'This task is already assigned to you.');
        }

        $application = TaskApplication::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'task_id' => $task->id,
            ],
            [
                'event_id' => $task->event_id,
                'status' => 'pending',
                'message' => $request->message,
                'feedback' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]
        );

        OrgNotifier::send(
            (int) $task->event->organization_id,
            'volunteer',
            'New application received',
            Auth::user()->name.' applied for "'.$task->title.'" on "'.($task->event->title ?? 'an event').'".',
            Auth::user()->name,
            route('org.dashboard', ['section' => 'tasks']),
            'Review application',
            true,
            'application-'.$application->id
        );

        AuditTrail::record([
            'action' => 'Task application submitted',
            'action_type' => 'created',
            'module' => 'Tasks',
            'description' => Auth::user()->name.' applied for "'.$task->title.'".',
            'subject' => $application,
            'subject_label' => $task->title,
            'new' => ['event' => $task->event->title ?? 'Event', 'status' => 'pending'],
        ]);

        return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
            ->with('success', 'Application submitted. The organization will review it before assigning the task.');
    }

    /**
     * Add a skill to the volunteer's profile.
     */
    public function addSkill(Request $request)
    {
        $volunteer = Auth::user();

        $skillName = null;
        if ($request->has('skill_id')) {
            $request->validate(['skill_id' => 'required|exists:skills,id']);
            $volunteer->skills()->syncWithoutDetaching([$request->skill_id]);
            $skillName = Skill::find($request->skill_id)?->name;
        } elseif ($request->has('skill_name')) {
            $request->validate(['skill_name' => 'required|string|max:255']);
            $skill = Skill::firstOrCreate([
                'name' => Str::title(trim($request->skill_name))
            ]);
            $volunteer->skills()->syncWithoutDetaching([$skill->id]);
            $skillName = $skill->name;
        }

        if ($skillName) {
            if ($volunteer->organization_id) {
                OrgNotifier::send(
                    (int) $volunteer->organization_id,
                    'volunteer',
                    'Volunteer skills updated',
                    $volunteer->name.' added the skill "'.$skillName.'".',
                    $volunteer->name,
                    route('org.dashboard', ['section' => 'volunteers']),
                    'View volunteer'
                );
            }
            AuditTrail::record([
                'action' => 'Skill added',
                'action_type' => 'updated',
                'module' => 'Volunteers',
                'description' => $volunteer->name.' added the skill "'.$skillName.'".',
                'subject' => $volunteer,
                'subject_label' => $volunteer->name,
                'new' => ['skill' => $skillName],
            ]);
        }

        return redirect()->route('volunteer.dashboard', ['section' => 'skills'])
            ->with('success', 'Profile skills updated successfully.');
    }

    /**
     * Remove a skill from the volunteer's profile.
     */
    public function removeSkill($id)
    {
        $volunteer = Auth::user();
        $skillName = Skill::find($id)?->name ?? 'Skill';
        $volunteer->skills()->detach($id);

        AuditTrail::record([
            'action' => 'Skill removed',
            'action_type' => 'updated',
            'module' => 'Volunteers',
            'description' => $volunteer->name.' removed the skill "'.$skillName.'".',
            'subject' => $volunteer,
            'subject_label' => $volunteer->name,
            'previous' => ['skill' => $skillName],
        ]);

        return redirect()->route('volunteer.dashboard', ['section' => 'skills'])
            ->with('success', 'Skill removed from profile.');
    }

    /**
     * Toggle availability status.
     */
    public function toggleAvailability(Request $request)
    {
        $data = $request->validate([
            'availability' => 'required|in:active,limited,inactive',
            'availability_notes' => 'nullable|string|max:1000',
            'days' => 'nullable|array',
        ]);

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $schedule = [];
        foreach ($days as $day) {
            $row = $request->input('days.'.$day, []);
            if (empty($row['on'])) {
                continue;
            }
            $from = is_string($row['from'] ?? null) ? $row['from'] : '';
            $to = is_string($row['to'] ?? null) ? $row['to'] : '';
            if (($from !== '' && !preg_match('/^\d{2}:\d{2}$/', $from)) || ($to !== '' && !preg_match('/^\d{2}:\d{2}$/', $to))) {
                return redirect()->route('volunteer.dashboard', ['section' => 'availability'])
                    ->with('error', 'Use a valid time for each selected day.');
            }
            if ($from !== '' && $to !== '' && $from >= $to) {
                return redirect()->route('volunteer.dashboard', ['section' => 'availability'])
                    ->with('error', 'The end time has to be later than the start time.');
            }
            $schedule[$day] = ['on' => true, 'from' => $from, 'to' => $to];
        }

        $volunteer = User::findOrFail(Auth::id());
        $previous = $volunteer->availability;
        $volunteer->availability = $data['availability'];
        $volunteer->availability_notes = $data['availability_notes'] ?? null;
        $volunteer->availability_schedule = $schedule ?: null;
        $volunteer->save();

        AuditTrail::record([
            'action' => 'Availability updated',
            'action_type' => 'updated',
            'module' => 'Volunteers',
            'description' => $volunteer->name.' changed availability to '.$data['availability'].'.',
            'subject' => $volunteer,
            'subject_label' => $volunteer->name,
            'previous' => ['availability' => $previous],
            'new' => ['availability' => $data['availability']],
        ]);

        $statusStr = match ($data['availability']) {
            'inactive' => 'Unavailable',
            'limited' => 'Busy / Limited Availability',
            default => 'Available',
        };

        return redirect()->route('volunteer.dashboard', ['section' => 'availability'])
            ->with('success', 'Availability saved as '.$statusStr.'. Tasks you already accepted stay assigned.');
    }

    /**
     * Accept or decline a task the organization assigned.
     */
    public function respondToAssignment(Request $request, $id)
    {
        $request->validate([
            'decision' => ['required', 'in:accept,decline'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $assignment = Assignment::where('user_id', Auth::id())
            ->where('id', $id)
            ->with([
                'event' => fn ($query) => $query->withTrashed()->with('organization'),
                'task' => fn ($query) => $query->withTrashed(),
            ])
            ->firstOrFail();

        if (!$assignment->task || !$assignment->event) {
            return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
                ->with('error', 'This assignment is no longer available.');
        }

        if ($assignment->status !== 'pending') {
            return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
                ->with('error', 'This task is no longer waiting for your answer.');
        }

        if (ScheduleClock::phase($assignment->event->start_time, $assignment->event->end_time, $assignment->event->status) === 'past') {
            return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
                ->with('error', 'This event has already passed, so it can no longer be accepted or declined.');
        }

        $volunteer = Auth::user();
        $organizationId = (int) $assignment->event->organization_id;
        $taskTitle = $assignment->task->title;
        $eventTitle = $assignment->event->title ?? 'Event';

        if ($request->decision === 'accept') {
            $assignment->status = 'approved';
            $assignment->responded_at = now();
            $assignment->save();

            if (!in_array($assignment->task->status, ['completed', 'cancelled'], true)) {
                $assignment->task->status = 'assigned';
                $assignment->task->save();
            }

            OrgNotifier::send(
                $organizationId,
                'task',
                'Volunteer accepted a task',
                $volunteer->name.' accepted "'.$taskTitle.'" on "'.$eventTitle.'".',
                $eventTitle,
                route('org.dashboard', ['section' => 'tasks']),
                'View task',
                true
            );

            PortalNotifier::send(
                (int) $volunteer->id,
                'Task accepted',
                'You accepted "'.$taskTitle.'".',
                'You accepted "'.$taskTitle.'" for "'.$eventTitle.'". Submit the finished work for review when it is done.',
                'fa-list-check',
                route('volunteer.dashboard', ['section' => 'tasks'])
            );

            AuditTrail::record([
                'action' => 'Task accepted',
                'action_type' => 'approved',
                'module' => 'Tasks',
                'description' => $volunteer->name.' accepted "'.$taskTitle.'".',
                'subject' => $assignment->task,
                'subject_label' => $taskTitle,
                'previous' => ['assignment' => 'pending'],
                'new' => ['assignment' => 'approved', 'event' => $eventTitle],
            ]);

            return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
                ->with('success', 'You accepted "'.$taskTitle.'".');
        }

        $reason = trim((string) $request->input('reason'));
        $assignment->status = 'cancelled';
        $assignment->feedback = $reason === ''
            ? 'Declined by the volunteer.'
            : 'Declined by the volunteer. '.$reason;
        $assignment->responded_at = now();
        $assignment->save();

        $stillHeld = Assignment::where('task_id', $assignment->task_id)
            ->where('id', '!=', $assignment->id)
            ->whereIn('status', ['pending', 'approved', 'submitted', 'completed'])
            ->exists();

        if (!$stillHeld && !in_array($assignment->task->status, ['completed', 'cancelled'], true)) {
            $assignment->task->status = 'pending';
            $assignment->task->save();
        }

        OrgNotifier::send(
            $organizationId,
            'task',
            'Volunteer declined a task',
            $volunteer->name.' declined "'.$taskTitle.'" on "'.$eventTitle.'".'.($reason !== '' ? ' Reason: '.$reason.'.' : '').' The task is open for another volunteer.',
            $eventTitle,
            route('org.dashboard', ['section' => 'tasks']),
            'View task',
            true
        );

        PortalNotifier::send(
            (int) $volunteer->id,
            'Task declined',
            'You declined "'.$taskTitle.'".',
            'You declined "'.$taskTitle.'" for "'.$eventTitle.'".'.($reason !== '' ? ' Reason: '.$reason.'.' : ''),
            'fa-list-check',
            route('volunteer.dashboard', ['section' => 'tasks'])
        );

        AuditTrail::record([
            'action' => 'Task declined',
            'action_type' => 'updated',
            'module' => 'Tasks',
            'description' => $volunteer->name.' declined "'.$taskTitle.'".',
            'subject' => $assignment->task,
            'subject_label' => $taskTitle,
            'previous' => ['assignment' => 'pending'],
            'new' => ['assignment' => 'declined', 'event' => $eventTitle],
        ]);

        return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
            ->with('success', 'You declined "'.$taskTitle.'". The organization can assign it to someone else.');
    }

    /**
     * Submit task completion proof for organization review.
     */
    public function completeTask(Request $request, $id)
    {
        $request->validate([
            'completion_note' => ['required', 'string', 'max:1000'],
            'completion_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,docx', 'max:5120'],
        ]);

        $assignment = Assignment::where('user_id', Auth::id())
            ->where('id', $id)
            ->with(['event.organization', 'task'])
            ->firstOrFail();

        if ($assignment->status === 'completed') {
            AuditTrail::record([
                'action' => 'Task submission denied',
                'action_type' => 'other',
                'module' => 'Tasks',
                'description' => 'Did not submit "'.($assignment->task->title ?? 'a task').'" again because it is already completed.',
                'status' => 'failed',
                'subject' => $assignment->task,
                'subject_label' => $assignment->task->title ?? 'Task',
            ]);

            return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
                ->with('error', 'This duty has already been completed.');
        }

        if ($assignment->status === 'submitted') {
            AuditTrail::record([
                'action' => 'Task submission denied',
                'action_type' => 'other',
                'module' => 'Tasks',
                'description' => 'Did not submit "'.($assignment->task->title ?? 'a task').'" again because it is already waiting for review.',
                'status' => 'failed',
                'subject' => $assignment->task,
                'subject_label' => $assignment->task->title ?? 'Task',
            ]);

            return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
                ->with('error', 'This duty is already submitted and waiting for organization review.');
        }

        if (!in_array($assignment->status, ['approved', 'rejected'])) {
            AuditTrail::record([
                'action' => 'Task submission denied',
                'action_type' => 'other',
                'module' => 'Tasks',
                'description' => 'Did not submit "'.($assignment->task->title ?? 'a task').'" because it has not been approved.',
                'status' => 'failed',
                'subject' => $assignment->task,
                'subject_label' => $assignment->task->title ?? 'Task',
                'previous' => ['assignment' => $assignment->status],
            ]);

            return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
                ->with('error', 'You cannot complete a duty that has not been approved.');
        }

        $proofPath = $assignment->completion_proof_path;
        if ($request->hasFile('completion_proof')) {
            $proofPath = $request->file('completion_proof')
                ->store('task-proofs/' . Auth::id(), 'public');
        }

        $previousStatus = $assignment->status;
        $assignment->status = 'submitted';
        $assignment->completion_note = $request->completion_note;
        $assignment->completion_proof_path = $proofPath;
        $assignment->submitted_at = Carbon::now();
        $assignment->reviewed_at = null;
        $assignment->reviewed_by = null;
        $assignment->feedback = null;
        $assignment->save();

        if ($assignment->task && in_array($assignment->task->status, ['pending', 'assigned'], true)) {
            $assignment->task->status = 'in_progress';
            $assignment->task->save();
        }

        if ($assignment->event && $assignment->event->organization) {
            $taskTitle = $assignment->task->title ?? 'assigned task';
            $eventTitle = $assignment->event->title ?? 'an event';
            $orgName = $assignment->event->organization->name;

            OrgNotifier::send(
                (int) $assignment->event->organization->id,
                'task',
                'Task awaiting completion review',
                Auth::user()->name.' submitted "'.$taskTitle.'" for "'.$eventTitle.'". Review it before hours are recorded.',
                $eventTitle,
                route('org.dashboard', ['section' => 'certificates']),
                'Review submission',
                true,
                'completion-'.$assignment->id
            );

            PortalNotifier::admins(
                'Completion waiting for the organization',
                Auth::user()->name . ' submitted "' . $taskTitle . '".',
                Auth::user()->name . ' submitted "' . $taskTitle . '" for "' . $eventTitle . '" at ' . $orgName . '. The organization reviews the completion. Proof files stay with that organization.',
                'fa-clipboard-check',
                route('admin.dashboard', [
                    'tab' => 'tasks',
                    'task_org' => $assignment->event->organization_id,
                    'task_event' => $assignment->event_id,
                ])
            );
        }

        AuditTrail::record([
            'action' => 'Task submitted for review',
            'action_type' => 'updated',
            'module' => 'Tasks',
            'description' => Auth::user()->name.' submitted "'.($assignment->task->title ?? 'a task').'" for review.',
            'subject' => $assignment->task,
            'subject_label' => $assignment->task->title ?? 'Task',
            'previous' => ['assignment' => $previousStatus],
            'new' => ['assignment' => 'submitted', 'event' => $assignment->event->title ?? 'Event'],
        ]);

        return redirect()->route('volunteer.dashboard', ['section' => 'tasks'])
            ->with('success', 'Task submitted for organization review. A certificate can be rendered after the organization approves the completed work.');
    }

    /**
     * View/Print generated certificate.
     */
    public function downloadCertificate($id)
    {
        $certificate = Certificate::where('user_id', Auth::id())
            ->where('id', $id)
            ->with(['user', 'event.organization'])
            ->firstOrFail();

        AuditTrail::record([
            'action' => 'Certificate opened',
            'action_type' => 'other',
            'module' => 'Certificates',
            'description' => Auth::user()->name.' opened a certificate for "'.($certificate->event->title ?? 'an event').'".',
            'subject' => $certificate,
            'subject_label' => $certificate->event->title ?? 'Certificate',
        ]);

        return view('volunteer.certificate', compact('certificate'));
    }

    /**
     * Download the certificate as a PDF file.
     */
    public function pdfCertificate($id)
    {
        $certificate = Certificate::where('user_id', Auth::id())
            ->where('id', $id)
            ->with(['user', 'event.organization'])
            ->firstOrFail();

        $code = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $certificate->certificate_code) ?: 'certificate';

        AuditTrail::record([
            'action' => 'Certificate downloaded',
            'action_type' => 'other',
            'module' => 'Certificates',
            'description' => Auth::user()->name.' downloaded the certificate for "'.($certificate->event->title ?? 'an event').'".',
            'subject' => $certificate,
            'subject_label' => $certificate->event->title ?? 'Certificate',
        ]);

        return response(CertificatePdf::make($certificate), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$code.'.pdf"',
        ]);
    }

    /**
     * Process Chatbot Question for Volunteer.
     */
    public function askChatbot(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500'
        ]);

        $volunteer = Auth::user();
        $message = trim($request->message);
        $organization = $this->volunteerOrganization($volunteer);
        $conversation = $organization
            ? OrgConversation::firstOrCreate(
                ['organization_id' => $organization->id, 'volunteer_id' => $volunteer->id],
                ['mode' => 'assistant']
            )
            : null;

        $orgOnline = $organization && Cache::has('org-online-'.$organization->id);
        $withAgent = $conversation?->withAgent() || $orgOnline;
        $answer = $withAgent ? null : app(VolunteerAssistant::class)->answer($volunteer, $message, $organization);
        $needsTakeover = $answer === null;
        $response = $answer['response'] ?? VolunteerAssistant::HANDOFF;

        $chatLog = ChatbotResponse::create([
            'user_id' => $volunteer->id,
            'organization_id' => $organization?->id,
            'message' => $message,
            'response' => $withAgent ? '' : $response,
            'intent' => $answer['intent'] ?? ($withAgent ? 'agent' : 'handoff'),
            'confidence' => $answer['confidence'] ?? 0,
            'needs_takeover' => $needsTakeover && !$withAgent,
            'answered_by' => $answer ? 'assistant' : 'pending',
        ]);

        if ($conversation) {
            $conversation->forceFill([
                'needs_takeover' => $conversation->needs_takeover || ($needsTakeover && !$withAgent),
                'last_message_at' => $chatLog->created_at,
            ])->save();

            if ($needsTakeover && !$withAgent) {
                PortalNotifier::send(
                    (int) $organization->id,
                    'A volunteer needs a reply',
                    $volunteer->name.' asked something the assistant could not answer.',
                    $volunteer->name.' asked: "'.$message.'". Take over the conversation to reply.',
                    'fa-comment',
                    route('org.dashboard', ['section' => 'messages', 'volunteer' => $volunteer->id])
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => $chatLog->message,
            'response' => $chatLog->response,
            'show_assistant' => $chatLog->answered_by === 'assistant' || $chatLog->needs_takeover,
            'with_agent' => (bool) $withAgent,
            'organization' => $organization?->name,
            'intent' => $chatLog->intent,
            'time' => $chatLog->created_at->format('g:i A'),
        ]);
    }

    /**
     * Retrieve Chatbot Conversation History for Volunteer.
     */
    public function chatbotHistory()
    {
        $volunteer = Auth::user();
        $organization = $this->volunteerOrganization($volunteer);
        $conversation = $organization
            ? OrgConversation::where('organization_id', $organization->id)->where('volunteer_id', $volunteer->id)->first()
            : null;

        $history = ChatbotResponse::where('user_id', $volunteer->id)
            ->when($organization, fn ($query) => $query->where('organization_id', $organization->id))
            ->orderBy('created_at', 'asc')
            ->take(40)
            ->get()
            ->map(function ($item) use ($organization) {
                return [
                    'message' => $item->message,
                    'response' => $item->response,
                    'show_assistant' => $item->answered_by === 'assistant' || (bool) $item->needs_takeover,
                    'admin_reply' => $item->admin_reply,
                    'organization' => $organization?->name ?? 'Organization',
                    'intent' => $item->intent,
                    'time' => $item->created_at->format('g:i A'),
                    'admin_time' => $item->admin_replied_at?->format('g:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'mode' => $conversation?->mode ?? 'assistant',
            'organization' => $organization?->name,
            'organization_online' => $organization ? Cache::has('org-online-'.$organization->id) : false,
            'history' => $history,
        ]);
    }

    private function volunteerOrganization(User $volunteer): ?User
    {
        if ($volunteer->organization_id) {
            $organization = User::where('id', $volunteer->organization_id)->where('role', 'organization')->first();
            if ($organization) {
                return $organization;
            }
        }

        $eventId = Assignment::where('user_id', $volunteer->id)->whereHas('event')->latest('id')->value('event_id')
            ?? TaskApplication::where('user_id', $volunteer->id)->whereHas('event')->latest('id')->value('event_id');

        if (!$eventId) {
            return null;
        }

        $organizationId = Event::where('id', $eventId)->value('organization_id');

        return $organizationId
            ? User::where('id', $organizationId)->where('role', 'organization')->first()
            : null;
    }

    public function readAllNotifications()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->route('volunteer.dashboard', ['section' => 'notifications'])
            ->with('success', 'All notifications marked as read.');
    }

    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $volunteer = Auth::user();
        $organization = $this->volunteerOrganization($volunteer);
        if (!$organization) {
            return redirect()->route('volunteer.dashboard', ['section' => 'messages'])
                ->with('error', 'There is no organization available for this message.');
        }

        $conversation = OrgConversation::firstOrCreate(
            ['organization_id' => $organization->id, 'volunteer_id' => $volunteer->id],
            ['mode' => 'assistant']
        );

        $chatLog = ChatbotResponse::create([
            'user_id' => $volunteer->id,
            'organization_id' => $organization->id,
            'message' => trim($data['message']),
            'response' => '',
            'intent' => 'message',
            'confidence' => 0,
            'needs_takeover' => true,
            'answered_by' => 'pending',
        ]);

        $conversation->forceFill([
            'needs_takeover' => true,
            'last_message_at' => $chatLog->created_at,
        ])->save();

        PortalNotifier::send(
            (int) $organization->id,
            'New volunteer message',
            $volunteer->name.' sent a message.',
            $volunteer->name.' wrote: "'.$chatLog->message.'"',
            'fa-comment',
            route('org.dashboard', ['section' => 'messages', 'volunteer' => $volunteer->id])
        );

        OrgNotifier::send(
            (int) $organization->id,
            'volunteer',
            'New volunteer message',
            $volunteer->name.' sent a message: '.$chatLog->message,
            $volunteer->name,
            route('org.dashboard', ['section' => 'messages', 'volunteer' => $volunteer->id]),
            'Open messages'
        );

        return redirect()->route('volunteer.dashboard', ['section' => 'messages'])
            ->with('success', 'Message sent.');
    }

    public function updateSettings(Request $request)
    {
        $volunteer = User::findOrFail(Auth::id());
        $section = (string) $request->input('section');

        if ($section === 'email') {
            $data = $request->validate([
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$volunteer->id],
                'current_password' => ['required', 'current_password'],
            ]);

            $volunteer->email = $data['email'];
            $volunteer->email_verified_at = null;
            $volunteer->save();

            return redirect()->route('volunteer.dashboard', ['section' => 'settings'])
                ->with('success', 'Email address updated. Email verification is not turned on, so the new address is active now.');
        }

        if ($section === 'preferences') {
            $preferences = $volunteer->notification_preferences ?? [];
            foreach (['assignments', 'events', 'certificates', 'reminders'] as $key) {
                $preferences[$key] = $request->boolean($key);
            }
            $volunteer->notification_preferences = $preferences;
            $volunteer->save();

            return redirect()->route('volunteer.dashboard', ['section' => 'settings'])
                ->with('success', 'Notification preferences saved.');
        }

        if ($section === 'deactivate') {
            $request->validate(['confirm' => ['required', 'accepted']]);
            $preferences = $volunteer->notification_preferences ?? [];
            if (!empty($preferences['deactivation_requested_at'])) {
                return redirect()->route('volunteer.dashboard', ['section' => 'settings'])
                    ->with('error', 'A deactivation request is already on file. The organization has to handle it.');
            }

            $preferences['deactivation_requested_at'] = now()->toIso8601String();
            $volunteer->notification_preferences = $preferences;
            $volunteer->save();

            $organizationId = (int) ($this->volunteerOrganization($volunteer)?->id ?? 0);
            if ($organizationId > 0) {
                OrgNotifier::send(
                    $organizationId,
                    'account',
                    'Deactivation requested',
                    $volunteer->name.' asked to deactivate their volunteer account. The account is still active until an administrator handles the request.',
                    $volunteer->name,
                    route('org.dashboard', ['section' => 'volunteers']),
                    'View volunteers',
                    false,
                    'deactivation-request-'.$volunteer->id
                );
            }

            return redirect()->route('volunteer.dashboard', ['section' => 'settings'])
                ->with('success', 'Deactivation request sent. Your account stays active until it is reviewed.');
        }

        abort(404);
    }

    public function reports(Request $request)
    {
        AuditTrail::report($request);

        return view('reports.show', [
            'report' => ActivityReport::build($request, 'volunteer', Auth::user()),
        ]);
    }

    public function exportReport(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'xls'], true), 404);

        $report = ActivityReport::build($request, 'volunteer', Auth::user());
        AuditTrail::report($request, $format);

        return $format === 'pdf' ? ReportFiles::pdf($report) : ReportFiles::excel($report);
    }
}
