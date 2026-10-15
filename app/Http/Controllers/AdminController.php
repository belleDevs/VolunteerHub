<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\Task;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\ChatbotRule;
use App\Models\ChatbotResponse;
use App\Models\RecordArchive;
use App\Models\AuditLog;
use App\Support\ActivityReport;
use App\Support\AuditFiles;
use App\Support\AuditTrail;
use App\Support\OrgNotifier;
use App\Support\PortalNotifier;
use App\Support\VolunteerNotice;
use App\Support\ReportFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;


class AdminController extends Controller
{
    /**
     * Display the Admin Dashboard.
     */
    public function dashboard(Request $request)
    {
        $now = Carbon::now();

        $totalVolunteers = User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->count();

        $activeEvents = Event::where('status', 'published')
            ->where('end_time', '>=', $now)
            ->count();

        $approvedOrgs = User::where('role', 'organization')->where('status', 'approved')->count();
        $eventsAwaitingReview = Event::where('status', 'draft')->count();
        $openTasks = Task::whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->whereHas('event', function ($query) {
                $query->where('status', 'published');
            })
            ->count();

        $pendingOrgs = User::where('role', 'organization')->where('status', 'pending')->get();
        $pendingCompletionReviews = Assignment::where('status', 'submitted')->count();
        $issuedCertificates = Certificate::count();
        $chatbotMessageCount = ChatbotResponse::count();
        $broadcastLogCount = RecordArchive::where('table_name', 'broadcasts')->count();

        $monthStart = $now->copy()->startOfMonth();
        $previousMonthEnd = $monthStart->copy()->subSecond();

        $previousVolunteers = User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->where('created_at', '<', $monthStart)
            ->count();
        $previousOrgs = User::where('role', 'organization')
            ->where('status', 'approved')
            ->where('created_at', '<', $monthStart)
            ->count();
        $previousEvents = Event::whereIn('status', ['published', 'completed'])
            ->where('start_time', '<=', $previousMonthEnd)
            ->where('end_time', '>=', $previousMonthEnd)
            ->count();
        $previousOpenTasks = Task::whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->where('created_at', '<', $monthStart)
            ->whereHas('event', function ($query) {
                $query->where('status', 'published');
            })
            ->count();

        $metricTrends = [
            'volunteers' => $this->monthOverMonthTrend($totalVolunteers, $previousVolunteers),
            'events' => $this->monthOverMonthTrend($activeEvents, $previousEvents),
            'orgs' => $this->monthOverMonthTrend($approvedOrgs, $previousOrgs),
            'tasks' => $this->monthOverMonthTrend($openTasks, $previousOpenTasks),
        ];

        $growthStart = $monthStart->copy()->subMonths(5);
        $volunteerSignups = User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->where('created_at', '>=', $growthStart)
            ->get(['created_at'])
            ->groupBy(fn ($user) => $user->created_at->format('Y-m'));

        $volunteerGrowthLabels = [];
        $volunteerGrowthValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $monthStart->copy()->subMonths($i);
            $volunteerGrowthLabels[] = $month->format('M');
            $volunteerGrowthValues[] = $volunteerSignups->get($month->format('Y-m'), collect())->count();
        }

        $volunteerGrowthTrend = $this->monthOverMonthTrend(
            $volunteerGrowthValues[5],
            $volunteerGrowthValues[4]
        );

        $chatbotRules = ChatbotRule::all();
        $chatbotLogs = ChatbotResponse::with('user')
            ->latest()
            ->take(30)
            ->get();
        $organizations = User::where('role', 'organization')
            ->withCount('events')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();
        $organizationIds = $organizations->pluck('id');
        $organizationLocations = $organizationIds->isEmpty()
            ? collect()
            : Event::whereIn('organization_id', $organizationIds)
                ->orderByDesc('start_time')
                ->get(['organization_id', 'location'])
                ->unique('organization_id')
                ->pluck('location', 'organization_id');
        $organizationVolunteerCounts = $organizationIds->isEmpty()
            ? collect()
            : DB::table('assignments')
                ->join('events', 'events.id', '=', 'assignments.event_id')
                ->whereIn('events.organization_id', $organizationIds)
                ->whereNull('assignments.deleted_at')
                ->whereNull('events.deleted_at')
                ->select('events.organization_id as org_id', 'assignments.user_id')
                ->union(
                    DB::table('task_applications')
                        ->join('events', 'events.id', '=', 'task_applications.event_id')
                        ->whereIn('events.organization_id', $organizationIds)
                        ->whereNull('events.deleted_at')
                        ->select('events.organization_id as org_id', 'task_applications.user_id')
                )
                ->union(
                    DB::table('users')
                        ->whereIn('organization_id', $organizationIds)
                        ->where('role', 'volunteer')
                        ->whereNull('deleted_at')
                        ->select('organization_id as org_id', 'id as user_id')
                )
                ->get()
                ->groupBy(fn ($row) => (int) $row->org_id)
                ->map(fn ($rows) => $rows->pluck('user_id')->unique()->count());

        $postedEvents = Event::with([
            'organization:id,name,organization_type',
            'tasks.applications:id,task_id,user_id,status',
            'tasks.assignments' => function ($query) {
                $query->select('id', 'task_id', 'event_id', 'user_id', 'status')
                    ->whereIn('status', ['approved', 'submitted', 'completed'])
                    ->with('user:id,name');
            },
        ])->orderByDesc('start_time')->get();

        $selectedEventOrg = (int) $request->query('org', 0);

        $postedTasks = Task::with([
            'event:id,organization_id,title,location,status',
            'event.organization:id,name,organization_type,event_reward,task_reward',
            'applications:id,task_id,status',
            'skills:id,name',
            'assignments' => function ($query) {
                $query->select('id', 'task_id', 'event_id', 'user_id', 'status')
                    ->whereIn('status', ['approved', 'submitted', 'completed'])
                    ->with('user:id,name');
            },
        ])->orderByRaw('due_date is null, due_date')->get();

        $selectedTaskOrg = (int) $request->query('task_org', 0);
        $eventStatusFilter = in_array($request->query('event_status'), ['review', 'upcoming', 'ongoing', 'completed', 'cancelled'], true)
            ? $request->query('event_status')
            : 'all';
        $taskStatusFilter = in_array($request->query('task_status'), ['pending', 'assigned', 'in_progress', 'completed', 'overdue'], true)
            ? $request->query('task_status')
            : 'all';
        $taskEventFilter = (int) $request->query('task_event', 0);

        $dashboardEventStatus = [
            ['key' => 'review', 'label' => 'Needs review', 'value' => $postedEvents->filter(fn (Event $event) => $event->oversightStatus() === 'review')->count(), 'color' => '#f59e0b'],
            ['key' => 'upcoming', 'label' => 'Upcoming', 'value' => $postedEvents->filter(fn (Event $event) => $event->oversightStatus() === 'upcoming')->count(), 'color' => '#0097d6'],
            ['key' => 'ongoing', 'label' => 'Ongoing', 'value' => $postedEvents->filter(fn (Event $event) => $event->oversightStatus() === 'ongoing')->count(), 'color' => '#0284c7'],
            ['key' => 'completed', 'label' => 'Completed', 'value' => $postedEvents->where('status', 'completed')->count(), 'color' => '#059669'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'value' => $postedEvents->where('status', 'cancelled')->count(), 'color' => '#e11d48'],
        ];
        $dashboardTaskStatus = [
            ['key' => 'pending', 'label' => 'Pending', 'value' => $postedTasks->where('status', 'pending')->count(), 'color' => '#0097d6'],
            ['key' => 'assigned', 'label' => 'Assigned', 'value' => $postedTasks->where('status', 'assigned')->count(), 'color' => '#0284c7'],
            ['key' => 'in_progress', 'label' => 'In Progress', 'value' => $postedTasks->where('status', 'in_progress')->count(), 'color' => '#f59e0b'],
            ['key' => 'completed', 'label' => 'Completed', 'value' => $postedTasks->where('status', 'completed')->count(), 'color' => '#059669'],
            ['key' => 'overdue', 'label' => 'Overdue', 'value' => $postedTasks->filter(fn (Task $task) => $task->isOverdue())->count(), 'color' => '#e11d48'],
        ];

        $dashboardMonthLabels = [];
        $dashboardMonthEvents = [];
        $dashboardMonthVolunteers = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $monthStart->copy()->subMonths($i);
            $monthEnd = $month->copy()->endOfMonth();
            $monthEvents = $postedEvents->filter(fn (Event $event) => $event->start_time && $event->start_time->between($month, $monthEnd));
            $dashboardMonthLabels[] = $month->format('M');
            $dashboardMonthEvents[] = $monthEvents->count();
            $dashboardMonthVolunteers[] = $monthEvents
                ->flatMap(fn (Event $event) => $event->tasks->flatMap(fn (Task $task) => $task->assignments->pluck('user_id')))
                ->unique()
                ->count();
        }

        $dashboardOrganizations = $organizations->map(function (User $org) use ($postedEvents, $postedTasks, $organizationVolunteerCounts) {
            $orgTasks = $postedTasks->filter(fn (Task $task) => (int) $task->event?->organization_id === (int) $org->id);

            return [
                'id' => $org->id,
                'name' => $org->name,
                'events' => $postedEvents->where('organization_id', $org->id)->count(),
                'tasks' => $orgTasks->count(),
                'volunteers' => (int) ($organizationVolunteerCounts[$org->id] ?? 0),
            ];
        })->values();

        $systemUsers = User::whereIn('role', ['volunteer', 'organization'])
            ->with([
                'skills',
                'certificates.event:id,title,organization_id',
                'volunteerRewards.event:id,title',
                'volunteerRewards.task:id,title',
                'volunteerRewards.organization:id,name',
                'primaryOrganization',
                'assignments.event.organization',
                'assignments.task:id,title',
                'taskApplications.event.organization',
            ])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        // Get active tab from session or request (default to dashboard)
        $activeTab = $request->query('tab', 'dashboard');
        $selectedNoticeId = (string) $request->query('notice', '');

        if ($activeTab === 'notifications' && $selectedNoticeId !== '') {
            DB::table('notifications')
                ->where('id', $selectedNoticeId)
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', Auth::id())
                ->whereNull('read_at')
                ->update([
                    'read_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        $adminNotices = Auth::user()
            ->notifications()
            ->latest()
            ->limit(40)
            ->get()
            ->map(function ($notice) {
                $data = is_array($notice->data) ? $notice->data : json_decode($notice->data, true);
                $message = $data['message'] ?? '';

                return [
                    'id' => $notice->id,
                    'title' => $data['title'] ?? 'Notice',
                    'summary' => $data['summary'] ?? \Illuminate\Support\Str::limit($message, 90),
                    'message' => $message,
                    'icon' => $data['icon'] ?? 'fa-bell',
                    'url' => $data['url'] ?? null,
                    'read' => $notice->read_at !== null,
                    'when' => $notice->created_at?->diffForHumans(),
                    'stamp' => $notice->created_at?->format('M d, Y g:i A'),
                ];
            });
        $selectedNotice = $adminNotices->firstWhere('id', $selectedNoticeId);

        return view('admin.dashboard', compact(
            'totalVolunteers',
            'activeEvents',
            'approvedOrgs',
            'openTasks',
            'pendingOrgs',
            'pendingCompletionReviews',
            'issuedCertificates',
            'chatbotMessageCount',
            'broadcastLogCount',
            'chatbotRules',
            'chatbotLogs',
            'systemUsers',
            'activeTab',
            'metricTrends',
            'volunteerGrowthLabels',
            'volunteerGrowthValues',
            'volunteerGrowthTrend',
            'organizations',
            'organizationLocations',
            'organizationVolunteerCounts',
            'postedEvents',
            'selectedEventOrg',
            'postedTasks',
            'selectedTaskOrg',
            'eventsAwaitingReview',
            'eventStatusFilter',
            'taskStatusFilter',
            'taskEventFilter',
            'adminNotices',
            'selectedNotice',
            'dashboardEventStatus',
            'dashboardTaskStatus',
            'dashboardMonthLabels',
            'dashboardMonthEvents',
            'dashboardMonthVolunteers',
            'dashboardOrganizations'
        ));
    }

    /**
     * Signed change versus the previous month. Hidden when the count did not move.
     * A zero baseline cannot become a percentage, so the chip shows the raw difference.
     */
    private function monthOverMonthTrend(int $current, int $previous): ?array
    {
        if ($current === $previous) {
            return null;
        }

        if ($previous === 0) {
            $delta = $current - $previous;

            return [
                'direction' => $delta > 0 ? 'up' : 'down',
                'label' => ($delta > 0 ? '+' : '') . $delta,
            ];
        }

        $percent = (int) round((($current - $previous) / $previous) * 100);

        return [
            'direction' => $percent > 0 ? 'up' : 'down',
            'label' => ($percent > 0 ? '+' : '') . $percent . '%',
        ];
    }

    /**
     * Approve organization registration.
     */
    public function approveOrg($id)
    {
        $org = User::findOrFail($id);
        $previous = $org->status;
        $org->status = 'approved';
        $org->verification_status = 'verified';
        $org->verification_updated_at = now();
        $org->documents_requested = null;
        $org->save();

        AuditTrail::record([
            'action' => 'Organization approved',
            'action_type' => 'approved',
            'module' => 'Organizations',
            'description' => 'Approved '.$org->name.'.',
            'subject' => $org,
            'subject_label' => $org->name,
            'previous' => ['status' => $previous],
            'new' => ['status' => 'approved'],
        ]);

        OrgNotifier::send(
            (int) $org->id,
            'account',
            'Organization registration approved',
            $org->name.' is approved. The organization account now has full dashboard access.',
            $org->name,
            route('org.dashboard'),
            'Open dashboard',
            true,
            'org-approved-'.$org->id
        );

        return redirect()->back()
            ->with('success', "Organization '{$org->name}' has been approved successfully.");
    }

    /**
     * Reject organization registration.
     */
    public function rejectOrg(Request $request, $id)
    {
        $org = User::findOrFail($id);
        $previous = $org->status;
        $org->status = 'rejected';
        $org->verification_status = 'rejected';
        $org->verification_feedback = $request->input('feedback') ?: $org->verification_feedback;
        $org->verification_updated_at = now();
        $org->save();

        AuditTrail::record([
            'action' => 'Organization rejected',
            'action_type' => 'rejected',
            'module' => 'Organizations',
            'description' => 'Rejected '.$org->name.'.',
            'subject' => $org,
            'subject_label' => $org->name,
            'previous' => ['status' => $previous],
            'new' => ['status' => 'rejected'],
        ]);

        OrgNotifier::send(
            (int) $org->id,
            'account',
            'Organization registration rejected',
            'The registration for '.$org->name.' was declined during compliance review.'.($org->verification_feedback ? ' '.$org->verification_feedback : ''),
            $org->name,
            route('org.dashboard', ['section' => 'settings', 'pane' => 'documents']),
            'Open documents',
            true,
            'org-rejected-'.$org->id
        );

        return redirect()->back()
            ->with('success', "Organization '{$org->name}' has been rejected.");
    }

    public function requestOrgDocuments(Request $request, $id)
    {
        $org = User::where('role', 'organization')->findOrFail($id);
        $data = $request->validate([
            'documents_requested' => ['required', 'string', 'max:1000'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        $org->verification_status = 'additional_information';
        $org->documents_requested = $data['documents_requested'];
        $org->verification_feedback = $data['feedback'] ?? null;
        $org->verification_updated_at = now();
        $org->save();

        AuditTrail::record([
            'action' => 'Documents requested',
            'action_type' => 'updated',
            'module' => 'Organizations',
            'description' => 'Asked '.$org->name.' for additional verification documents.',
            'subject' => $org,
            'subject_label' => $org->name,
            'new' => ['documents_requested' => $data['documents_requested']],
        ]);

        OrgNotifier::send(
            (int) $org->id,
            'account',
            'Additional verification documents requested',
            $data['documents_requested'].($data['feedback'] ? ' '.$data['feedback'] : ''),
            $org->name,
            route('org.dashboard', ['section' => 'settings', 'pane' => 'documents']),
            'Open documents',
            true,
            'docs-requested-'.$org->id.'-'.now()->timestamp
        );

        return redirect()->back()
            ->with('success', 'The document request was sent to '.$org->name.'.');
    }

    /**
     * Store new Chatbot Rule.
     */
    public function storeChatbotRule(Request $request)
    {
        $data = $request->validate([
            'keyword' => ['required', 'string', 'unique:chatbot_rules,keyword'],
            'response' => ['required', 'string'],
        ]);

        $rule = ChatbotRule::create($data);

        AuditTrail::record([
            'action' => 'Chatbot rule created',
            'action_type' => 'created',
            'module' => 'System',
            'description' => 'Saved a chatbot rule for "'.$rule->keyword.'".',
            'subject' => $rule,
            'subject_label' => $rule->keyword,
            'new' => ['keyword' => $rule->keyword],
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'chatbot'])
            ->with('success', 'New intent rule saved successfully.');
    }

    /**
     * Delete Chatbot Rule.
     */
    public function deleteChatbotRule($id)
    {
        $rule = ChatbotRule::findOrFail($id);
        $keyword = $rule->keyword;
        $rule->delete();

        AuditTrail::record([
            'action' => 'Chatbot rule deleted',
            'action_type' => 'deleted',
            'module' => 'System',
            'description' => 'Deleted the chatbot rule for "'.$keyword.'".',
            'subject_label' => $keyword,
            'previous' => ['keyword' => $keyword],
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'chatbot'])
            ->with('success', 'Intent rule deleted successfully.');
    }

    public function replyToChatbotConversation(Request $request, ChatbotResponse $chatbotResponse)
    {
        $data = $request->validate([
            'admin_reply' => ['required', 'string', 'max:2000'],
        ]);

        $chatbotResponse->update([
            'admin_reply' => $data['admin_reply'],
            'admin_replied_by' => Auth::id(),
            'admin_replied_at' => Carbon::now(),
        ]);

        AuditTrail::record([
            'action' => 'Chatbot reply sent',
            'action_type' => 'updated',
            'module' => 'System',
            'description' => 'Replied to a volunteer assistant question.',
            'subject' => $chatbotResponse,
            'subject_label' => 'Assistant question',
        ]);

        if ($chatbotResponse->user_id) {
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $chatbotResponse->user_id,
                'data' => json_encode([
                    'title' => 'Admin replied to your assistant question',
                    'message' => 'Open the AI Assistant to view the admin response to your recent question.',
                    'icon' => 'fa-comments',
                ]),
                'read_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return redirect()->route('admin.dashboard', ['tab' => 'chatbot'])
            ->with('success', 'Admin reply sent to the volunteer conversation.');
    }

    /**
     * Handle system notice form submission.
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'notice_type' => ['required', 'string', 'in:technical_issue,maintenance,service_update,general_advisory'],
            'impact_level' => ['required', 'string', 'in:info,minor,major,critical'],
            'title' => ['required', 'string'],
            'body' => ['required', 'string'],
        ]);

        $noticeTypes = [
            'technical_issue' => 'Technical Issue',
            'maintenance' => 'Scheduled Maintenance',
            'service_update' => 'Service Update',
            'general_advisory' => 'General Advisory',
        ];

        $impactLevels = [
            'info' => 'Informational',
            'minor' => 'Minor Impact',
            'major' => 'Major Impact',
            'critical' => 'Critical',
        ];

        $mediums = ['Web Portal'];
        $mediumsStr = implode(', ', $mediums);
        $noticeType = $noticeTypes[$request->notice_type];
        $impactLevel = $impactLevels[$request->impact_level];
        $recipients = User::whereIn('role', ['organization', 'volunteer'])
            ->where('status', 'approved')
            ->get(['id', 'role']);
        $now = Carbon::now();

        RecordArchive::create([
            'table_name' => 'broadcasts',
            'record_id' => time(),
            'original_data' => [
                'title' => $request->title,
                'body' => $request->body,
                'notice_type' => $noticeType,
                'impact_level' => $impactLevel,
                'mediums' => $mediums,
                'recipient_count' => $recipients->count()
            ],
            'archived_by' => Auth::id(),
            'reason' => "{$noticeType} system notice recorded for {$mediumsStr} with {$impactLevel} impact."
        ]);

        if ($recipients->isNotEmpty()) {
            DB::table('notifications')->insert(
                $recipients->map(function ($recipient) use ($request, $noticeType, $impactLevel, $now) {
                    return [
                        'id' => Str::uuid(),
                        'type' => 'App\\Notifications\\GenericNotification',
                        'notifiable_type' => 'App\\Models\\User',
                        'notifiable_id' => $recipient->id,
                        'data' => json_encode([
                            'title' => $request->title,
                            'message' => "{$noticeType} ({$impactLevel}): {$request->body}",
                            'category' => 'system',
                            'related' => 'Admin',
                            'url' => $recipient->role === 'organization' ? route('org.dashboard', ['section' => 'notifications']) : null,
                            'action' => 'View notifications',
                            'icon' => 'fa-tower-broadcast',
                        ]),
                        'read_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->toArray()
            );
        }

        AuditTrail::record([
            'action' => 'System notice posted',
            'action_type' => 'created',
            'module' => 'System',
            'description' => 'Posted "'.$request->title.'" to '.$recipients->count().' approved account(s).',
            'subject_label' => $request->title,
            'new' => [
                'notice_type' => $noticeType,
                'impact' => $impactLevel,
                'recipients' => $recipients->count(),
            ],
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'broadcast'])
            ->with('success', "{$noticeType} notice posted to {$recipients->count()} approved portal user(s).");
    }

    /**
     * Make an organization event visible to volunteers.
     */
    public function approveEvent(Event $event)
    {
        if ($event->status !== 'draft') {
            AuditTrail::record([
                'action' => 'Event approval denied',
                'action_type' => 'approved',
                'module' => 'Events',
                'description' => 'Did not approve "'.$event->title.'" because it is already past review.',
                'status' => 'failed',
                'subject' => $event,
                'subject_label' => $event->title,
                'previous' => ['status' => $event->status],
            ]);

            return redirect()->back()->with('error', 'This event is already past review.');
        }

        if ($event->end_time && $event->end_time->isPast()) {
            AuditTrail::record([
                'action' => 'Event approval denied',
                'action_type' => 'approved',
                'module' => 'Events',
                'description' => 'Did not approve "'.$event->title.'" because the scheduled end has passed.',
                'status' => 'failed',
                'subject' => $event,
                'subject_label' => $event->title,
                'previous' => ['status' => $event->status],
            ]);

            return redirect()->back()->with('error', 'Correct the event dates before approving it. The scheduled end has already passed.');
        }

        $event->status = 'published';
        $event->save();

        AuditTrail::record([
            'action' => 'Event approved',
            'action_type' => 'approved',
            'module' => 'Events',
            'description' => 'Approved "'.$event->title.'".',
            'subject' => $event,
            'subject_label' => $event->title,
            'previous' => ['status' => 'draft'],
            'new' => ['status' => 'published'],
        ]);

        OrgNotifier::send(
            (int) $event->organization_id,
            'event',
            'Event approved',
            'Your event "'.$event->title.'" has been approved by the Admin and is now available on VolunteerHub.',
            $event->title,
            route('org.dashboard', ['section' => 'events']),
            'View event',
            true,
            'event-approved-'.$event->id
        );

        VolunteerNotice::notifyPublishedEvent($event);

        return redirect()->back()->with('success', "\"{$event->title}\" is approved and visible to volunteers.");
    }

    /**
     * Tell the organization that one overdue task needs a volunteer. The administrator confirms this first.
     */
    public function notifyOverdueTask(Task $task)
    {
        if (!PortalNotifier::notifyOrganizationAboutOverdue($task)) {
            AuditTrail::record([
                'action' => 'Overdue notice denied',
                'action_type' => 'other',
                'module' => 'Tasks',
                'description' => 'Did not notify anyone about "'.$task->title.'" because it is not an open overdue task.',
                'status' => 'failed',
                'subject' => $task,
                'subject_label' => $task->title,
            ]);

            return redirect()->back()->with('error', 'Only an open overdue task can be sent to its organization.');
        }

        $organization = $task->event->organization->name ?? 'the organization';

        AuditTrail::record([
            'action' => 'Overdue notice sent',
            'action_type' => 'other',
            'module' => 'Tasks',
            'description' => 'Notified '.$organization.' that "'.$task->title.'" is overdue.',
            'subject' => $task,
            'subject_label' => $task->title,
            'new' => ['organization' => $organization, 'task_status' => $task->status],
        ]);

        return redirect()->back()->with('success', "{$organization} was notified about \"{$task->title}\".");
    }

    public function reports(Request $request)
    {
        AuditTrail::report($request);

        return view('reports.show', [
            'report' => ActivityReport::build($request, 'admin', Auth::user()),
        ]);
    }

    public function exportReport(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'xls'], true), 404);

        $report = ActivityReport::build($request, 'admin', Auth::user());
        AuditTrail::report($request, $format);

        return $format === 'pdf' ? ReportFiles::pdf($report) : ReportFiles::excel($report);
    }

    public function auditLogs(Request $request)
    {
        return view('admin.audit', [
            'summary' => AuditTrail::summary(),
            'logs' => AuditTrail::filtered($request)->paginate(15)->withQueryString(),
        ]);
    }

    public function auditLog(AuditLog $auditLog)
    {
        return response()->json([
            'id' => $auditLog->id,
            'when' => AuditTrail::localTime($auditLog->created_at),
            'actor_name' => $auditLog->actor_name,
            'actor_role' => AuditTrail::roleLabel($auditLog->actor_role),
            'action' => $auditLog->action,
            'module' => $auditLog->module,
            'description' => $auditLog->description,
            'subject_label' => $auditLog->subject_label,
            'previous' => $auditLog->previous_values,
            'new' => $auditLog->new_values,
            'status' => $auditLog->status === 'failed' ? 'Failed' : 'Success',
            'ip_address' => $auditLog->ip_address,
        ]);
    }

    public function exportAudit(Request $request, string $format)
    {
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);

        $query = AuditTrail::filtered($request);

        AuditTrail::record([
            'action' => 'Audit log exported',
            'action_type' => 'other',
            'module' => 'System',
            'description' => 'Exported the filtered audit log as '.strtoupper($format).'.',
            'new' => array_filter([
                'format' => $format,
                'role' => $request->query('role'),
                'module' => $request->query('module'),
                'status' => $request->query('status'),
                'action_type' => $request->query('action_type'),
            ], fn ($value) => $value !== null && $value !== ''),
        ]);

        return $format === 'csv' ? AuditFiles::csv($query) : AuditFiles::pdf($query);
    }

    private function notifyUser(int $userId, string $title, string $message, string $icon): void
    {
        if ($userId <= 0) {
            return;
        }

        $now = Carbon::now();

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $userId,
            'data' => json_encode([
                'title' => $title,
                'message' => $message,
                'icon' => $icon,
            ]),
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
