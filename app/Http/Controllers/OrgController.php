<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\Task;
use App\Models\Skill;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\TaskApplication;
use App\Models\RecordArchive;
use App\Mail\TaskVolunteerInviteMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $organization = Auth::user();
        $orgId = $organization->id;
        $now = Carbon::now();

        $endedEventIds = Event::where('organization_id', $orgId)
            ->where('status', 'published')
            ->where('end_time', '<', $now)
            ->pluck('id');

        if ($endedEventIds->isNotEmpty()) {
            Event::whereIn('id', $endedEventIds)->update(['status' => 'completed']);
        }

        $eventsWithoutTasks = Event::where('organization_id', $orgId)
            ->where('status', 'published')
            ->where('end_time', '>=', $now)
            ->whereDoesntHave('tasks')
            ->get();

        foreach ($eventsWithoutTasks as $eventWithoutTask) {
            Task::create([
                'event_id' => $eventWithoutTask->id,
                'title' => 'Volunteer support for ' . $eventWithoutTask->title,
                'description' => 'General volunteer assistance for this community event.',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => $eventWithoutTask->start_time,
            ]);
        }

        $myEventsCount = Event::where('organization_id', $orgId)->count();

        // Count unique volunteers assigned to tasks/events of this organization
        $eventIds = Event::where('organization_id', $orgId)->pluck('id')->toArray();
        $assignedVolunteersCount = Assignment::whereIn('event_id', $eventIds)
            ->whereHas('user')
            ->distinct('user_id')
            ->count('user_id');

        $events = Event::where('organization_id', $orgId)
            ->with(['tasks.skills', 'assignments.user'])
            ->orderBy('created_at', 'desc')
            ->get();

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

        // Tasks list for the volunteer matching dropdown (only pending/in_progress tasks)
        $allOrgTasks = Task::whereIn('event_id', $eventIds)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereHas('event', function ($query) use ($now) {
                $query->where('end_time', '>=', $now)
                    ->where('status', 'published');
            })
            ->with('skills')
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

        return view('org.dashboard', compact(
            'organization',
            'myEventsCount',
            'assignedVolunteersCount',
            'events',
            'complianceDocuments',
            'completionReviews',
            'taskApplications',
            'allOrgTasks',
            'selectedTaskId',
            'selectedTask',
            'skillMatches',
            'skills'
        ));
    }

    /**
     * Launch a new Event and define initial tasks.
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

            // Task inputs
            'task_title' => ['nullable', 'array'],
            'task_title.*' => ['required', 'string', 'max:255'],
            'task_priority' => ['nullable', 'array'],
            'task_priority.*' => ['required', 'in:low,medium,high'],
            'task_skills' => ['nullable', 'array'], // key is task index, value is array of skill IDs
        ]);

        $event = Event::create([
            'organization_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'start_time' => Carbon::parse($request->start_time),
            'end_time' => Carbon::parse($request->end_time),
            'capacity' => $request->capacity,
            'status' => 'published',
        ]);

        // Process associated tasks. If none are added, create one general volunteer task
        // so the organization can still recruit volunteers for the event.
        $taskTitles = collect($request->input('task_title', []))
            ->filter(fn($title) => filled($title));

        if ($taskTitles->isEmpty()) {
            Task::create([
                'event_id' => $event->id,
                'title' => 'Volunteer support for ' . $event->title,
                'description' => 'General volunteer assistance for this community event.',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => $event->start_time,
            ]);
        } else {
            foreach ($taskTitles as $index => $taskTitle) {
                $priority = $request->task_priority[$index] ?? 'medium';
                $task = Task::create([
                    'event_id' => $event->id,
                    'title' => $taskTitle,
                    'description' => 'Initial event setup task.',
                    'priority' => $priority,
                    'status' => 'pending',
                    'due_date' => $event->start_time,
                ]);

                if ($request->has("task_skills.{$index}")) {
                    $task->skills()->attach($request->task_skills[$index]);
                }
            }
        }

        return redirect()->route('org.dashboard')
            ->with('success', "Event '{$event->title}' has been successfully launched.");
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

        $task = Task::with('event')->findOrFail($data['task_id']);
        abort_unless($task->event && (int) $task->event->organization_id === Auth::id(), 403);

        $volunteer = User::where('id', $data['user_id'])
            ->where('role', 'volunteer')
            ->where('status', 'approved')
            ->firstOrFail();

        $assignment = Assignment::updateOrCreate(
            [
                'event_id' => $task->event_id,
                'task_id' => $task->id,
            ],
            [
                'user_id' => $volunteer->id,
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

        if ($task->status === 'pending') {
            $task->status = 'in_progress';
            $task->save();
        }

        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $volunteer->id,
            'data' => json_encode([
                'title' => 'New Task Assigned',
                'message' => 'You have been assigned to "' . $task->title . '" for "' . ($task->event->title ?? 'an organization event') . '".',
                'icon' => 'fa-list-check',
            ]),
            'read_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('org.dashboard', ['task_id' => $task->id])
            ->with('success', "{$volunteer->name} has been assigned to '{$task->title}'.");
    }

    /**
     * Send a task recruitment notice to approved volunteers.
     */
    public function sendTaskOutreach(Task $task)
    {
        $task->load(['event.organization', 'skills', 'assignments']);
        abort_unless($task->event && (int) $task->event->organization_id === Auth::id(), 403);

        if ($task->event->end_time->isPast() || $task->event->status !== 'published' || $task->status === 'completed') {
            return redirect()->route('org.dashboard', ['task_id' => $task->id])
                ->with('error', 'Only active, open tasks can be sent to volunteers.');
        }

        if ($task->assignments->whereIn('status', ['approved', 'submitted', 'completed'])->isNotEmpty()) {
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

        if ($application->task->status === 'pending') {
            $application->task->status = 'in_progress';
            $application->task->save();
        }

        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $application->user_id,
            'data' => json_encode([
                'title' => 'Task Application Approved',
                'message' => 'You were assigned to "' . ($application->task->title ?? 'an open task') . '".',
                'icon' => 'fa-circle-check',
            ]),
            'read_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

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

        // Create notification for admins
        $orgName = Auth::user()->name;
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $admin->id,
                'data' => json_encode([
                    'title' => 'Compliance Doc Uploaded',
                    'message' => "{$orgName} uploaded a new compliance document: '{$filename}'.",
                    'icon' => 'fa-file-shield',
                ]),
                'read_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return redirect()->route('org.dashboard')
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

        return Storage::disk('public')->download($path, $document->original_data['filename'] ?? basename($path));
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

        $existingCertificate = Certificate::where('user_id', $assignment->user_id)
            ->where('event_id', $assignment->event_id)
            ->first();

        if (!$existingCertificate) {
            $certCode = 'JCI-WENSIES-' . strtoupper(Str::random(4)) . '-' . time();
            Certificate::create([
                'user_id' => $assignment->user_id,
                'event_id' => $assignment->event_id,
                'certificate_code' => $certCode,
                'issued_at' => Carbon::now(),
                'file_path' => 'certificates/' . $certCode . '.pdf',
            ]);
        }

        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $assignment->user_id,
            'data' => json_encode([
                'title' => 'Task Completion Approved',
                'message' => 'Your completion for "' . ($assignment->task->title ?? 'assigned task') . '" was approved. Your certificate is now available.',
                'icon' => 'fa-award',
            ]),
            'read_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('org.dashboard')
            ->with('success', 'Completion approved and certificate issued.');
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

        return redirect()->route('org.dashboard')
            ->with('success', 'Submission returned to the volunteer for revision.');
    }
}
