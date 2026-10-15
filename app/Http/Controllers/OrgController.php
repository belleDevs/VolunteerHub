<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\Task;
use App\Models\Skill;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\TaskApplication;
use App\Models\OrgConversation;
use App\Models\ChatbotResponse;
use App\Models\VolunteerReward;
use App\Models\RecordArchive;
use App\Mail\TaskVolunteerInviteMail;
use App\Support\ActivityReport;
use App\Support\OrgNotifier;
use App\Support\OrgPreferences;
use App\Support\AuditTrail;
use App\Support\PortalNotifier;
use App\Models\Officer;
use App\Models\OfficerAlbum;
use App\Models\OrganizationNotice;
use App\Support\RoleCatalog;
use App\Support\VolunteerNotice;
use App\Support\ReportFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrgController extends Controller
{
    /**
     * Display the Organization Dashboard.
     */
    public function dashboard(Request $request)
    {
        if ($request->query('section') === 'matching') {
            return redirect()->route('org.dashboard', ['section' => 'tasks']);
        }

        $organization = Auth::user();
        $orgId = $organization->id;
        $now = Carbon::now();

        $myEventsCount = Event::where('organization_id', $orgId)->count();

        // Count unique volunteers assigned to tasks/events of this organization
        $eventIds = Event::where('organization_id', $orgId)->pluck('id')->toArray();
        $assignedVolunteersCount = Assignment::whereIn('event_id', $eventIds)
            ->whereHas('user')
            ->distinct('user_id')
            ->count('user_id');

        $linkedVolunteerIds = collect($eventIds === [] ? [] : Assignment::whereIn('event_id', $eventIds)->pluck('user_id'))
            ->merge($eventIds === [] ? [] : TaskApplication::whereIn('event_id', $eventIds)->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        $organizationVolunteers = User::where('role', 'volunteer')
            ->where(function ($query) use ($orgId, $linkedVolunteerIds) {
                $query->where('organization_id', $orgId);
                if ($linkedVolunteerIds->isNotEmpty()) {
                    $query->orWhereIn('id', $linkedVolunteerIds);
                }
            })
            ->with([
                'skills',
                'assignments' => function ($query) use ($eventIds) {
                    $query->whereIn('event_id', $eventIds)->with(['event', 'task']);
                },
                'certificates' => function ($query) use ($eventIds) {
                    $query->whereIn('event_id', $eventIds)->with('event');
                },
                'taskApplications' => function ($query) use ($eventIds) {
                    $query->whereIn('event_id', $eventIds)->with(['event', 'task']);
                },
            ])
            ->orderBy('name')
            ->get();

        $events = Event::where('organization_id', $orgId)
            ->with(['tasks.skills', 'assignments.user'])
            ->orderBy('start_time', 'asc')
            ->get();
        $activeEvents = $events->filter(fn($event) => !in_array($event->status, ['completed', 'cancelled']));
        $archivedEvents = $events->filter(fn($event) => in_array($event->status, ['completed', 'cancelled']))
            ->sortByDesc('end_time');

        $complianceDocuments = RecordArchive::where('table_name', 'compliance_documents')
            ->where('archived_by', $orgId)
            ->orderBy('id', 'desc')
            ->get();

        $completionReviews = Assignment::whereIn('event_id', $eventIds)
            ->where('status', 'submitted')
            ->with(['user', 'event', 'task'])
            ->latest('submitted_at')
            ->get();

        $taskApplications = TaskApplication::whereIn('event_id', $eventIds)
            ->where('status', 'pending')
            ->with(['user.skills', 'event', 'task.skills'])
            ->latest()
            ->get();

        // Open tasks on live events, plus overdue tasks the organization still needs to reassign.
        $allOrgTasks = Task::whereIn('event_id', $eventIds)
            ->whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->where(function ($query) use ($now) {
                $query->whereHas('event', function ($event) use ($now) {
                    $event->where('end_time', '>=', $now)
                        ->where('status', 'published');
                })->orWhere(function ($overdue) use ($now) {
                    $overdue->whereNotNull('due_date')->where('due_date', '<', $now);
                });
            })
            ->with(['skills', 'event', 'assignments.user'])
            ->get();

        // Run volunteer matching if a task is selected
        $selectedTaskId = $request->query('task_id');
        if ($selectedTaskId && !$allOrgTasks->contains('id', (int) $selectedTaskId)) {
            $selectedTaskId = null;
        }

        if (!$selectedTaskId && $allOrgTasks->count() > 0) {
            $selectedTaskId = $allOrgTasks->first()->id;
        }

        $selectedTask = null;
        $skillMatches = [];

        if ($selectedTaskId) {
            $selectedTask = Task::with(['skills', 'assignments.user'])->find($selectedTaskId);
            if ($selectedTask) {
                $requiredSkills = $selectedTask->skills;
                $requiredSkillIds = $requiredSkills->pluck('id')->toArray();

                if (count($requiredSkillIds) > 0) {
                    $volunteers = User::where('role', 'volunteer')
                        ->where('status', 'approved')
                        ->with('skills')
                        ->get();

                    foreach ($volunteers as $vol) {
                        $volSkillIds = $vol->skills->pluck('id')->toArray();
                        $matchingSkills = array_intersect($requiredSkillIds, $volSkillIds);
                        if (count($matchingSkills) === 0) {
                            continue;
                        }

                        $score = round((count($matchingSkills) / count($requiredSkillIds)) * 100);

                        $skillMatches[] = [
                            'volunteer' => $vol,
                            'score' => $score,
                            'matched_skills' => $requiredSkills->filter(fn($sk) => in_array($sk->id, $volSkillIds)),
                        ];
                    }

                    // Sort by score descending, then by volunteer name
                    usort($skillMatches, function ($a, $b) {
                        if ($b['score'] === $a['score']) {
                            return strcmp($a['volunteer']->name, $b['volunteer']->name);
                        }
                        return $b['score'] <=> $a['score'];
                    });
                }
            }
        }

        // Fetch all available skills to display when creating tasks
        $skills = Skill::all();

        $section = $request->query('section', 'dashboard');
        $sections = ['dashboard', 'events', 'gallery', 'tasks', 'volunteers', 'officers', 'notifications', 'certificates', 'messages', 'settings'];
        if (!in_array($section, $sections, true)) {
            $section = 'dashboard';
        }

        $eventsThisWeek = $activeEvents->filter(function ($event) use ($now) {
            return $event->created_at && $event->created_at->gte($now->copy()->startOfWeek());
        })->count();

        $pendingAssignmentsCount = Assignment::whereIn('event_id', $eventIds)
            ->whereNotIn('status', ['completed', 'rejected'])
            ->count();

        $taskQuery = Task::whereIn('event_id', $eventIds)->where('status', '!=', 'cancelled');
        $completedTasksCount = (clone $taskQuery)->where('status', 'completed')->count();
        $trackedTasksCount = (clone $taskQuery)->count();
        $completionRate = $trackedTasksCount > 0
            ? (int) round(($completedTasksCount / $trackedTasksCount) * 100)
            : 0;

        $certificateQuery = Certificate::whereIn('event_id', $eventIds);
        $certificatesCount = (clone $certificateQuery)->count();
        $certificatesThisMonth = (clone $certificateQuery)
            ->where('issued_at', '>=', $now->copy()->startOfMonth())
            ->count();

        $upcomingEvents = $activeEvents
            ->filter(fn ($event) => $event->end_time && $event->end_time->gte($now))
            ->sortBy('start_time')
            ->values();

        $assignedVolunteerIds = Assignment::whereIn('event_id', $eventIds)->pluck('user_id')->filter()->unique()->values();
        $availabilityVolunteers = User::where('role', 'volunteer')
            ->where(function ($query) use ($orgId, $assignedVolunteerIds) {
                $query->where('organization_id', $orgId);
                if ($assignedVolunteerIds->isNotEmpty()) {
                    $query->orWhereIn('id', $assignedVolunteerIds);
                }
            })
            ->orderBy('name')
            ->get();

        $assignableVolunteers = User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->with('skills')
            ->orderByRaw('case when organization_id = ? then 0 else 1 end', [$orgId])
            ->orderBy('name')
            ->get();

        $activeEventsCount = $activeEvents->count();

        $dashboardTasks = Task::whereIn('event_id', $eventIds)
            ->with(['event', 'assignments.user', 'skills'])
            ->get();

        $taskStatusCounts = [
            'pending' => $dashboardTasks->where('status', 'pending')->count(),
            'assigned' => $dashboardTasks->where('status', 'assigned')->count(),
            'in_progress' => $dashboardTasks->where('status', 'in_progress')->count(),
            'completed' => $dashboardTasks->where('status', 'completed')->count(),
            'overdue' => $dashboardTasks->filter(fn (Task $task) => $task->isOverdue())->count(),
        ];
        $activeTasksCount = $dashboardTasks->whereIn('status', ['pending', 'assigned', 'in_progress'])->count();
        $upcomingEventsCount = $events->filter(fn (Event $event) => $event->oversightStatus() === 'upcoming')->count();
        $dashboardUpcoming = $events
            ->filter(function (Event $event) use ($now) {
                return !in_array($event->status, ['completed', 'cancelled'], true)
                    && $event->end_time
                    && $event->end_time->gte($now);
            })
            ->sortBy('start_time')
            ->take(4)
            ->values();
        $openDashboardTasks = $dashboardTasks
            ->filter(fn (Task $task) => !in_array($task->status, ['completed', 'cancelled'], true))
            ->sortBy(fn (Task $task) => $task->due_date?->timestamp ?? PHP_INT_MAX)
            ->take(5)
            ->values();

        $taskIds = $dashboardTasks->pluck('id');
        $applicationIds = TaskApplication::whereIn('event_id', $eventIds)->pluck('id');
        $assignmentIds = Assignment::whereIn('event_id', $eventIds)->pluck('id');
        $auditActivities = AuditLog::query()
            ->where(function ($query) use ($orgId, $eventIds, $taskIds, $applicationIds, $assignmentIds) {
                $query->where('user_id', $orgId)->whereIn('module', ['Events', 'Tasks', 'Volunteers', 'Certificates']);
                if ($eventIds !== []) {
                    $query->orWhere(function ($owned) use ($eventIds) {
                        $owned->where('subject_type', Event::class)->whereIn('subject_id', $eventIds);
                    });
                }
                if ($taskIds->isNotEmpty()) {
                    $query->orWhere(function ($owned) use ($taskIds) {
                        $owned->where('subject_type', Task::class)->whereIn('subject_id', $taskIds);
                    });
                }
                if ($applicationIds->isNotEmpty()) {
                    $query->orWhere(function ($owned) use ($applicationIds) {
                        $owned->where('subject_type', TaskApplication::class)->whereIn('subject_id', $applicationIds);
                    });
                }
                if ($assignmentIds->isNotEmpty()) {
                    $query->orWhere(function ($owned) use ($assignmentIds) {
                        $owned->where('subject_type', Assignment::class)->whereIn('subject_id', $assignmentIds);
                    });
                }
            })
            ->whereNotIn('action', ['Logged in', 'Logged out', 'Login failed', 'Login denied'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        $recentActivities = collect();
        foreach ($auditActivities as $activity) {
            $recentActivities->push([
                'title' => $activity->description ?: $activity->action,
                'detail' => $activity->subject_label,
                'at' => $activity->created_at,
            ]);
        }
        foreach (TaskApplication::whereIn('event_id', $eventIds)->with(['user', 'task', 'event'])->latest()->limit(8)->get() as $application) {
            $recentActivities->push([
                'title' => ($application->user->name ?? 'A volunteer').' applied for '.($application->task->title ?? 'a task').'.',
                'detail' => $application->event->title,
                'at' => $application->created_at,
            ]);
        }
        foreach (Assignment::whereIn('event_id', $eventIds)->with(['user', 'task', 'event'])->latest('updated_at')->limit(12)->get() as $assignment) {
            $who = $assignment->user->name ?? 'A volunteer';
            $taskTitle = $assignment->task->title ?? 'a task';
            $title = match ($assignment->status) {
                'submitted' => $who.' submitted '.$taskTitle.' for completion review.',
                'completed' => 'Task marked as completed: '.$taskTitle,
                'approved', 'assigned' => $who.' was assigned to '.$taskTitle.'.',
                'rejected' => 'An assignment for '.$who.' was declined.',
                default => null,
            };
            if ($title === null) {
                continue;
            }
            $recentActivities->push([
                'title' => $title,
                'detail' => $assignment->event->title,
                'at' => $assignment->reviewed_at ?? $assignment->submitted_at ?? $assignment->updated_at,
            ]);
        }
        foreach ($events as $ownedEvent) {
            if ($ownedEvent->updated_at && $ownedEvent->created_at && $ownedEvent->updated_at->gt($ownedEvent->created_at->copy()->addMinutes(2))) {
                $recentActivities->push([
                    'title' => 'Event details were updated: '.$ownedEvent->title,
                    'detail' => $ownedEvent->status === 'draft' ? 'Pending Approval' : $ownedEvent->oversightLabel(),
                    'at' => $ownedEvent->updated_at,
                ]);
            }
        }
        $recentActivities = $recentActivities
            ->filter(fn ($item) => $item['at'])
            ->sortByDesc(fn ($item) => $item['at']->getTimestamp())
            ->unique(fn ($item) => $item['title'].'|'.($item['detail'] ?? ''))
            ->take(8)
            ->values();

        $recentVolunteers = User::where('role', 'volunteer')
            ->where(function ($query) use ($orgId, $assignedVolunteerIds) {
                $query->where('organization_id', $orgId);
                if ($assignedVolunteerIds->isNotEmpty()) {
                    $query->orWhereIn('id', $assignedVolunteerIds);
                }
            })
            ->with('skills')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();
        $awaitingAssignmentCount = $taskApplications->pluck('user_id')->unique()->count();

        $reminders = collect();
        foreach ($events->where('status', 'draft') as $draftEvent) {
            $reminders->push([
                'title' => 'Event awaiting approval',
                'detail' => $draftEvent->title.' is pending admin approval.',
                'when' => $draftEvent->created_at?->diffForHumans() ?? 'Waiting',
                'tone' => 'amber',
            ]);
        }
        foreach ($dashboardTasks->filter(fn (Task $task) => $task->isOverdue())->take(3) as $overdueTask) {
            $reminders->push([
                'title' => 'Overdue task',
                'detail' => $overdueTask->title.' was due '.$overdueTask->due_date->format('M d, Y g:i A').'.',
                'when' => $overdueTask->due_date->diffForHumans(),
                'tone' => 'rose',
            ]);
        }
        foreach ($dashboardTasks->filter(function (Task $task) use ($now) {
            return $task->due_date
                && $task->due_date->isFuture()
                && $task->due_date->lte($now->copy()->addDays(3))
                && !in_array($task->status, ['completed', 'cancelled'], true);
        })->take(3) as $dueSoon) {
            $reminders->push([
                'title' => 'Task deadline approaching',
                'detail' => $dueSoon->title.' is due '.$dueSoon->due_date->format('M d, Y g:i A').'.',
                'when' => $dueSoon->due_date->diffForHumans(),
                'tone' => 'amber',
            ]);
        }
        foreach ($dashboardUpcoming->filter(fn (Event $event) => $event->start_time && $event->start_time->isFuture() && $event->start_time->lte($now->copy()->addDays(7))) as $soonEvent) {
            $reminders->push([
                'title' => 'Upcoming event',
                'detail' => $soonEvent->title.' starts '.$soonEvent->start_time->format('M d, Y g:i A').'.',
                'when' => $soonEvent->start_time->diffForHumans(),
                'tone' => 'blue',
            ]);
        }
        if ($taskApplications->isNotEmpty()) {
            $reminders->push([
                'title' => 'New volunteer applications',
                'detail' => $taskApplications->count().' application'.($taskApplications->count() === 1 ? '' : 's').' waiting for review.',
                'when' => 'Now',
                'tone' => 'blue',
            ]);
        }
        if ($completionReviews->isNotEmpty()) {
            $reminders->push([
                'title' => 'Completion review waiting',
                'detail' => $completionReviews->count().' submitted task'.($completionReviews->count() === 1 ? '' : 's').' need verification before hours and certificates are issued.',
                'when' => 'Now',
                'tone' => 'amber',
            ]);
        }
        foreach (Auth::user()->notifications()->latest()->take(4)->get() as $notice) {
            $data = is_array($notice->data) ? $notice->data : json_decode($notice->data, true);
            $reminders->push([
                'title' => $data['title'] ?? 'Notification',
                'detail' => $data['message'] ?? ($data['summary'] ?? ''),
                'when' => $notice->created_at->diffForHumans(),
                'tone' => $notice->read_at ? 'slate' : 'blue',
            ]);
        }
        $reminders = $reminders->take(6)->values();

        $messageThreads = collect();
        $selectedMessageVolunteer = null;
        $selectedConversation = null;
        $messageEntries = collect();
        $issuedCertificates = collect();
        $certificateChoices = collect();
        $sentNotices = collect();
        $noticeRecipients = collect();
        $orgNotices = collect();
        $orgNoticeBox = 'all';
        $orgNoticeCategory = '';
        $orgNoticeSearch = '';
        $settingsPane = 'profile';
        $accountSessions = collect();
        $notificationPreferences = OrgPreferences::defaults();
        $officerAlbums = collect();
        $openOfficerAlbum = null;
        $galleryEvents = collect();
        $openGalleryEvent = null;
        if ($section === 'notifications') {
            $sentNotices = OrganizationNotice::where('organization_id', $orgId)->latest()->take(30)->get();
            $noticeRecipients = VolunteerNotice::linkedVolunteers($organization);
            $orgNoticeBox = $request->query('box') === 'unread' ? 'unread' : 'all';
            $orgNoticeCategory = (string) $request->query('category', '');
            $orgNoticeSearch = (string) $request->query('q', '');
            $orgNotices = OrgNotifier::inbox($orgId, $orgNoticeBox, $orgNoticeCategory, $orgNoticeSearch);
        }
        if ($section === 'certificates') {
            [$issuedCertificates, $certificateChoices] = $this->certificateLists($eventIds);
        }
        if ($section === 'officers') {
            $currentYear = (int) $now->year;
            OfficerAlbum::firstOrCreate([
                'organization_id' => $orgId,
                'year' => $currentYear,
            ]);
            $officerAlbums = OfficerAlbum::where('organization_id', $orgId)
                ->with('officers')
                ->orderByDesc('year')
                ->get()
                ->each(function (OfficerAlbum $album) {
                    $album->setRelation('officers', $album->officers
                        ->sortBy(fn (Officer $officer) => [Officer::rank($officer->position), strtolower($officer->name)])
                        ->values());
                });
            $requestedYear = (int) $request->query('year', $currentYear);
            $openOfficerAlbum = $officerAlbums->firstWhere('year', $requestedYear) ?? $officerAlbums->first();
        }
        if ($section === 'gallery') {
            $galleryEvents = Event::where('organization_id', $orgId)
                ->with('photos')
                ->orderByDesc('start_time')
                ->get();
            $requestedEvent = (int) $request->query('event');
            $openGalleryEvent = $galleryEvents->firstWhere('id', $requestedEvent) ?? $galleryEvents->first();
        }
        if ($section === 'settings') {
            $requestedPane = (string) $request->query('pane', 'profile');
            $settingsPane = in_array($requestedPane, ['profile', 'security', 'notifications', 'preferences', 'documents'], true)
                ? $requestedPane
                : 'profile';
            $notificationPreferences = OrgPreferences::forUser($organization);
            if ($settingsPane === 'security') {
                $accountSessions = DB::table('sessions')
                    ->where('user_id', $orgId)
                    ->orderByDesc('last_activity')
                    ->get();
            }
        }
        if ($section === 'messages') {
            Cache::put('org-online-'.$orgId, true, now()->addMinutes(3));
            $messageThreads = $this->organizationMessageThreads($orgId);
            $selectedId = (int) $request->query('volunteer');
            $selectedMessageVolunteer = $messageThreads->firstWhere('id', $selectedId) ?? $messageThreads->first();
            if ($selectedMessageVolunteer) {
                $selectedConversation = OrgConversation::where('organization_id', $orgId)
                    ->where('volunteer_id', $selectedMessageVolunteer->id)
                    ->first();
                $messageEntries = ChatbotResponse::where('organization_id', $orgId)
                    ->where('user_id', $selectedMessageVolunteer->id)
                    ->orderBy('created_at')
                    ->get();
            }
        }

        return view('org.dashboard', compact(
            'organization',
            'myEventsCount',
            'assignedVolunteersCount',
            'organizationVolunteers',
            'events',
            'activeEvents',
            'archivedEvents',
            'complianceDocuments',
            'completionReviews',
            'taskApplications',
            'allOrgTasks',
            'selectedTaskId',
            'selectedTask',
            'skillMatches',
            'skills',
            'section',
            'eventsThisWeek',
            'pendingAssignmentsCount',
            'completedTasksCount',
            'completionRate',
            'certificatesCount',
            'certificatesThisMonth',
            'upcomingEvents',
            'availabilityVolunteers',
            'assignableVolunteers',
            'activeEventsCount',
            'dashboardTasks',
            'taskStatusCounts',
            'activeTasksCount',
            'upcomingEventsCount',
            'dashboardUpcoming',
            'openDashboardTasks',
            'recentActivities',
            'recentVolunteers',
            'awaitingAssignmentCount',
            'reminders',
            'messageThreads',
            'selectedMessageVolunteer',
            'selectedConversation',
            'messageEntries',
            'issuedCertificates',
            'certificateChoices',
            'sentNotices',
            'noticeRecipients',
            'orgNotices',
            'orgNoticeBox',
            'orgNoticeCategory',
            'orgNoticeSearch',
            'settingsPane',
            'accountSessions',
            'notificationPreferences',
            'officerAlbums',
            'openOfficerAlbum',
            'galleryEvents',
            'openGalleryEvent'
        ));
    }

    /**
     * Add a task to an event this organization already owns.
     */
    public function storeTask(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'role' => ['nullable', 'string', 'max:255'],
            'custom_role' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
        ]);

        $event = Event::where('id', $data['event_id'])
            ->where('organization_id', Auth::id())
            ->firstOrFail();

        $chosen = ($data['role'] ?? '') === '__custom__'
            ? ($data['custom_role'] ?? '')
            : ($data['role'] ?? ($data['title'] ?? ''));
        $title = trim((string) $chosen);
        if ($title === '') {
            return back()->withErrors(['role' => 'Choose a role, or add the role this event needs.'])->withInput();
        }

        $task = Task::create([
            'event_id' => $event->id,
            'title' => $title,
            'description' => 'Role added for '.$event->title.'.',
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'pending',
            'due_date' => !empty($data['due_date']) ? Carbon::parse($data['due_date']) : $event->start_time,
        ]);

        $relatedSkillIds = Skill::whereIn('name', RoleCatalog::relatedSkillNames($title))->pluck('id');
        if ($relatedSkillIds->isNotEmpty()) {
            $task->skills()->sync($relatedSkillIds);
        }

        AuditTrail::record([
            'action' => 'Task created',
            'action_type' => 'created',
            'module' => 'Tasks',
            'description' => 'Added "'.$task->title.'" to "'.$event->title.'".',
            'subject' => $task,
            'subject_label' => $task->title,
            'new' => ['event' => $event->title, 'status' => 'pending'],
        ]);

        OrgNotifier::send(
            (int) Auth::id(),
            'task',
            'Task created',
            '"'.$task->title.'" was added to "'.$event->title.'".',
            $event->title,
            route('org.dashboard', ['section' => 'tasks']),
            'View task'
        );

        return redirect()->route('org.dashboard', ['section' => 'tasks'])
            ->with('success', 'Role "'.$task->title.'" was added to '.$event->title.'.');
    }

    public function openOrganizationNotice(string $notification)
    {
        $row = OrgNotifier::owned((int) Auth::id(), $notification);
        abort_unless($row, 404);
        DB::table('notifications')->where('id', $notification)->update(['read_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        $data = json_decode($row->data, true) ?: [];
        $url = is_string($data['url'] ?? null) ? $data['url'] : route('org.dashboard', ['section' => 'notifications']);
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $query = parse_url($url, PHP_URL_QUERY);
        if (!str_starts_with($path, '/org')) {
            $path = parse_url(route('org.dashboard', ['section' => 'notifications']), PHP_URL_PATH);
            $query = 'section=notifications';
        }

        return redirect($path.($query ? '?'.$query : ''));
    }

    public function readOrganizationNotice(string $notification)
    {
        abort_unless(OrgNotifier::owned((int) Auth::id(), $notification), 404);
        DB::table('notifications')->where('id', $notification)->whereNull('read_at')->update([
            'read_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('org.dashboard', ['section' => 'notifications']);
    }

    public function readAllOrganizationNotices()
    {
        DB::table('notifications')
            ->where('notifiable_id', Auth::id())
            ->where('notifiable_type', User::class)
            ->whereNull('dismissed_at')
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

        return redirect()->route('org.dashboard', ['section' => 'notifications'])
            ->with('success', 'All notifications are marked as read.');
    }

    public function dismissOrganizationNotice(string $notification)
    {
        abort_unless(OrgNotifier::owned((int) Auth::id(), $notification), 404);
        DB::table('notifications')->where('id', $notification)->update([
            'dismissed_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('org.dashboard', ['section' => 'notifications'])
            ->with('success', 'Notification dismissed. The activity record stays in the audit log.');
    }

    public function updateOrganizationProfile(Request $request)
    {
        $organization = $this->currentOrganization();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization_type' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(User::ORGANIZATION_TYPES))],
            'bio' => ['nullable', 'string', 'max:2000'],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $organization->fill([
            'name' => $data['name'],
            'organization_type' => $data['organization_type'],
            'bio' => $data['bio'] ?? null,
            'representative_name' => $data['representative_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'website' => $data['website'] ?? null,
            'facebook_url' => $data['facebook_url'] ?? null,
            'instagram_url' => $data['instagram_url'] ?? null,
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($organization->profile_photo_path) {
                Storage::disk('public')->delete($organization->profile_photo_path);
            }
            $organization->profile_photo_path = $request->file('profile_photo')
                ->store('profile-photos/'.$organization->id, 'public');
        }

        $organization->save();
        $this->auditSettings($organization, 'Organization profile updated', 'Updated the organization profile.');

        return redirect()->route('org.dashboard', ['section' => 'settings', 'pane' => 'profile'])
            ->with('success', 'Organization profile saved.');
    }

    public function updateOrganizationSecurity(Request $request)
    {
        $organization = $this->currentOrganization();
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$organization->id],
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $previousEmail = $organization->email;
        $organization->email = $data['email'];
        if (!empty($data['password'])) {
            $organization->password = $data['password'];
        }
        $organization->save();

        $this->auditSettings(
            $organization,
            !empty($data['password']) ? 'Password changed' : 'Login email updated',
            !empty($data['password'])
                ? $organization->name.' changed the account password.'
                : $organization->name.' changed the login email from '.$previousEmail.' to '.$organization->email.'.'
        );

        return redirect()->route('org.dashboard', ['section' => 'settings', 'pane' => 'security'])
            ->with('success', 'Account security settings saved.');
    }

    public function revokeOrganizationSession(string $sessionId)
    {
        $organization = $this->currentOrganization();
        abort_if($sessionId === session()->getId(), 422);

        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $organization->id)
            ->delete();
        abort_unless($deleted === 1, 404);

        $this->auditSettings($organization, 'Session signed out', 'Signed out another active session.');

        return redirect()->route('org.dashboard', ['section' => 'settings', 'pane' => 'security'])
            ->with('success', 'That session was signed out.');
    }

    public function updateOrganizationNotifications(Request $request)
    {
        $organization = $this->currentOrganization();
        $preferences = [];
        foreach (array_keys(OrgPreferences::NOTIFICATION_KEYS) as $key) {
            $preferences[$key] = $request->boolean($key);
        }
        $organization->notification_preferences = $preferences;
        $organization->save();
        $this->auditSettings($organization, 'Notification preferences updated', 'Updated which organization notifications are emailed.');

        return redirect()->route('org.dashboard', ['section' => 'settings', 'pane' => 'notifications'])
            ->with('success', 'Notification preferences saved.');
    }

    public function updateOrganizationPreferences(Request $request)
    {
        $organization = $this->currentOrganization();
        $data = $request->validate([
            'timezone' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(OrgPreferences::TIMEZONES))],
            'date_format' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(OrgPreferences::DATE_FORMATS))],
            'locale' => ['required', 'in:en'],
        ]);

        $organization->timezone = $data['timezone'];
        $organization->date_format = $data['date_format'];
        $organization->save();
        $this->auditSettings($organization, 'Display preferences updated', 'Updated the organization time zone and date format.');

        return redirect()->route('org.dashboard', ['section' => 'settings', 'pane' => 'preferences'])
            ->with('success', 'Display preferences saved.');
    }

    public function storeOfficerAlbum(Request $request)
    {
        $organization = $this->currentOrganization();
        $data = $request->validate([
            'year' => [
                'required',
                'integer',
                'min:1990',
                'max:2100',
                \Illuminate\Validation\Rule::unique('officer_albums', 'year')->where('organization_id', $organization->id),
            ],
        ]);

        OfficerAlbum::create([
            'organization_id' => $organization->id,
            'year' => $data['year'],
        ]);
        $this->auditSettings($organization, 'Officer album created', 'Added the '.$data['year'].' officer album.');

        return redirect()->route('org.dashboard', ['section' => 'officers', 'year' => $data['year']])
            ->with('success', 'The '.$data['year'].' album was added.');
    }

    public function storeOfficer(Request $request)
    {
        $organization = $this->currentOrganization();
        $data = $request->validate([
            'album_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $album = OfficerAlbum::where('organization_id', $organization->id)->findOrFail($data['album_id']);
        $path = $request->file('photo')->store('officers/'.$organization->id.'/'.$album->id, 'public');
        $officer = $album->officers()->create([
            'name' => $data['name'],
            'position' => $data['position'],
            'photo_path' => $path,
        ]);
        $this->auditSettings($organization, 'Officer added', 'Added '.$officer->name.' to the '.$album->year.' album.');

        return redirect()->route('org.dashboard', ['section' => 'officers', 'year' => $album->year])
            ->with('success', $officer->name.' was added to '.$album->year.'.');
    }

    public function destroyOfficer(Officer $officer)
    {
        $organization = $this->currentOrganization();
        $album = $officer->album;
        abort_unless($album && (int) $album->organization_id === (int) $organization->id, 403);

        Storage::disk('public')->delete($officer->photo_path);
        $name = $officer->name;
        $year = $album->year;
        $officer->delete();
        $this->auditSettings($organization, 'Officer removed', 'Removed '.$name.' from the '.$year.' album.');

        return redirect()->route('org.dashboard', ['section' => 'officers', 'year' => $year])
            ->with('success', $name.' was removed from '.$year.'.');
    }

    public function storeEventPhotos(Request $request)
    {
        $organization = $this->currentOrganization();
        $data = $request->validate([
            'event_id' => ['required', 'integer'],
            'photos' => ['required', 'array', 'min:1', 'max:12'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $event = Event::where('organization_id', $organization->id)->findOrFail($data['event_id']);
        foreach ($request->file('photos', []) as $photo) {
            $event->photos()->create([
                'photo_path' => $photo->store('events/'.$organization->id.'/'.$event->id, 'public'),
            ]);
        }
        $count = count($request->file('photos', []));
        $this->auditSettings($organization, 'Event photos added', 'Added '.$count.' photo'.($count === 1 ? '' : 's').' to "'.$event->title.'".');

        return redirect()->route('org.dashboard', ['section' => 'gallery', 'event' => $event->id])
            ->with('success', $count.' photo'.($count === 1 ? '' : 's').' added to '.$event->title.'.');
    }

    public function destroyEventPhoto(EventPhoto $photo)
    {
        $organization = $this->currentOrganization();
        $event = $photo->event;
        abort_unless($event && (int) $event->organization_id === (int) $organization->id, 403);

        Storage::disk('public')->delete($photo->photo_path);
        $title = $event->title;
        $photo->delete();
        $this->auditSettings($organization, 'Event photo removed', 'Removed a photo from "'.$title.'".');

        return redirect()->route('org.dashboard', ['section' => 'gallery', 'event' => $event->id])
            ->with('success', 'Photo removed from '.$title.'.');
    }

    private function currentOrganization(): User
    {
        /** @var User $organization */
        $organization = Auth::user();
        abort_unless($organization && $organization->role === 'organization', 403);

        return $organization;
    }

    private function auditSettings(User $organization, string $action, string $description): void
    {
        AuditTrail::record([
            'action' => $action,
            'action_type' => 'updated',
            'module' => 'Organizations',
            'description' => $description,
            'subject' => $organization,
            'subject_label' => $organization->name,
        ]);
    }

    /**
     * Send a portal notice to this organization's volunteers.
     */
    public function sendNotification(Request $request)
    {
        $data = $request->validate([
            'notice_type' => ['required', 'in:event,task,reminder'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $organization = Auth::user();
        $result = VolunteerNotice::broadcast($organization, $data['notice_type'], $data['title'], $data['message']);

        if ($result['recipients'] === 0) {
            $error = $data['notice_type'] === 'task'
                ? 'No volunteer has an open assigned task yet.'
                : 'There are no approved volunteers to notify yet.';

            return redirect()->route('org.dashboard', ['section' => 'notifications'])->with('error', $error);
        }

        AuditTrail::record([
            'action' => 'Volunteer notice sent',
            'action_type' => 'other',
            'module' => 'Volunteers',
            'description' => 'Sent "'.$data['title'].'" to '.$result['recipients'].' volunteer'.($result['recipients'] === 1 ? '' : 's').'.',
            'subject_label' => $data['title'],
            'new' => ['recipients' => $result['recipients'], 'emails' => $result['emails']],
        ]);

        $success = 'Notification posted for '.$result['recipients'].' volunteer'.($result['recipients'] === 1 ? '' : 's').' on the platform';
        if ($result['emails'] > 0) {
            $success .= ' and emailed to '.implode(', ', $result['addresses']).'.';
        } elseif ($result['failures'] !== []) {
            $success .= '. Email was not accepted: '.reset($result['failures']);
        } else {
            $success .= '. No registered email could be reached.';
        }

        return redirect()->route('org.dashboard', ['section' => 'notifications'])->with('success', $success);
    }

    /**
     * Store the Gmail mailbox that sends volunteer notices, then mail a test to that inbox.
     */
    public function saveMailSettings(Request $request)
    {
        $data = $request->validate([
            'mail_username' => ['required', 'email', 'max:255'],
            'mail_password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        $address = $data['mail_username'];
        $password = preg_replace('/\s+/', '', $data['mail_password']) ?? '';
        VolunteerNotice::connectGmail($address, $password);

        try {
            Mail::mailer('smtp')->raw(
                'VolunteerHub is connected to this Gmail account. Volunteer notices will now be delivered from here.',
                function ($message) use ($address) {
                    $message->to($address)
                        ->from($address, 'VolunteerHub')
                        ->subject('VolunteerHub email is connected');
                }
            );
        } catch (\Throwable $exception) {
            $rejected = str_contains($exception->getMessage(), '535')
                || str_contains($exception->getMessage(), 'BadCredentials');

            return redirect()->route('org.dashboard', ['section' => 'notifications'])
                ->with('error', $rejected
                    ? 'Google did not accept that app password. Create a new 16-character app password and paste it here.'
                    : 'Google did not accept the sending account. Check the Gmail address and app password, then try again.');
        }

        return redirect()->route('org.dashboard', ['section' => 'notifications'])
            ->with('success', 'A test email was sent to '.$address.'. Check that inbox, including spam. Volunteer notices will now be emailed from this account.');
    }

    public function messagePresence()
    {
        Cache::put('org-online-'.Auth::id(), true, now()->addMinutes(3));

        return response()->json(['online' => true]);
    }

    public function replyMessage(Request $request, User $volunteer)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);
        abort_unless($this->volunteerBelongsToOrganization($volunteer, Auth::id()), 403);

        OrgConversation::updateOrCreate(
            ['organization_id' => Auth::id(), 'volunteer_id' => $volunteer->id],
            [
                'mode' => 'agent',
                'needs_takeover' => false,
                'taken_over_at' => now(),
                'last_message_at' => now(),
            ]
        );

        $pending = ChatbotResponse::where('organization_id', Auth::id())
            ->where('user_id', $volunteer->id)
            ->whereNull('admin_reply')
            ->where('message', '!=', '')
            ->latest('id')
            ->first();

        if ($pending) {
            $pending->update([
                'admin_reply' => $data['message'],
                'admin_replied_by' => Auth::id(),
                'admin_replied_at' => now(),
                'needs_takeover' => false,
            ]);
        } else {
            ChatbotResponse::create([
                'user_id' => $volunteer->id,
                'organization_id' => Auth::id(),
                'message' => '',
                'response' => '',
                'admin_reply' => $data['message'],
                'admin_replied_by' => Auth::id(),
                'admin_replied_at' => now(),
                'answered_by' => 'organization',
                'needs_takeover' => false,
                'intent' => 'agent',
                'confidence' => 1,
            ]);
        }

        ChatbotResponse::where('organization_id', Auth::id())
            ->where('user_id', $volunteer->id)
            ->update(['needs_takeover' => false]);

        PortalNotifier::send(
            (int) $volunteer->id,
            Auth::user()->name.' replied',
            'Open the assistant to read the reply.',
            Auth::user()->name.' replied: '.$data['message'],
            'fa-comment',
            route('volunteer.dashboard')
        );

        return redirect()->route('org.dashboard', ['section' => 'messages', 'volunteer' => $volunteer->id])
            ->with('success', 'Reply sent to '.$volunteer->name.'.');
    }

    public function takeOverMessage(User $volunteer)
    {
        abort_unless($this->volunteerBelongsToOrganization($volunteer, Auth::id()), 403);

        OrgConversation::updateOrCreate(
            ['organization_id' => Auth::id(), 'volunteer_id' => $volunteer->id],
            [
                'mode' => 'agent',
                'needs_takeover' => false,
                'taken_over_at' => now(),
                'last_message_at' => now(),
            ]
        );

        ChatbotResponse::where('organization_id', Auth::id())
            ->where('user_id', $volunteer->id)
            ->update(['needs_takeover' => false]);

        return redirect()->route('org.dashboard', ['section' => 'messages', 'volunteer' => $volunteer->id])
            ->with('success', 'You took over the conversation with '.$volunteer->name.'.');
    }

    private function organizationMessageThreads(int $orgId)
    {
        $ids = OrgConversation::where('organization_id', $orgId)->pluck('volunteer_id')
            ->merge(ChatbotResponse::where('organization_id', $orgId)->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $volunteers = User::whereIn('id', $ids)->orderBy('name')->get();
        $conversations = OrgConversation::where('organization_id', $orgId)->whereIn('volunteer_id', $ids)->get()->keyBy('volunteer_id');
        $latest = ChatbotResponse::where('organization_id', $orgId)
            ->whereIn('user_id', $ids)
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        return $volunteers->map(function (User $volunteer) use ($conversations, $latest) {
            $last = $latest->get($volunteer->id);
            $conversation = $conversations->get($volunteer->id);
            $preview = $last?->admin_reply
                ?: ($last?->needs_takeover ? 'Needs a reply from you' : ($last?->message ?: $last?->response));
            $volunteer->setAttribute('message_preview', $preview ?: 'No messages yet');
            $volunteer->setAttribute('message_at', $last?->created_at ?? $conversation?->last_message_at);
            $volunteer->setAttribute('needs_takeover', (bool) $conversation?->needs_takeover);
            $volunteer->setAttribute('with_agent', $conversation?->mode === 'agent');

            return $volunteer;
        })->sortByDesc(fn (User $volunteer) => $volunteer->message_at?->timestamp ?? 0)->values();
    }

    private function volunteerBelongsToOrganization(User $volunteer, int $orgId): bool
    {
        if ($volunteer->role !== 'volunteer') {
            return false;
        }

        if ((int) $volunteer->organization_id === $orgId) {
            return true;
        }

        $eventIds = Event::where('organization_id', $orgId)->pluck('id');

        return Assignment::where('user_id', $volunteer->id)->whereIn('event_id', $eventIds)->exists()
            || TaskApplication::where('user_id', $volunteer->id)->whereIn('event_id', $eventIds)->exists()
            || OrgConversation::where('organization_id', $orgId)->where('volunteer_id', $volunteer->id)->exists();
    }

    /**
     * Launch a new event. Roles are added later from Task Assignment.
     */
    public function storeEvent(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        $event = Event::create([
            'organization_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'start_time' => Carbon::parse($request->start_time),
            'end_time' => Carbon::parse($request->end_time),
            'capacity' => $request->capacity,
            'status' => 'draft',
        ]);

        AuditTrail::record([
            'action' => 'Event created',
            'action_type' => 'created',
            'module' => 'Events',
            'description' => Auth::user()->name.' submitted "'.$event->title.'" for review.',
            'subject' => $event,
            'subject_label' => $event->title,
            'new' => [
                'status' => 'draft',
                'location' => $event->location,
                'start' => $event->start_time?->toDateTimeString(),
                'end' => $event->end_time?->toDateTimeString(),
            ],
        ]);

        PortalNotifier::admins(
            'Event awaiting review',
            Auth::user()->name . ' submitted "' . $event->title . '" for approval.',
            Auth::user()->name . ' posted "' . $event->title . '" at ' . $event->location . ' on ' . $event->start_time->format('M d, Y g:i A') . '. Volunteers cannot see it until an administrator approves it.',
            'fa-calendar-check',
            route('admin.dashboard', ['tab' => 'events', 'org' => Auth::id()])
        );

        return redirect()->route('org.dashboard', ['section' => 'events'])
            ->with('success', "Event '{$event->title}' was submitted. An admin will review it before volunteers can see it.");
    }

    public function updateEvent(Request $request, Event $event)
    {
        abort_unless((int) $event->organization_id === Auth::id(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,published,completed,cancelled'],
        ]);

        $before = [
            'title' => $event->title,
            'location' => $event->location,
            'status' => $event->status,
            'start' => $event->start_time?->toDateTimeString(),
            'end' => $event->end_time?->toDateTimeString(),
            'capacity' => $event->capacity,
        ];

        $status = $data['status'];
        if ($event->status === 'draft' && $status !== 'cancelled') {
            $status = 'draft';
        }
        if ($event->status !== 'draft' && $status === 'draft') {
            $status = $event->status;
        }

        $event->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'],
            'start_time' => Carbon::parse($data['start_time']),
            'end_time' => Carbon::parse($data['end_time']),
            'capacity' => $data['capacity'] ?? null,
            'status' => $status,
        ]);

        $event->refresh();
        $after = [
            'title' => $event->title,
            'location' => $event->location,
            'status' => $event->status,
            'start' => $event->start_time?->toDateTimeString(),
            'end' => $event->end_time?->toDateTimeString(),
            'capacity' => $event->capacity,
        ];
        $cancelled = $before['status'] !== 'cancelled' && $event->status === 'cancelled';

        AuditTrail::record([
            'action' => $cancelled ? 'Event cancelled' : 'Event updated',
            'action_type' => 'updated',
            'module' => 'Events',
            'description' => ($cancelled ? 'Cancelled' : 'Updated').' "'.$event->title.'".',
            'subject' => $event,
            'subject_label' => $event->title,
            'previous' => $before,
            'new' => $after,
        ]);

        if ($cancelled) {
            OrgNotifier::send(
                (int) Auth::id(),
                'event',
                'Event cancellation confirmed',
                '"'.$event->title.'" is cancelled.',
                $event->title,
                route('org.dashboard', ['section' => 'events']),
                'View event'
            );
        } elseif ($before['start'] !== $after['start'] || $before['end'] !== $after['end'] || $before['status'] !== $after['status']) {
            OrgNotifier::send(
                (int) Auth::id(),
                'event',
                'Event status updated',
                '"'.$event->title.'" is now '.$event->oversightLabel().'. It runs '.$event->start_time->format('M d, Y g:i A').' to '.$event->end_time->format('M d, Y g:i A').'.',
                $event->title,
                route('org.dashboard', ['section' => 'events']),
                'View event'
            );
        }

        return redirect()->route('org.dashboard', ['section' => 'events'])
            ->with('success', "Event '{$event->title}' has been updated.");
    }

    public function destroyEvent(Event $event)
    {
        abort_unless((int) $event->organization_id === Auth::id(), 403);

        $title = $event->title;
        $previousStatus = $event->status;
        $event->tasks()->delete();
        $event->delete();

        AuditTrail::record([
            'action' => 'Event deleted',
            'action_type' => 'deleted',
            'module' => 'Events',
            'description' => 'Removed "'.$title.'" from the planner.',
            'subject_label' => $title,
            'previous' => ['status' => $previousStatus],
        ]);

        return redirect()->route('org.dashboard', ['section' => 'events'])
            ->with('success', "Event '{$title}' has been removed.");
    }

    public function updateTask(Request $request, Task $task)
    {
        abort_unless($task->event && (int) $task->event->organization_id === Auth::id(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:pending,assigned,in_progress,completed,cancelled'],
            'due_date' => ['nullable', 'date'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ]);

        $before = [
            'title' => $task->title,
            'priority' => $task->priority,
            'status' => $task->status,
            'due_date' => $task->due_date?->toDateTimeString(),
        ];

        $task->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'status' => $data['status'],
            'due_date' => isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
        ]);
        $task->skills()->sync($data['skill_ids'] ?? []);

        AuditTrail::record([
            'action' => 'Task updated',
            'action_type' => 'updated',
            'module' => 'Tasks',
            'description' => 'Updated "'.$task->title.'".',
            'subject' => $task,
            'subject_label' => $task->title,
            'previous' => $before,
            'new' => [
                'title' => $task->title,
                'priority' => $task->priority,
                'status' => $task->status,
                'due_date' => $task->due_date?->toDateTimeString(),
            ],
        ]);

        return redirect()->route('org.dashboard', ['task_id' => $task->id])
            ->with('success', "Task '{$task->title}' has been updated.");
    }

    public function destroyTask(Task $task)
    {
        abort_unless($task->event && (int) $task->event->organization_id === Auth::id(), 403);

        $title = $task->title;
        $eventTitle = $task->event->title ?? 'Event';
        $task->delete();

        AuditTrail::record([
            'action' => 'Task deleted',
            'action_type' => 'deleted',
            'module' => 'Tasks',
            'description' => 'Removed "'.$title.'" from "'.$eventTitle.'".',
            'subject_label' => $title,
            'previous' => ['event' => $eventTitle],
        ]);

        return redirect()->route('org.dashboard', ['section' => 'tasks'])
            ->with('success', "Task '{$title}' has been removed.");
    }

    private function notifyIfEventIsFull(?Event $event): void
    {
        if (!$event || !$event->capacity) {
            return;
        }

        $filled = Assignment::where('event_id', $event->id)
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->distinct('user_id')
            ->count('user_id');

        if ($filled < (int) $event->capacity) {
            return;
        }

        OrgNotifier::send(
            (int) $event->organization_id,
            'event',
            'Event reached volunteer capacity',
            '"'.$event->title.'" has '.$filled.' of '.$event->capacity.' volunteer places filled.',
            $event->title,
            route('org.dashboard', ['section' => 'events']),
            'View event',
            true,
            'event-full-'.$event->id.'-'.$event->capacity
        );
    }

    /**
     * Assign or reassign a volunteer to a task owned by the organization.
     */
    public function assignVolunteer(Request $request)
    {
        $data = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $task = Task::with(['event', 'skills'])->findOrFail($data['task_id']);
        abort_unless($task->event && (int) $task->event->organization_id === Auth::id(), 403);

        $volunteer = User::where('id', $data['user_id'])
            ->where('role', 'volunteer')
            ->where('status', 'approved')
            ->where('organization_id', Auth::id())
            ->with('skills')
            ->firstOrFail();

        $existing = Assignment::with('user')
            ->where('event_id', $task->event_id)
            ->where('task_id', $task->id)
            ->first();
        $previousVolunteer = $existing?->user?->name;
        $reassigned = $existing && (int) $existing->user_id !== (int) $volunteer->id;

        if (!$reassigned && $task->matchedSkillsFor($volunteer)->isEmpty()) {
            return back()->withErrors([
                'user_id' => $volunteer->name.' does not have a skill that matches '.$task->title.'.',
            ]);
        }

        $assignment = Assignment::updateOrCreate(
            [
                'event_id' => $task->event_id,
                'task_id' => $task->id,
            ],
            [
                'user_id' => $volunteer->id,
                'status' => 'pending',
                'hours_logged' => 0,
                'feedback' => null,
                'completion_note' => null,
                'completion_proof_path' => null,
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]
        );

        if (!in_array($task->status, ['completed', 'cancelled'], true)) {
            $task->status = 'assigned';
            $task->save();
        }

        VolunteerNotice::notifyAssignment(Auth::user(), $volunteer, $task, $reassigned ? $existing?->user : null);

        AuditTrail::record([
            'action' => $reassigned ? 'Task reassigned' : 'Task assigned',
            'action_type' => $reassigned ? 'updated' : 'other',
            'module' => 'Tasks',
            'description' => ($reassigned ? 'Reassigned' : 'Assigned').' "'.$task->title.'" to '.$volunteer->name.'.',
            'subject' => $assignment,
            'subject_label' => $task->title,
            'previous' => $previousVolunteer ? ['volunteer' => $previousVolunteer] : null,
            'new' => ['volunteer' => $volunteer->name, 'event' => $task->event->title ?? 'Event', 'status' => 'assigned'],
        ]);

        $eventTitle = $task->event->title ?? 'Event';
        if ($reassigned) {
            OrgNotifier::send(
                (int) Auth::id(),
                'task',
                'Task reassigned',
                '"'.$task->title.'" was reassigned from '.$previousVolunteer.' to '.$volunteer->name.'.',
                $eventTitle,
                route('org.dashboard', ['section' => 'tasks']),
                'View task',
                true
            );
        } else {
            OrgNotifier::send(
                (int) Auth::id(),
                'task',
                'Waiting for the volunteer',
                $volunteer->name.' can accept or decline "'.$task->title.'".',
                $eventTitle,
                route('org.dashboard', ['section' => 'tasks']),
                'View task'
            );
        }
        $this->notifyIfEventIsFull($task->event);

        return redirect()->route('org.dashboard', ['section' => 'tasks'])
            ->with('success', $reassigned
                ? "{$volunteer->name} has been reassigned to '{$task->title}'."
                : "{$volunteer->name} has been assigned to '{$task->title}'.");
    }

    /**
     * Send a task recruitment notice to approved volunteers.
     */
    public function sendTaskOutreach(Task $task)
    {
        $task->load(['event.organization', 'skills', 'assignments']);
        abort_unless($task->event && (int) $task->event->organization_id === Auth::id(), 403);

        if ($task->event->status !== 'published' || in_array($task->status, ['completed', 'cancelled'], true)) {
            AuditTrail::record([
                'action' => 'Task invite denied',
                'action_type' => 'other',
                'module' => 'Tasks',
                'description' => 'Did not invite volunteers to "'.$task->title.'" because the task is not open.',
                'status' => 'failed',
                'subject' => $task,
                'subject_label' => $task->title,
            ]);

            return redirect()->route('org.dashboard', ['task_id' => $task->id])
                ->with('error', 'Only active, open tasks can be sent to volunteers.');
        }

        if ($task->assignments->whereIn('status', ['pending', 'approved', 'submitted', 'completed'])->isNotEmpty()) {
            AuditTrail::record([
                'action' => 'Task invite denied',
                'action_type' => 'other',
                'module' => 'Tasks',
                'description' => 'Did not invite volunteers to "'.$task->title.'" because a volunteer is already assigned.',
                'status' => 'failed',
                'subject' => $task,
                'subject_label' => $task->title,
            ]);

            return redirect()->route('org.dashboard', ['task_id' => $task->id])
                ->with('error', 'This task already has an assigned volunteer. Create or select another open task before sending an invite.');
        }

        $requiredSkillIds = $task->skills->pluck('id');

        $volunteersQuery = User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->whereNotNull('email')
            ->with('skills');

        if ($requiredSkillIds->isNotEmpty()) {
            $volunteersQuery->whereHas('skills', function ($query) use ($requiredSkillIds) {
                $query->whereIn('skills.id', $requiredSkillIds);
            });
        }

        $volunteers = $volunteersQuery->orderBy('name')->get();

        if ($volunteers->isEmpty()) {
            AuditTrail::record([
                'action' => 'Task invite denied',
                'action_type' => 'other',
                'module' => 'Tasks',
                'description' => 'No approved volunteers matched "'.$task->title.'".',
                'status' => 'failed',
                'subject' => $task,
                'subject_label' => $task->title,
            ]);

            return redirect()->route('org.dashboard', ['task_id' => $task->id])
                ->with('error', 'No approved volunteers with email addresses matched this task yet.');
        }

        $sentCount = 0;
        $failedRecipients = [];
        $now = Carbon::now();

        foreach ($volunteers as $volunteer) {
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $volunteer->id,
                'data' => json_encode([
                    'title' => 'Volunteer Needed: ' . $task->title,
                    'message' => ($task->event->organization->name ?? 'A partner organization') . ' is inviting volunteers for "' . $task->event->title . '". Open your dashboard to apply.',
                    'icon' => 'fa-hand-holding-heart',
                ]),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            try {
                Mail::to($volunteer->email)->send(new TaskVolunteerInviteMail($task, $volunteer));
                $sentCount++;
            } catch (\Throwable $exception) {
                $failedRecipients[] = [
                    'user_id' => $volunteer->id,
                    'email' => $volunteer->email,
                    'error' => 'Email delivery failed. Check mail configuration or recipient address.',
                ];
            }
        }

        RecordArchive::create([
            'table_name' => 'task_outreach',
            'record_id' => $task->id,
            'original_data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'event_id' => $task->event_id,
                'event_title' => $task->event->title,
                'recipient_count' => $volunteers->count(),
                'email_sent_count' => $sentCount,
                'email_failed_count' => count($failedRecipients),
                'portal_notification_count' => $volunteers->count(),
                'matched_by_skills' => $requiredSkillIds->isNotEmpty(),
                'failed_recipients' => $failedRecipients,
                'sent_at' => $now->toDateTimeString(),
            ],
            'archived_by' => Auth::id(),
            'reason' => 'Task volunteer recruitment notice sent by organization.',
        ]);

        AuditTrail::record([
            'action' => 'Task invite sent',
            'action_type' => 'other',
            'module' => 'Tasks',
            'description' => 'Invited '.$volunteers->count().' volunteer(s) to "'.$task->title.'".',
            'status' => count($failedRecipients) === $volunteers->count() && $sentCount === 0 ? 'success' : 'success',
            'subject' => $task,
            'subject_label' => $task->title,
            'new' => [
                'recipients' => $volunteers->count(),
                'emails_sent' => $sentCount,
                'emails_failed' => count($failedRecipients),
            ],
        ]);

        $message = "Task invite sent to {$volunteers->count()} approved volunteer(s). Portal notifications were delivered.";
        $message .= $sentCount > 0 ? " Email sent to {$sentCount}." : ' No emails were sent; check Gmail SMTP settings.';
        if (count($failedRecipients) > 0) {
            $message .= ' Some email deliveries failed and were recorded for review.';
        }

        return redirect()->route('org.dashboard', ['task_id' => $task->id])
            ->with(count($failedRecipients) === $volunteers->count() ? 'error' : 'success', $message);
    }

    /**
     * Approve a volunteer task application and create the assignment.
     */
    public function approveApplication(TaskApplication $application)
    {
        $application->load(['task.event', 'user']);
        abort_unless($application->task && $application->task->event && (int) $application->task->event->organization_id === Auth::id(), 403);
        abort_unless($application->status === 'pending', 422);

        $assignment = Assignment::updateOrCreate(
            [
                'event_id' => $application->event_id,
                'task_id' => $application->task_id,
            ],
            [
                'user_id' => $application->user_id,
                'status' => 'approved',
                'hours_logged' => 0,
                'feedback' => null,
                'completion_note' => null,
                'completion_proof_path' => null,
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]
        );

        $application->status = 'approved';
        $application->reviewed_at = Carbon::now();
        $application->reviewed_by = Auth::id();
        $application->save();

        TaskApplication::where('task_id', $application->task_id)
            ->where('id', '!=', $application->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'feedback' => 'Another volunteer was assigned to this task.',
                'reviewed_at' => Carbon::now(),
                'reviewed_by' => Auth::id(),
            ]);

        if (!in_array($application->task->status, ['completed', 'cancelled'], true)) {
            $application->task->status = 'assigned';
            $application->task->save();
        }

        VolunteerNotice::notifyAssignment(Auth::user(), $application->user, $application->task);

        AuditTrail::record([
            'action' => 'Task application approved',
            'action_type' => 'approved',
            'module' => 'Tasks',
            'description' => 'Approved '.$application->user->name.' for "'.$application->task->title.'".',
            'subject' => $application->task,
            'subject_label' => $application->task->title,
            'previous' => ['application' => 'pending'],
            'new' => ['volunteer' => $application->user->name, 'task_status' => 'assigned'],
        ]);

        $eventTitle = $application->event->title ?? ($application->task->event->title ?? 'Event');
        OrgNotifier::send(
            (int) Auth::id(),
            'volunteer',
            'Volunteer application accepted',
            $application->user->name.' was accepted for "'.$application->task->title.'".',
            $application->user->name,
            route('org.dashboard', ['section' => 'tasks']),
            'View task',
            true
        );
        OrgNotifier::send(
            (int) Auth::id(),
            'task',
            'Volunteer accepted a task assignment',
            $application->user->name.' is now assigned to "'.$application->task->title.'" on "'.$eventTitle.'".',
            $eventTitle,
            route('org.dashboard', ['section' => 'tasks']),
            'View task'
        );
        $this->notifyIfEventIsFull($application->event ?? $application->task->event);

        return redirect()->route('org.dashboard')
            ->with('success', "{$application->user->name} has been assigned to '{$application->task->title}'.");
    }

    /**
     * Reject a volunteer task application with feedback.
     */
    public function rejectApplication(Request $request, TaskApplication $application)
    {
        $application->load(['task.event', 'user']);
        abort_unless($application->task && $application->task->event && (int) $application->task->event->organization_id === Auth::id(), 403);
        abort_unless($application->status === 'pending', 422);

        $data = $request->validate([
            'feedback' => ['required', 'string', 'max:1000'],
        ]);

        $application->status = 'rejected';
        $application->feedback = $data['feedback'];
        $application->reviewed_at = Carbon::now();
        $application->reviewed_by = Auth::id();
        $application->save();

        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $application->user_id,
            'data' => json_encode([
                'title' => 'Task Application Update',
                'message' => 'Your application for "' . ($application->task->title ?? 'an open task') . '" was not selected: ' . $data['feedback'],
                'icon' => 'fa-circle-info',
            ]),
            'read_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        AuditTrail::record([
            'action' => 'Task application rejected',
            'action_type' => 'rejected',
            'module' => 'Tasks',
            'description' => 'Did not select '.$application->user->name.' for "'.($application->task->title ?? 'a task').'".',
            'subject' => $application->task,
            'subject_label' => $application->task->title ?? 'Task',
            'previous' => ['application' => 'pending'],
            'new' => ['application' => 'rejected', 'volunteer' => $application->user->name],
        ]);

        OrgNotifier::send(
            (int) Auth::id(),
            'volunteer',
            'Volunteer application rejected',
            $application->user->name.' was not selected for "'.($application->task->title ?? 'a task').'".',
            $application->user->name,
            route('org.dashboard', ['section' => 'tasks']),
            'View task'
        );

        return redirect()->route('org.dashboard')
            ->with('success', 'Application rejected with feedback.');
    }

    /**
     * Upload a compliance document for the organization.
     */
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document_file' => ['required', 'file', 'mimes:pdf,docx,xlsx', 'max:10240'], // 10MB limit
        ]);

        $file = $request->file('document_file');
        $filename = $file->getClientOriginalName();
        $sizeBytes = $file->getSize();

        // Convert size to human readable
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $sizeBytes > 0 ? floor(log($sizeBytes, 1024)) : 0;
        $sizeStr = number_format($sizeBytes / pow(1024, $power), 2) . ' ' . $units[$power];

        $path = $file->store('compliance/' . Auth::id(), 'public');
        $checksum = hash_file('sha256', $file->getRealPath());

        RecordArchive::create([
            'table_name' => 'compliance_documents',
            'record_id' => time(),
            'original_data' => [
                'filename' => $filename,
                'size' => $sizeStr,
                'uploaded_at' => Carbon::now()->toDateTimeString(),
                'file_path' => $path,
                'checksum' => $checksum,
            ],
            'archived_by' => Auth::id(),
            'reason' => 'Compliance document uploaded by organization.'
        ]);

        AuditTrail::record([
            'action' => 'Compliance document uploaded',
            'action_type' => 'created',
            'module' => 'Organizations',
            'description' => Auth::user()->name.' uploaded "'.$filename.'".',
            'subject_label' => $filename,
            'new' => ['filename' => $filename, 'size' => $sizeStr],
        ]);

        PortalNotifier::admins(
            'Compliance document uploaded',
            Auth::user()->name . ' uploaded ' . $filename . '.',
            Auth::user()->name . ' uploaded "' . $filename . '" (' . $sizeStr . ') for compliance review. The file stays in the organization archive.',
            'fa-file-shield',
            route('admin.dashboard', ['tab' => 'organizations'])
        );

        $destination = $request->input('return') === 'settings'
            ? route('org.dashboard', ['section' => 'settings', 'pane' => 'documents'])
            : route('org.dashboard');

        return redirect($destination)
            ->with('success', "Document '{$filename}' uploaded and archived successfully.");
    }

    /**
     * Download an uploaded compliance document owned by the organization.
     */
    public function downloadDocument(RecordArchive $document)
    {
        abort_unless(
            $document->table_name === 'compliance_documents' && (int) $document->archived_by === Auth::id(),
            403
        );

        $path = $document->original_data['file_path'] ?? null;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $filename = $document->original_data['filename'] ?? basename($path);
        AuditTrail::record([
            'action' => 'Compliance document downloaded',
            'action_type' => 'other',
            'module' => 'Organizations',
            'description' => 'Downloaded "'.$filename.'".',
            'subject' => $document,
            'subject_label' => $filename,
        ]);

        return Storage::disk('public')->download($path, $filename);
    }

    /**
     * Download submitted completion proof for assignments owned by the organization.
     */
    public function downloadCompletionProof(Assignment $assignment)
    {
        abort_unless($assignment->event && (int) $assignment->event->organization_id === Auth::id(), 403);

        $path = $assignment->completion_proof_path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path);
    }

    /**
     * Approve a volunteer completion submission and issue a certificate.
     */
    public function approveCompletion(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->event && (int) $assignment->event->organization_id === Auth::id(), 403);
        abort_unless($assignment->status === 'submitted', 422);

        $data = $request->validate([
            'hours_logged' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignment->status = 'completed';
        $assignment->hours_logged = $data['hours_logged'];
        $assignment->feedback = $data['feedback'] ?? null;
        $assignment->reviewed_at = Carbon::now();
        $assignment->reviewed_by = Auth::id();
        $assignment->save();

        if ($assignment->task) {
            $assignment->task->status = 'completed';
            $assignment->task->save();
        }

        $organization = $assignment->event->organization;
        $taskReward = $organization->task_reward ?? 'service_hours';
        $eventReward = $organization->event_reward ?? 'service_certificate';
        $now = Carbon::now();

        VolunteerReward::firstOrCreate(
            [
                'user_id' => $assignment->user_id,
                'task_id' => $assignment->task_id,
                'reward_scope' => 'task',
            ],
            [
                'organization_id' => $organization->id,
                'event_id' => $assignment->event_id,
                'assignment_id' => $assignment->id,
                'reward_type' => $taskReward,
                'issued_at' => $now,
            ]
        );

        $stillWorking = Assignment::where('user_id', $assignment->user_id)
            ->where('event_id', $assignment->event_id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        $eventReady = false;
        if (!$stillWorking) {
            VolunteerReward::firstOrCreate(
                [
                    'user_id' => $assignment->user_id,
                    'event_id' => $assignment->event_id,
                    'reward_scope' => 'event',
                ],
                [
                    'organization_id' => $organization->id,
                    'assignment_id' => $assignment->id,
                    'reward_type' => $eventReward,
                    'issued_at' => $now,
                ]
            );
            $eventReady = true;
        }

        $taskRewardLabel = User::TASK_REWARDS[$taskReward] ?? 'Task reward';
        $message = 'Your completion for "' . ($assignment->task->title ?? 'assigned task') . '" was approved. Task reward: ' . $taskRewardLabel . '.';
        if ($eventReady) {
            $message .= ' Every task on "' . ($assignment->event->title ?? 'this event') . '" is finished. ' . ($organization->name ?? 'Your organization') . ' can now render your certificate.';
        }

        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $assignment->user_id,
            'data' => json_encode([
                'title' => 'Task Completion Approved',
                'message' => $message,
                'icon' => 'fa-award',
            ]),
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $assignment->load(['user', 'task', 'event']);
        AuditTrail::record([
            'action' => 'Task completion approved',
            'action_type' => 'approved',
            'module' => 'Tasks',
            'description' => 'Approved completion of "'.($assignment->task->title ?? 'a task').'" for '.($assignment->user->name ?? 'a volunteer').'.',
            'subject' => $assignment->task,
            'subject_label' => $assignment->task->title ?? 'Task',
            'previous' => ['assignment' => 'submitted'],
            'new' => [
                'assignment' => 'completed',
                'hours' => $assignment->hours_logged,
                'volunteer' => $assignment->user->name ?? 'Volunteer',
            ],
        ]);

        OrgNotifier::send(
            (int) Auth::id(),
            'task',
            'Task marked as completed',
            ($assignment->user->name ?? 'A volunteer').' finished "'.($assignment->task->title ?? 'a task').'" on "'.($assignment->event->title ?? 'an event').'".',
            $assignment->event->title ?? 'Event',
            route('org.dashboard', ['section' => 'tasks']),
            'View task'
        );
        if ($eventReady) {
            OrgNotifier::send(
                (int) Auth::id(),
                'certificate',
                'Certificate ready for review',
                ($assignment->user->name ?? 'A volunteer').' finished every task on "'.($assignment->event->title ?? 'an event').'". The certificate can be rendered.',
                $assignment->event->title ?? 'Event',
                route('org.dashboard', ['section' => 'certificates']),
                'Open certificates'
            );
        }

        return redirect()->route('org.dashboard', ['section' => 'certificates'])
            ->with('success', $eventReady
                ? 'Task approved. Every task on this event is finished, so you can render the certificate.'
                : 'Task approved. The certificate waits until their other tasks on this event are finished.');
    }

    /**
     * Return a submitted task to the volunteer with feedback.
     */
    public function rejectCompletion(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->event && (int) $assignment->event->organization_id === Auth::id(), 403);
        abort_unless($assignment->status === 'submitted', 422);

        $data = $request->validate([
            'feedback' => ['required', 'string', 'max:1000'],
        ]);

        $assignment->status = 'rejected';
        $assignment->feedback = $data['feedback'];
        $assignment->reviewed_at = Carbon::now();
        $assignment->reviewed_by = Auth::id();
        $assignment->save();

        $assignment->load('task');
        if ($assignment->task && $assignment->task->status === 'in_progress') {
            $assignment->task->status = 'assigned';
            $assignment->task->save();
        }

        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $assignment->user_id,
            'data' => json_encode([
                'title' => 'Task Submission Needs Revision',
                'message' => 'Your submission for "' . ($assignment->task->title ?? 'assigned task') . '" needs revision: ' . $data['feedback'],
                'icon' => 'fa-circle-exclamation',
            ]),
            'read_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        AuditTrail::record([
            'action' => 'Task completion rejected',
            'action_type' => 'rejected',
            'module' => 'Tasks',
            'description' => 'Returned "'.($assignment->task->title ?? 'a task').'" for revision.',
            'subject' => $assignment->task,
            'subject_label' => $assignment->task->title ?? 'Task',
            'previous' => ['assignment' => 'submitted'],
            'new' => ['assignment' => 'rejected'],
        ]);

        return redirect()->route('org.dashboard', ['section' => 'certificates'])
            ->with('success', 'Submission returned to the volunteer for revision.');
    }

    /**
     * Render a certificate after the volunteer has finished every task on an event.
     */
    public function issueCertificate(Request $request)
    {
        $data = $request->validate([
            'volunteer_id' => ['required', 'integer'],
            'event_id' => ['required', 'integer'],
            'hours_rendered' => ['required', 'numeric', 'min:0.25', 'max:999'],
        ]);

        $event = Event::where('organization_id', Auth::id())->findOrFail($data['event_id']);
        $volunteer = User::where('role', 'volunteer')->findOrFail($data['volunteer_id']);
        $ready = $this->finishedEventAssignments($volunteer->id, $event->id);

        if ($ready->isEmpty()) {
            return redirect()->route('org.dashboard', ['section' => 'certificates'])
                ->with('error', 'A certificate is available after this volunteer finishes every task on the event.');
        }

        if (Certificate::where('user_id', $volunteer->id)->where('event_id', $event->id)->exists()) {
            return redirect()->route('org.dashboard', ['section' => 'certificates'])
                ->with('error', 'A certificate was already issued for this volunteer and event.');
        }

        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) Auth::user()->name), 0, 6));
        $certCode = 'VH-'.($prefix !== '' ? $prefix : 'ORG').'-'.strtoupper(Str::random(4)).'-'.time();
        $now = Carbon::now();

        $certificate = Certificate::create([
            'user_id' => $volunteer->id,
            'event_id' => $event->id,
            'certificate_code' => $certCode,
            'issued_at' => $now,
            'hours_rendered' => $data['hours_rendered'],
            'file_path' => 'certificates/'.$certCode.'.pdf',
        ]);

        PortalNotifier::send(
            $volunteer->id,
            'Certificate issued',
            Auth::user()->name.' issued your certificate for "'.$event->title.'".',
            Auth::user()->name.' issued a certificate for "'.$event->title.'" ('.$certCode.'). Open it from your dashboard.',
            'fa-certificate',
            route('volunteer.dashboard')
        );

        OrgNotifier::send(
            (int) Auth::id(),
            'certificate',
            'Certificate issued',
            'A certificate for "'.$event->title.'" was issued to '.$volunteer->name.' ('.$certCode.').',
            $event->title,
            route('org.dashboard', ['section' => 'certificates']),
            'View certificates',
            true,
            'certificate-issued-'.$certificate->id
        );

        AuditTrail::record([
            'action' => 'Certificate issued',
            'action_type' => 'created',
            'module' => 'Certificates',
            'description' => 'Issued a certificate to '.$volunteer->name.' for "'.$event->title.'".',
            'subject' => $certificate,
            'subject_label' => $event->title,
            'new' => [
                'volunteer' => $volunteer->name,
                'event' => $event->title,
                'hours' => $data['hours_rendered'],
                'code' => $certCode,
            ],
        ]);

        return redirect()->route('org.dashboard', ['section' => 'certificates'])
            ->with('success', 'Certificate issued for '.$volunteer->name.'.');
    }

    /**
     * Open a printable certificate owned by this organization.
     */
    public function downloadCertificate(Certificate $certificate)
    {
        $certificate->load(['user', 'event.organization']);
        abort_unless($certificate->event && (int) $certificate->event->organization_id === Auth::id(), 403);

        AuditTrail::record([
            'action' => 'Certificate opened',
            'action_type' => 'other',
            'module' => 'Certificates',
            'description' => 'Opened the certificate for '.($certificate->user->name ?? 'a volunteer').' on "'.($certificate->event->title ?? 'an event').'".',
            'subject' => $certificate,
            'subject_label' => $certificate->event->title ?? 'Certificate',
        ]);

        return view('volunteer.certificate', compact('certificate'));
    }

    /**
     * Issued certificates, plus volunteer and event pairs that are ready to receive one.
     */
    private function certificateLists(array $eventIds): array
    {
        if ($eventIds === []) {
            return [collect(), collect()];
        }

        $issuedCertificates = Certificate::whereIn('event_id', $eventIds)
            ->whereHas('user')
            ->with(['user', 'event'])
            ->orderByDesc('issued_at')
            ->get();

        $issuedKeys = $issuedCertificates->mapWithKeys(fn (Certificate $certificate) => [
            $certificate->user_id.'-'.$certificate->event_id => true,
        ]);

        $certificateChoices = Assignment::whereIn('event_id', $eventIds)
            ->with(['user', 'event', 'task'])
            ->get()
            ->groupBy(fn (Assignment $assignment) => $assignment->user_id.'-'.$assignment->event_id)
            ->filter(function ($group, $key) use ($issuedKeys) {
                if ($issuedKeys->has($key)) {
                    return false;
                }

                $first = $group->first();
                if (!$first?->user || $first->user->role !== 'volunteer' || !$first->event) {
                    return false;
                }

                $active = $group->where('status', '!=', 'cancelled');

                return $active->isNotEmpty() && $active->every(fn (Assignment $assignment) => $assignment->status === 'completed');
            })
            ->map(function ($group) {
                $completed = $group->where('status', 'completed');
                $first = $completed->first();
                $hours = round((float) $completed->sum('hours_logged'), 2);

                return [
                    'volunteer_id' => $first->user_id,
                    'volunteer_name' => $first->user->name,
                    'email' => $first->user->email,
                    'event_id' => $first->event_id,
                    'event_title' => $first->event->title,
                    'hours' => $hours > 0 ? $hours : null,
                    'tasks' => $completed->map(fn (Assignment $assignment) => $assignment->task->title ?? null)->filter()->unique()->values()->all(),
                ];
            })
            ->sortBy([
                ['volunteer_name', 'asc'],
                ['event_title', 'asc'],
            ])
            ->values();

        return [$issuedCertificates, $certificateChoices];
    }

    /**
     * Completed assignments for a volunteer who has no unfinished work left on the event.
     */
    private function finishedEventAssignments(int $volunteerId, int $eventId)
    {
        $assignments = Assignment::where('user_id', $volunteerId)
            ->where('event_id', $eventId)
            ->where('status', '!=', 'cancelled')
            ->get();

        if ($assignments->isEmpty() || $assignments->contains(fn (Assignment $assignment) => $assignment->status !== 'completed')) {
            return collect();
        }

        return $assignments;
    }

    public function reports(Request $request)
    {
        AuditTrail::report($request);

        return view('reports.show', [
            'report' => ActivityReport::build($request, 'organization', Auth::user()),
        ]);
    }

    public function exportReport(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'xls'], true), 404);

        $report = ActivityReport::build($request, 'organization', Auth::user());
        AuditTrail::report($request, $format);

        return $format === 'pdf' ? ReportFiles::pdf($report) : ReportFiles::excel($report);
    }
}
