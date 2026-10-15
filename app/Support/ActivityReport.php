<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ActivityReport
{
    public function __construct(
        public string $audience,
        public string $category,
        public string $rangeKey,
        public string $rangeLabel,
        public string $startDate,
        public string $endDate,
        public string $search,
        public string $status,
        public ?int $organizationId,
        public ?int $eventId,
        public ?int $taskId,
        public ?int $volunteerId,
        public string $purpose,
        public string $purposeLabel,
        public string $subjectTitle,
        public bool $awaitingSubject,
        public ?string $detail,
        public string $pageRoute,
        public string $exportRoute,
        public array $categories,
        public array $statuses,
        public array $organizationOptions,
        public array $eventOptions,
        public array $taskOptions,
        public array $volunteerOptions,
        public array $cards,
        public array $eventStatus,
        public array $taskStatus,
        public array $monthlyEvents,
        public array $monthlyAttendance,
        public array $monthlyLabels,
        public array $monthlySignups,
        public bool $showSignupChart,
        public ?string $completionRate,
        public int $tasksTotal,
        public int $tasksCompleted,
        public array $sections,
        public ?string $detailTitle,
    ) {}

    public static function build(Request $request, string $audience, User $user): self
    {
        [$start, $end, $rangeKey] = self::period($request);
        $categories = self::categoriesFor($audience);
        $category = (string) $request->query('category', $audience === 'volunteer' ? 'participation' : 'overview');
        if (!array_key_exists($category, $categories)) {
            $category = $audience === 'volunteer' ? 'participation' : 'overview';
        }

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $detail = $request->query('detail');
        $detail = is_string($detail) && $detail !== '' ? $detail : null;

        $purpose = $audience === 'admin' ? (string) $request->query('purpose', 'platform') : 'platform';
        if (!in_array($purpose, ['platform', 'organization', 'event', 'task', 'volunteer'], true)) {
            $purpose = 'platform';
        }

        $organizationId = $audience === 'organization'
            ? (int) $user->id
            : ($request->filled('organization') ? (int) $request->query('organization') : null);
        $eventId = null;
        $taskId = null;
        $volunteerId = null;
        $awaitingSubject = false;

        if ($audience === 'volunteer') {
            $organizationId = null;
        }

        if ($audience === 'admin' && in_array($purpose, ['platform', 'event'], true) && $request->filled('event')) {
            $eventId = (int) $request->query('event');
        }

        if ($audience === 'admin' && $purpose === 'task' && $request->filled('task')) {
            $chosenTask = Task::with('event')->find((int) $request->query('task'));
            $eventNarrow = $request->filled('event') ? (int) $request->query('event') : null;
            if ($chosenTask && $chosenTask->event && (!$organizationId || (int) $chosenTask->event->organization_id === $organizationId) && (!$eventNarrow || (int) $chosenTask->event_id === $eventNarrow)) {
                $taskId = (int) $chosenTask->id;
                $eventId = (int) $chosenTask->event_id;
            }
        }

        if ($audience === 'admin' && $purpose === 'volunteer' && $request->filled('volunteer')) {
            $chosenVolunteer = User::where('role', 'volunteer')->find((int) $request->query('volunteer'));
            if ($chosenVolunteer) {
                $volunteerId = (int) $chosenVolunteer->id;
            }
        }

        if ($audience === 'admin' && $eventId && $organizationId && $purpose === 'event') {
            $chosenEvent = Event::find($eventId);
            if (!$chosenEvent || (int) $chosenEvent->organization_id !== $organizationId) {
                $eventId = null;
            }
        }

        $awaitingSubject = $audience === 'admin' && (
            ($purpose === 'organization' && !$organizationId)
            || ($purpose === 'event' && !$eventId)
            || ($purpose === 'task' && !$taskId)
            || ($purpose === 'volunteer' && !$volunteerId)
        );

        $skipPeriod = in_array($purpose, ['event', 'task'], true) && ($eventId || $taskId);

        $events = Event::query()
            ->with(['organization', 'tasks.skills', 'tasks.assignments.user', 'assignments.user'])
            ->when($audience === 'organization', fn ($query) => $query->where('organization_id', $user->id))
            ->when($audience === 'volunteer', function ($query) use ($user) {
                $query->whereHas('assignments', fn ($assignments) => $assignments->where('user_id', $user->id));
            })
            ->when(!$skipPeriod, function ($query) use ($start, $end) {
                $query->where(function ($query) use ($start, $end) {
                    $query->where(function ($query) use ($start, $end) {
                        $query->where('start_time', '<=', $end)->where('end_time', '>=', $start);
                    })->orWhereBetween('created_at', [$start, $end]);
                });
            })
            ->when($audience === 'admin' && $organizationId && !in_array($purpose, ['event', 'task'], true), fn ($query) => $query->where('organization_id', $organizationId))
            ->when($purpose === 'volunteer' && $volunteerId, fn ($query) => $query->whereHas('assignments', fn ($assignments) => $assignments->where('user_id', $volunteerId)))
            ->when($eventId, fn ($query) => $query->where('id', $eventId))
            ->orderBy('start_time')
            ->get();

        $tasks = $events->flatMap(function (Event $event) use ($audience, $user) {
            return $event->tasks->filter(function (Task $task) use ($audience, $user, $event) {
                if ($audience !== 'volunteer') {
                    return true;
                }

                return $event->assignments
                    ->where('task_id', $task->id)
                    ->where('user_id', $user->id)
                    ->isNotEmpty();
            })->each(function (Task $task) use ($event, $audience, $user) {
                $task->setRelation('event', $event);
                if ($audience === 'volunteer') {
                    $task->setRelation(
                        'assignments',
                        $event->assignments->where('task_id', $task->id)->where('user_id', $user->id)->values()
                    );
                }
            });
        })->values();

        if ($taskId) {
            $tasks = $tasks->where('id', $taskId)->values();
        }

        if ($purpose === 'volunteer' && $volunteerId) {
            $tasks = $tasks->filter(function (Task $task) use ($volunteerId) {
                return $task->assignments->contains(fn ($assignment) => (int) $assignment->user_id === $volunteerId);
            })->each(function (Task $task) use ($volunteerId) {
                $task->setRelation('assignments', $task->assignments->where('user_id', $volunteerId)->values());
            })->values();
            $taskEventIds = $tasks->pluck('event_id');
            $events = $events->filter(function (Event $event) use ($volunteerId, $taskEventIds) {
                return $event->assignments->contains(fn ($assignment) => (int) $assignment->user_id === $volunteerId)
                    || $taskEventIds->contains($event->id);
            })->values();
        }

        $volunteers = self::volunteersFor($audience, $user, $events, $start, $end);
        $organizations = self::organizationsFor($audience, $user, $events, $start, $end);

        if ($eventId) {
            $volunteers = $volunteers->filter(fn (User $volunteer) => $volunteer->assignments->where('event_id', $eventId)->isNotEmpty())->values();
            $organizations = $organizations->filter(fn (User $organization) => $events->contains(fn (Event $event) => (int) $event->organization_id === (int) $organization->id))->values();
        }

        if ($purpose === 'organization' && $organizationId) {
            $members = User::where('role', 'volunteer')
                ->where('organization_id', $organizationId)
                ->with(['primaryOrganization', 'assignments', 'skills'])
                ->orderBy('name')
                ->get();
            $volunteers = $volunteers->merge($members)->unique('id')->sortBy('name')->values();
            if ($organizations->where('id', $organizationId)->isEmpty()) {
                $selectedOrganization = User::find($organizationId);
                if ($selectedOrganization) {
                    $organizations = collect([$selectedOrganization]);
                }
            }
            $organizations = $organizations->where('id', $organizationId)->values();
        }

        if ($volunteerId) {
            $volunteers = $volunteers->where('id', $volunteerId)->values();
            if ($volunteers->isEmpty()) {
                $selectedVolunteer = User::where('role', 'volunteer')
                    ->with(['primaryOrganization', 'assignments', 'skills'])
                    ->find($volunteerId);
                $volunteers = $selectedVolunteer ? collect([$selectedVolunteer]) : collect();
            }
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $events = $events->filter(fn (Event $event) => self::eventMatches($event, $needle))->values();
            $eventIds = $events->pluck('id');
            $tasks = $tasks->whereIn('event_id', $eventIds)->values();
            $assignedIds = $events->flatMap(fn (Event $event) => $event->assignments->pluck('user_id'));
            $volunteers = $volunteers->filter(fn (User $volunteer) => self::contains($volunteer->name, $needle) || $assignedIds->contains($volunteer->id))->values();
            $organizations = $organizations->filter(fn (User $organization) => self::contains($organization->name, $needle) || $eventIds->isNotEmpty() && $events->contains(fn (Event $event) => (int) $event->organization_id === (int) $organization->id))->values();
        }

        $organizationOptions = $audience === 'admin'
            ? User::where('role', 'organization')->orderBy('name')->get(['id', 'name'])->map(fn (User $organization) => [
                'id' => $organization->id,
                'label' => $organization->name,
            ])->all()
            : [];

        $eventOptions = Event::with('organization')
            ->when($audience === 'organization', fn ($query) => $query->where('organization_id', $user->id))
            ->when($audience === 'admin' && $organizationId, fn ($query) => $query->where('organization_id', $organizationId))
            ->orderBy('title')
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'label' => $organizationId ? $event->title : $event->title.' - '.($event->organization->name ?? 'Organization'),
            ])->all();

        $taskOptions = Task::with('event.organization')
            ->when($audience === 'organization', fn ($query) => $query->whereHas('event', fn ($events) => $events->where('organization_id', $user->id)))
            ->when($audience === 'admin' && $organizationId, fn ($query) => $query->whereHas('event', fn ($events) => $events->where('organization_id', $organizationId)))
            ->when($request->filled('event'), fn ($query) => $query->where('event_id', (int) $request->query('event')))
            ->orderBy('title')
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'label' => $task->title.' - '.($task->event->title ?? 'Event'),
            ])->all();

        $volunteerOptions = User::where('role', 'volunteer')
            ->when($audience === 'admin' && $organizationId, function ($query) use ($organizationId) {
                $query->where(function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId)
                        ->orWhereHas('assignments', fn ($assignments) => $assignments->whereHas('event', fn ($events) => $events->where('organization_id', $organizationId)));
                });
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $volunteer) => [
                'id' => $volunteer->id,
                'label' => $volunteer->name,
            ])->all();

        $statuses = self::statusesFor($category);
        if ($status !== 'all' && !array_key_exists($status, $statuses)) {
            $status = 'all';
        }

        if ($category === 'events' && $status !== 'all') {
            $events = $events->filter(fn (Event $event) => self::eventStatusKey($event) === $status || ($status === 'approved' && $event->status === 'published'))->values();
            $eventIds = $events->pluck('id');
            $tasks = $tasks->whereIn('event_id', $eventIds)->values();
        }

        if ($category === 'tasks' && $status !== 'all') {
            $tasks = $tasks->filter(fn (Task $task) => self::taskStatusKey($task, $status) === $status)->values();
            $eventIds = $tasks->pluck('event_id')->unique();
            $events = $events->whereIn('id', $eventIds)->values();
        }

        if (in_array($category, ['volunteers', 'participation'], true) && $status !== 'all') {
            $volunteers = $volunteers->filter(fn (User $volunteer) => ($volunteer->availability ?? 'active') === $status)->values();
        }

        if ($category === 'organizations' && $status !== 'all') {
            $organizations = $organizations->filter(fn (User $organization) => $organization->status === $status)->values();
            $orgIds = $organizations->pluck('id');
            $events = $events->whereIn('organization_id', $orgIds)->values();
            $eventIds = $events->pluck('id');
            $tasks = $tasks->whereIn('event_id', $eventIds)->values();
        }

        $events->each(function (Event $event) use ($tasks) {
            $event->setRelation('tasks', $tasks->where('event_id', $event->id)->values());
        });

        $registeredVolunteers = $volunteers->filter(fn (User $volunteer) => $volunteer->created_at && $volunteer->created_at->between($start, $end))->values();
        $registeredOrganizations = $organizations->filter(fn (User $organization) => $organization->created_at && $organization->created_at->between($start, $end))->values();

        $rowAudience = $purpose === 'volunteer' ? 'volunteer' : $audience;
        $rowUser = $purpose === 'volunteer' && $volunteers->isNotEmpty() ? $volunteers->first() : $user;
        $eventRows = $events->map(fn (Event $event) => self::eventRow($event, $rowAudience, $rowUser))->values();
        $taskRows = $tasks->map(fn (Task $task) => self::taskRow($task))->values();
        $volunteerRows = $volunteers->map(fn (User $volunteer) => self::volunteerRow($volunteer, $events, $audience, $user))->values();
        $organizationRows = $organizations
            ->map(fn (User $organization) => self::organizationRow($organization, $events, $tasks))
            ->sortByDesc(fn (array $row) => (int) $row['events'])
            ->values();
        $skillRows = self::skillRows($tasks);

        $eventStatus = [
            ['key' => 'review', 'label' => 'Needs review', 'value' => $events->filter(fn (Event $event) => $event->oversightStatus() === 'review')->count(), 'color' => '#f59e0b'],
            ['key' => 'upcoming', 'label' => 'Upcoming', 'value' => $events->filter(fn (Event $event) => $event->oversightStatus() === 'upcoming')->count(), 'color' => '#0097d6'],
            ['key' => 'ongoing', 'label' => 'Ongoing', 'value' => $events->filter(fn (Event $event) => $event->oversightStatus() === 'ongoing')->count(), 'color' => '#0284c7'],
            ['key' => 'completed', 'label' => 'Completed', 'value' => $events->where('status', 'completed')->count(), 'color' => '#059669'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'value' => $events->where('status', 'cancelled')->count(), 'color' => '#e11d48'],
        ];

        $taskStatus = [
            ['key' => 'pending', 'label' => 'Pending', 'value' => $tasks->where('status', 'pending')->count(), 'color' => '#0097d6'],
            ['key' => 'assigned', 'label' => 'Assigned', 'value' => $tasks->where('status', 'assigned')->count(), 'color' => '#0284c7'],
            ['key' => 'in_progress', 'label' => 'In Progress', 'value' => $tasks->where('status', 'in_progress')->count(), 'color' => '#f59e0b'],
            ['key' => 'completed', 'label' => 'Completed', 'value' => $tasks->where('status', 'completed')->count(), 'color' => '#059669'],
            ['key' => 'overdue', 'label' => 'Overdue', 'value' => $tasks->filter(fn (Task $task) => $task->isOverdue())->count(), 'color' => '#e11d48'],
        ];

        $tasksTotal = $tasks->count();
        $tasksCompleted = $tasks->where('status', 'completed')->count();
        $completionRate = $tasksTotal > 0 ? number_format(($tasksCompleted / $tasksTotal) * 100, 1).'%' : null;

        [$monthlyLabels, $monthlyEvents, $monthlyAttendance, $monthlySignups] = self::monthly(
            $start,
            $end,
            $events,
            $audience === 'admin' ? $registeredVolunteers : $volunteers,
            $purpose === 'volunteer' ? $volunteerId : null,
            $purpose === 'task' ? $tasks->pluck('id') : null
        );
        $showSignupChart = $purpose === 'platform' && $audience === 'admin' && $search === '' && !$eventId && !$request->filled('organization');

        $cards = $awaitingSubject ? [] : self::cards(
            $audience,
            $category,
            $registeredVolunteers,
            $volunteers,
            $registeredOrganizations,
            $organizations,
            $events,
            $tasks,
            $completionRate,
            $tasksCompleted,
            $tasksTotal,
            $purpose === 'volunteer' && $volunteerId ? $volunteerId : ($audience === 'volunteer' ? (int) $user->id : null),
            $purpose,
            $user
        );

        $sections = $awaitingSubject ? [] : self::sections($rowAudience, $category, $eventRows, $taskRows, $volunteerRows, $organizationRows, $skillRows, $events, $tasks, $purpose);
        $detailTitle = null;
        if ($detail) {
            [$detailTitle, $detailSection] = self::detailSection($detail, $events, $tasks, $registeredVolunteers, $volunteers, $registeredOrganizations, $organizations, $audience, $user);
            if ($detailSection) {
                array_unshift($sections, $detailSection);
            } else {
                $detail = null;
            }
        }

        $purposeLabels = [
            'organization' => 'Organization report',
            'event' => 'Event report',
            'task' => 'Task report',
            'volunteer' => 'Volunteer report',
            'platform' => 'Platform report',
        ];
        $subjectTitle = $awaitingSubject ? '' : match ($purpose) {
            'organization' => $organizations->first()->name ?? '',
            'event' => $events->first()->title ?? '',
            'task' => $tasks->first()->title ?? '',
            'volunteer' => $volunteers->first()->name ?? '',
            default => '',
        };

        $routes = [
            'admin' => ['admin.reports', 'admin.reports.export'],
            'organization' => ['org.reports', 'org.reports.export'],
            'volunteer' => ['volunteer.reports', 'volunteer.reports.export'],
        ];

        return new self(
            audience: $audience,
            category: $category,
            rangeKey: $rangeKey,
            rangeLabel: $start->format('M j, Y').' – '.$end->format('M j, Y'),
            startDate: $start->toDateString(),
            endDate: $end->toDateString(),
            search: $search,
            status: $status,
            organizationId: $audience === 'admin' ? $organizationId : null,
            eventId: $purpose === 'task' ? null : $eventId,
            taskId: $taskId,
            volunteerId: $volunteerId,
            purpose: $purpose,
            purposeLabel: $purposeLabels[$purpose],
            subjectTitle: $subjectTitle,
            awaitingSubject: $awaitingSubject,
            detail: $detail,
            pageRoute: $routes[$audience][0],
            exportRoute: $routes[$audience][1],
            categories: $categories,
            statuses: $statuses,
            organizationOptions: $organizationOptions,
            eventOptions: $eventOptions,
            taskOptions: $audience === 'admin' ? $taskOptions : [],
            volunteerOptions: $audience === 'admin' ? $volunteerOptions : [],
            cards: $cards,
            eventStatus: $eventStatus,
            taskStatus: $taskStatus,
            monthlyEvents: $monthlyEvents,
            monthlyAttendance: $monthlyAttendance,
            monthlyLabels: $monthlyLabels,
            monthlySignups: $monthlySignups,
            showSignupChart: $showSignupChart,
            completionRate: $completionRate,
            tasksTotal: $tasksTotal,
            tasksCompleted: $tasksCompleted,
            sections: $sections,
            detailTitle: $detailTitle,
        );
    }

    public function query(array $overrides = []): string
    {
        $params = [
            'range' => $this->rangeKey,
            'category' => $this->category,
            'purpose' => $this->purpose,
        ];

        if ($this->rangeKey === 'custom') {
            $params['from'] = $this->startDate;
            $params['to'] = $this->endDate;
        }
        if ($this->organizationId) {
            $params['organization'] = $this->organizationId;
        }
        if ($this->eventId) {
            $params['event'] = $this->eventId;
        }
        if ($this->taskId) {
            $params['task'] = $this->taskId;
        }
        if ($this->volunteerId) {
            $params['volunteer'] = $this->volunteerId;
        }
        if ($this->status !== 'all') {
            $params['status'] = $this->status;
        }
        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if ($this->detail) {
            $params['detail'] = $this->detail;
        }

        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return http_build_query($params);
    }

    private static function period(Request $request): array
    {
        $rangeKey = (string) $request->query('range', 'year');
        $now = Carbon::now();

        try {
            switch ($rangeKey) {
                case 'today':
                    $start = $now->copy()->startOfDay();
                    $end = $now->copy()->endOfDay();
                    break;
                case 'week':
                    $start = $now->copy()->startOfWeek();
                    $end = $now->copy()->endOfWeek();
                    break;
                case 'month':
                    $start = $now->copy()->startOfMonth();
                    $end = $now->copy()->endOfMonth();
                    break;
                case 'custom':
                    $start = Carbon::parse($request->query('from', $now->copy()->startOfYear()->toDateString()))->startOfDay();
                    $end = Carbon::parse($request->query('to', $now->toDateString()))->endOfDay();
                    if ($end->lt($start)) {
                        [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                    }
                    break;
                default:
                    $rangeKey = 'year';
                    $start = $now->copy()->startOfYear();
                    $end = $now->copy()->endOfYear();
                    break;
            }
        } catch (\Throwable) {
            $rangeKey = 'year';
            $start = $now->copy()->startOfYear();
            $end = $now->copy()->endOfYear();
        }

        return [$start, $end, $rangeKey];
    }

    private static function categoriesFor(string $audience): array
    {
        if ($audience === 'volunteer') {
            return ['participation' => 'My participation'];
        }

        $categories = [
            'overview' => 'Overview',
            'events' => 'Events',
            'tasks' => 'Tasks',
            'volunteers' => 'Volunteers',
        ];

        if ($audience === 'admin') {
            $categories['organizations'] = 'Organizations';
        }

        return $categories;
    }

    private static function statusesFor(string $category): array
    {
        return match ($category) {
            'events' => [
                'all' => 'All statuses',
                'review' => 'Needs review',
                'approved' => 'Approved',
                'upcoming' => 'Upcoming',
                'ongoing' => 'Ongoing',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ],
            'tasks' => [
                'all' => 'All statuses',
                'pending' => 'Pending',
                'assigned' => 'Assigned',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'overdue' => 'Overdue',
                'cancelled' => 'Cancelled',
            ],
            'volunteers', 'participation' => [
                'all' => 'All volunteers',
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
            'organizations' => [
                'all' => 'All organizations',
                'approved' => 'Verified',
                'pending' => 'Awaiting verification',
                'rejected' => 'Rejected',
            ],
            default => [],
        };
    }

    private static function volunteersFor(string $audience, User $user, Collection $events, Carbon $start, Carbon $end): Collection
    {
        $eventIds = $events->pluck('id');

        $query = User::query()
            ->where('role', 'volunteer')
            ->with(['primaryOrganization', 'assignments', 'skills']);

        if ($audience === 'volunteer') {
            $query->where('id', $user->id);
        } elseif ($audience === 'organization') {
            $query->where(function ($query) use ($user, $eventIds) {
                $query->where('organization_id', $user->id)
                    ->orWhereHas('assignments', fn ($assignments) => $assignments->whereIn('event_id', $eventIds));
            });
        }

        return $query->orderBy('name')->get()->filter(function (User $volunteer) use ($start, $end, $eventIds) {
            $registered = $volunteer->created_at && $volunteer->created_at->between($start, $end);
            $participated = $volunteer->assignments->whereIn('event_id', $eventIds)->isNotEmpty();

            return $registered || $participated;
        })->values();
    }

    private static function organizationsFor(string $audience, User $user, Collection $events, Carbon $start, Carbon $end): Collection
    {
        if ($audience === 'volunteer') {
            return collect();
        }

        if ($audience === 'organization') {
            return collect([$user]);
        }

        $eventOrgIds = $events->pluck('organization_id')->filter()->unique();

        return User::where('role', 'organization')
            ->orderBy('name')
            ->get()
            ->filter(function (User $organization) use ($start, $end, $eventOrgIds) {
                $registered = $organization->created_at && $organization->created_at->between($start, $end);

                return $registered || $eventOrgIds->contains($organization->id);
            })
            ->values();
    }

    private static function cards(
        string $audience,
        string $category,
        Collection $registeredVolunteers,
        Collection $volunteers,
        Collection $registeredOrganizations,
        Collection $organizations,
        Collection $events,
        Collection $tasks,
        ?string $completionRate,
        int $tasksCompleted,
        int $tasksTotal,
        ?int $hoursUserId,
        string $purpose = 'platform',
        ?User $user = null
    ): array {
        $completedEvents = $events->where('status', 'completed')->count();
        $pendingEvents = $events->where('status', 'draft')->count();
        $overdue = $tasks->filter(fn (Task $task) => $task->isOverdue())->count();
        $hours = self::recordedHours($events, $hoursUserId);

        if ($purpose === 'organization') {
            return [
                self::card('events', 'Events', $events->count(), 'Scheduled or created in this period', 'events'),
                self::card('tasks', 'Tasks', $tasksTotal, 'Tasks on those events', 'tasks'),
                self::card('completed-tasks', 'Completed tasks', $tasksCompleted, 'Status is completed', 'completed-tasks'),
                self::card('overdue', 'Overdue tasks', $overdue, 'Open tasks past their deadline', 'overdue'),
                self::card('volunteers', 'Volunteers', $volunteers->count(), 'Members and volunteers on these events', 'volunteers'),
                self::card('hours', 'Hours contributed', number_format($hours, 2), 'Recorded on completed assignments', 'hours'),
            ];
        }

        if ($purpose === 'event') {
            $event = $events->first();

            return [
                self::card('events', 'Event status', $event?->oversightLabel() ?? '—', 'Saved event status', 'events'),
                self::card('volunteers', 'Volunteers', $event ? $event->assignments->whereIn('status', ['approved', 'submitted', 'completed'])->pluck('user_id')->unique()->count() : 0, 'Accepted assignments on this event', 'volunteers'),
                self::card('tasks', 'Tasks', $tasksTotal, 'Tasks recorded on this event', 'tasks'),
                self::card('completed-tasks', 'Completed tasks', $tasksCompleted, 'Status is completed', 'completed-tasks'),
                self::card('overdue', 'Overdue tasks', $overdue, 'Open tasks past their deadline', 'overdue'),
            ];
        }

        if ($purpose === 'task') {
            $task = $tasks->first();
            $hours = $task ? (float) $task->assignments->where('status', 'completed')->sum('hours_logged') : 0;
            $names = $task ? $task->assignments->whereIn('status', ['approved', 'submitted', 'completed'])->map(fn ($assignment) => $assignment->user->name ?? 'Former volunteer')->unique()->join(', ') : '';

            return [
                self::card('tasks', 'Task status', $task ? ($task->isOverdue() ? $task->oversightLabel().' · Overdue' : $task->oversightLabel()) : '—', 'Saved task status', 'tasks'),
                self::card('volunteers', 'Volunteer', $names !== '' ? $names : 'Unassigned', 'Accepted assignment on this task', 'volunteers'),
                self::card('events', 'Event', $task?->event->title ?? '—', $task?->event->organization->name ?? 'Organization', 'events'),
                self::card('hours', 'Hours contributed', number_format($hours, 2), 'Recorded on a completed assignment', 'hours'),
            ];
        }

        if ($purpose === 'volunteer' && $hoursUserId) {
            $mine = $events->flatMap(fn (Event $event) => $event->assignments->where('user_id', $hoursUserId));

            return [
                self::card('events', 'Events joined', $events->count(), 'Events with this volunteer in the period', 'events'),
                self::card('assignments', 'Assignments', $mine->count(), 'Assignment records', 'assignments'),
                self::card('completed-assignments', 'Completed assignments', $mine->where('status', 'completed')->count(), 'Assignment status is completed', 'completed-assignments'),
                self::card('hours', 'Hours contributed', number_format($hours, 2), 'Recorded after the organization approves completion', 'hours'),
            ];
        }

        if ($audience === 'volunteer') {
            $mine = $events->flatMap(fn (Event $event) => $event->assignments->where('user_id', $hoursUserId));

            return [
                self::card('events', 'Events joined', $events->count(), 'Events with your assignment in this period', 'events'),
                self::card('assignments', 'Assignments', $mine->count(), 'Your assignment records', 'assignments'),
                self::card('completed-assignments', 'Completed assignments', $mine->where('status', 'completed')->count(), 'Assignment status is completed', 'completed-assignments'),
                self::card('hours', 'Hours contributed', number_format($hours, 2), 'Recorded after the organization approves completion', 'hours'),
            ];
        }

        if ($category === 'events') {
            return [
                self::card('events', 'Events created', $events->count(), 'Records in this period', 'events'),
                self::card('upcoming', 'Upcoming', $events->filter(fn (Event $event) => $event->oversightStatus() === 'upcoming')->count(), 'Published, start date still ahead', 'upcoming'),
                self::card('ongoing', 'Ongoing', $events->filter(fn (Event $event) => $event->oversightStatus() === 'ongoing')->count(), 'Published and already started', 'ongoing'),
                self::card('completed-events', 'Completed', $completedEvents, 'Status is completed', 'completed-events'),
                self::card('cancelled', 'Cancelled', $events->where('status', 'cancelled')->count(), 'Status is cancelled', 'cancelled'),
                self::card('pending-events', 'Pending approval', $pendingEvents, 'Status is draft', 'pending-events'),
                self::card('approved', 'Approved', $events->where('status', 'published')->count(), 'Status is published', 'approved'),
            ];
        }

        if ($category === 'tasks') {
            return [
                self::card('tasks', 'Tasks created', $tasksTotal, 'Tasks on events in this period', 'tasks'),
                self::card('pending', 'Pending', $tasks->where('status', 'pending')->count(), 'Status is pending', 'pending'),
                self::card('assigned', 'Assigned', $tasks->where('status', 'assigned')->count(), 'Status is assigned', 'assigned'),
                self::card('in-progress', 'In progress', $tasks->where('status', 'in_progress')->count(), 'Status is in progress', 'in-progress'),
                self::card('completed-tasks', 'Completed', $tasksCompleted, 'Status is completed', 'completed-tasks'),
                self::card('overdue', 'Overdue', $overdue, 'Open tasks whose deadline has passed', 'overdue'),
                self::card('completion-rate', 'Completion rate', $completionRate ?? '—', $tasksTotal > 0 ? $tasksCompleted.' of '.$tasksTotal.' tasks' : 'No tasks in this period', 'completed-tasks'),
            ];
        }

        if ($category === 'volunteers') {
            return [
                self::card('volunteers', 'Registered volunteers', $registeredVolunteers->count(), 'Accounts created in this period', 'volunteers'),
                self::card('active', 'Active', $registeredVolunteers->where('availability', 'active')->count(), 'Availability is active', 'active'),
                self::card('inactive', 'Inactive', $registeredVolunteers->where('availability', 'inactive')->count(), 'Availability is inactive', 'inactive'),
                self::card('completed-assignments', 'Completed assignments', self::completedAssignmentCount($events), 'Assignment status is completed', 'completed-assignments'),
                self::card('hours', 'Hours contributed', number_format($hours, 2), 'Recorded on completed assignments', 'hours'),
            ];
        }

        if ($category === 'organizations') {
            return [
                self::card('organizations', 'Registered organizations', $registeredOrganizations->count(), 'Accounts created in this period', 'organizations'),
                self::card('verified', 'Verified', $registeredOrganizations->where('status', 'approved')->count(), 'Status is approved', 'verified'),
                self::card('unverified', 'Awaiting verification', $registeredOrganizations->where('status', 'pending')->count(), 'Status is pending', 'unverified'),
                self::card('engaged', 'With activity', $organizations->count(), 'Registered in this period or with an event in it', 'engaged'),
            ];
        }

        return [
            self::card('volunteers', 'Volunteers', $registeredVolunteers->count(), 'Registered in this period', 'volunteers'),
            self::card('organizations', 'Organizations', $audience === 'organization' ? 1 : $registeredOrganizations->count(), $audience === 'organization' ? ($user->name ?? 'This organization') : 'Registered in this period', $audience === 'organization' ? 'engaged' : 'organizations'),
            self::card('events', 'Events', $events->count(), 'Scheduled or created in this period', 'events'),
            self::card('tasks', 'Tasks', $tasksTotal, 'Tasks on those events', 'tasks'),
            self::card('completed-events', 'Completed events', $completedEvents, 'Status is completed', 'completed-events'),
            self::card('completed-tasks', 'Completed tasks', $tasksCompleted, 'Status is completed', 'completed-tasks'),
            self::card('pending-events', 'Pending event approvals', $pendingEvents, 'Status is draft', 'pending-events'),
            self::card('overdue', 'Overdue tasks', $overdue, 'Open tasks past their deadline', 'overdue'),
        ];
    }

    private static function card(string $key, string $label, int|string $value, string $hint, string $detail): array
    {
        return compact('key', 'label', 'value', 'hint', 'detail');
    }

    private static function sections(
        string $audience,
        string $category,
        Collection $eventRows,
        Collection $taskRows,
        Collection $volunteerRows,
        Collection $organizationRows,
        array $skillRows,
        Collection $events,
        Collection $tasks,
        string $purpose = 'platform'
    ): array {
        $eventsSection = [
            'title' => 'Events',
            'headers' => $audience === 'volunteer'
                ? ['Event', 'Schedule', 'Status', $purpose === 'volunteer' ? 'Assignment' : 'Your assignment']
                : ['Event', 'Organization', 'Schedule', 'Status', 'Volunteers', 'Tasks completed'],
            'rows' => $eventRows->map(fn (array $row) => $audience === 'volunteer'
                ? [$row['title'], $row['schedule'], $row['status'], $row['own_assignment']]
                : [$row['title'], $row['organization'], $row['schedule'], $row['status'], $row['volunteers'], $row['tasks']]
            )->all(),
            'empty' => 'No events in this period.',
        ];

        $tasksSection = [
            'title' => 'Tasks',
            'headers' => ['Task', 'Event', 'Organization', 'Status', 'Deadline', 'Volunteer'],
            'rows' => $taskRows->map(fn (array $row) => [$row['title'], $row['event'], $row['organization'], $row['status'], $row['deadline'], $row['volunteer']])->all(),
            'empty' => 'No tasks in this period.',
        ];

        $byEvent = [
            'title' => 'Tasks completed per event',
            'headers' => ['Event', 'Completed tasks', 'Total tasks'],
            'rows' => $events->map(function (Event $event) {
                $total = $event->tasks->count();
                $completed = $event->tasks->where('status', 'completed')->count();

                return [$event->title, (string) $completed, (string) $total];
            })->all(),
            'empty' => 'No events in this period.',
        ];

        $byOrganization = [
            'title' => 'Tasks completed per organization',
            'headers' => ['Organization', 'Completed tasks', 'Overdue tasks', 'Total tasks'],
            'rows' => $tasks->groupBy(fn (Task $task) => $task->event->organization->name ?? 'Former organization')
                ->map(function (Collection $group, string $name) {
                    return [
                        $name,
                        (string) $group->where('status', 'completed')->count(),
                        (string) $group->filter(fn (Task $task) => $task->isOverdue())->count(),
                        (string) $group->count(),
                    ];
                })->values()->all(),
            'empty' => 'No tasks in this period.',
        ];

        $volunteersSection = [
            'title' => 'Volunteers',
            'headers' => ['Volunteer', 'Availability', 'Organization', 'Events joined', 'Completed assignments', 'Hours'],
            'rows' => $volunteerRows->map(fn (array $row) => [$row['name'], $row['availability'], $row['organization'], $row['events'], $row['completed'], $row['hours']])->all(),
            'empty' => 'No volunteers in this period.',
        ];

        $skillsSection = [
            'title' => 'Most requested skills',
            'headers' => ['Skill', 'Tasks requesting it'],
            'rows' => $skillRows,
            'empty' => 'No task skills were recorded in this period.',
        ];

        $organizationsSection = [
            'title' => 'Organizations',
            'headers' => ['Organization', 'Verification', 'Events conducted', 'Tasks completed', 'Overdue tasks', 'Volunteers engaged'],
            'rows' => $organizationRows->map(fn (array $row) => [$row['name'], $row['verification'], $row['events'], $row['completed_tasks'], $row['overdue'], $row['volunteers']])->all(),
            'empty' => 'No organizations in this period.',
        ];

        if ($purpose === 'organization') {
            return [$eventsSection, $tasksSection, $volunteersSection, $skillsSection];
        }

        if ($purpose === 'event') {
            return [$eventsSection, $tasksSection, $volunteersSection];
        }

        if ($purpose === 'task') {
            return [$tasksSection];
        }

        if ($purpose === 'volunteer') {
            return [$eventsSection, $tasksSection];
        }

        return match ($category) {
            'events' => array_values(array_filter([$eventsSection, $audience === 'admin' ? $organizationsSection : null])),
            'tasks' => array_values(array_filter([$tasksSection, $byEvent, $byOrganization])),
            'volunteers' => [$volunteersSection, $skillsSection],
            'organizations' => [$organizationsSection],
            'participation' => [$eventsSection, $tasksSection],
            default => array_values(array_filter([
                $audience === 'admin' ? $organizationsSection : $eventsSection,
                $skillsSection,
            ])),
        };
    }

    private static function detailSection(
        string $detail,
        Collection $events,
        Collection $tasks,
        Collection $registeredVolunteers,
        Collection $volunteers,
        Collection $registeredOrganizations,
        Collection $organizations,
        string $audience,
        User $user
    ): array {
        $eventSection = function (Collection $matched, string $title) use ($audience, $user) {
            $rows = $matched->map(fn (Event $event) => self::eventRow($event, $audience, $user));

            return [$title, [
                'title' => $title,
                'headers' => $audience === 'volunteer'
                    ? ['Event', 'Schedule', 'Status', 'Your assignment']
                    : ['Event', 'Organization', 'Schedule', 'Status', 'Volunteers', 'Tasks completed'],
                'rows' => $rows->map(fn (array $row) => $audience === 'volunteer'
                    ? [$row['title'], $row['schedule'], $row['status'], $row['own_assignment']]
                    : [$row['title'], $row['organization'], $row['schedule'], $row['status'], $row['volunteers'], $row['tasks']]
                )->all(),
                'empty' => 'No matching records.',
            ]];
        };

        $taskSection = function (Collection $matched, string $title) {
            $rows = $matched->map(fn (Task $task) => self::taskRow($task));

            return [$title, [
                'title' => $title,
                'headers' => ['Task', 'Event', 'Organization', 'Status', 'Deadline', 'Volunteer'],
                'rows' => $rows->map(fn (array $row) => [$row['title'], $row['event'], $row['organization'], $row['status'], $row['deadline'], $row['volunteer']])->all(),
                'empty' => 'No matching records.',
            ]];
        };

        return match ($detail) {
            'events' => $eventSection($events, 'All events in this report'),
            'upcoming' => $eventSection($events->filter(fn (Event $event) => $event->oversightStatus() === 'upcoming'), 'Upcoming events'),
            'ongoing' => $eventSection($events->filter(fn (Event $event) => $event->oversightStatus() === 'ongoing'), 'Ongoing events'),
            'completed-events' => $eventSection($events->where('status', 'completed'), 'Completed events'),
            'cancelled' => $eventSection($events->where('status', 'cancelled'), 'Cancelled events'),
            'pending-events' => $eventSection($events->where('status', 'draft'), 'Events waiting for approval'),
            'approved' => $eventSection($events->where('status', 'published'), 'Approved events'),
            'tasks' => $taskSection($tasks, 'All tasks in this report'),
            'pending' => $taskSection($tasks->where('status', 'pending'), 'Pending tasks'),
            'assigned' => $taskSection($tasks->where('status', 'assigned'), 'Assigned tasks'),
            'in-progress' => $taskSection($tasks->where('status', 'in_progress'), 'Tasks in progress'),
            'completed-tasks', 'completion-rate' => $taskSection($tasks->where('status', 'completed'), 'Completed tasks'),
            'overdue' => $taskSection($tasks->filter(fn (Task $task) => $task->isOverdue()), 'Overdue tasks'),
            'volunteers' => self::peopleSection('Volunteers registered in this period', $registeredVolunteers, $events),
            'active' => self::peopleSection('Active volunteers', $registeredVolunteers->where('availability', 'active'), $events),
            'inactive' => self::peopleSection('Inactive volunteers', $registeredVolunteers->where('availability', 'inactive'), $events),
            'organizations' => self::orgSection('Organizations registered in this period', $registeredOrganizations, $events, $tasks),
            'verified' => self::orgSection('Verified organizations', $registeredOrganizations->where('status', 'approved'), $events, $tasks),
            'unverified' => self::orgSection('Organizations awaiting verification', $registeredOrganizations->where('status', 'pending'), $events, $tasks),
            'engaged' => self::orgSection('Organizations in this report', $organizations, $events, $tasks),
            'completed-assignments' => self::assignmentSection($events, $audience === 'volunteer' ? $user->id : null, true, 'Completed assignments'),
            'assignments' => self::assignmentSection($events, $user->id, false, 'Your assignments'),
            'hours' => self::assignmentSection($events, $audience === 'volunteer' ? $user->id : null, true, 'Completed assignments with recorded hours'),
            default => [null, null],
        };
    }

    private static function peopleSection(string $title, Collection $volunteers, Collection $events): array
    {
        return [$title, [
            'title' => $title,
            'headers' => ['Volunteer', 'Availability', 'Organization', 'Events joined', 'Completed assignments', 'Hours'],
            'rows' => $volunteers->map(function (User $volunteer) use ($events) {
                $row = self::volunteerRow($volunteer, $events, 'admin', $volunteer);

                return [$row['name'], $row['availability'], $row['organization'], $row['events'], $row['completed'], $row['hours']];
            })->all(),
            'empty' => 'No matching records.',
        ]];
    }

    private static function orgSection(string $title, Collection $organizations, Collection $events, Collection $tasks): array
    {
        return [$title, [
            'title' => $title,
            'headers' => ['Organization', 'Verification', 'Events conducted', 'Tasks completed', 'Overdue tasks', 'Volunteers engaged'],
            'rows' => $organizations->map(function (User $organization) use ($events, $tasks) {
                $row = self::organizationRow($organization, $events, $tasks);

                return [$row['name'], $row['verification'], $row['events'], $row['completed_tasks'], $row['overdue'], $row['volunteers']];
            })->all(),
            'empty' => 'No matching records.',
        ]];
    }

    private static function assignmentSection(Collection $events, ?int $userId, bool $completedOnly, string $title): array
    {
        $rows = $events->flatMap(function (Event $event) use ($userId, $completedOnly) {
            return $event->assignments
                ->when($userId, fn (Collection $assignments) => $assignments->where('user_id', $userId))
                ->when($completedOnly, fn (Collection $assignments) => $assignments->where('status', 'completed'))
                ->map(function ($assignment) use ($event) {
                    $task = $event->tasks->firstWhere('id', $assignment->task_id);

                    return [
                        $assignment->user->name ?? 'Former volunteer',
                        $event->title,
                        $task->title ?? 'Event assignment',
                        ucfirst(str_replace('_', ' ', (string) $assignment->status)),
                        number_format((float) $assignment->hours_logged, 2),
                    ];
                });
        })->values()->all();

        return [$title, [
            'title' => $title,
            'headers' => ['Volunteer', 'Event', 'Task', 'Assignment status', 'Hours'],
            'rows' => $rows,
            'empty' => 'No matching records.',
        ]];
    }

    private static function eventRow(Event $event, string $audience, User $user): array
    {
        $participants = $event->assignments
            ->whereIn('status', ['approved', 'submitted', 'completed'])
            ->pluck('user_id')
            ->unique()
            ->count();
        $completedTasks = $event->tasks->where('status', 'completed')->count();
        $own = $event->assignments->firstWhere('user_id', $user->id);

        return [
            'title' => $event->title,
            'organization' => $event->organization->name ?? 'Former organization',
            'schedule' => $event->start_time->format('M j, Y').' – '.$event->end_time->format('M j, Y'),
            'status' => $event->oversightLabel(),
            'volunteers' => (string) $participants,
            'tasks' => $completedTasks.' of '.$event->tasks->count(),
            'own_assignment' => $own ? ucfirst(str_replace('_', ' ', (string) $own->status)) : '—',
        ];
    }

    private static function taskRow(Task $task): array
    {
        $names = $task->assignments
            ->whereIn('status', ['approved', 'submitted', 'completed'])
            ->map(fn ($assignment) => $assignment->user->name ?? 'Former volunteer')
            ->unique()
            ->join(', ');

        $status = $task->oversightLabel();
        if ($task->isOverdue()) {
            $status .= ' · Overdue';
        }

        return [
            'title' => $task->title,
            'event' => $task->event->title ?? 'Event',
            'organization' => $task->event->organization->name ?? 'Former organization',
            'status' => $status,
            'deadline' => $task->due_date ? $task->due_date->format('M j, Y g:i A') : 'No deadline',
            'volunteer' => $names !== '' ? $names : 'Unassigned',
        ];
    }

    private static function volunteerRow(User $volunteer, Collection $events, string $audience, User $viewer): array
    {
        $eventIds = $events->pluck('id');
        $assignments = $volunteer->assignments->whereIn('event_id', $eventIds);
        $hours = $assignments->where('status', 'completed')->sum('hours_logged');

        return [
            'name' => $volunteer->name,
            'availability' => ucfirst((string) ($volunteer->availability ?? 'active')),
            'organization' => $volunteer->primaryOrganization->name ?? '—',
            'events' => (string) $assignments->pluck('event_id')->unique()->count(),
            'completed' => (string) $assignments->where('status', 'completed')->count(),
            'hours' => number_format((float) $hours, 2),
        ];
    }

    private static function organizationRow(User $organization, Collection $events, Collection $tasks): array
    {
        $orgEvents = $events->where('organization_id', $organization->id);
        $conducted = $orgEvents->whereIn('status', ['published', 'completed'])->count();
        $orgTasks = $tasks->filter(fn (Task $task) => (int) $task->event->organization_id === (int) $organization->id);
        $volunteerIds = $orgEvents->flatMap(function (Event $event) {
            return $event->assignments
                ->whereIn('status', ['approved', 'submitted', 'completed'])
                ->pluck('user_id');
        })->unique();

        $verification = match ($organization->status) {
            'approved' => 'Verified',
            'pending' => 'Awaiting verification',
            'rejected' => 'Rejected',
            default => ucfirst((string) $organization->status),
        };

        return [
            'name' => $organization->name,
            'verification' => $verification,
            'events' => (string) $conducted,
            'completed_tasks' => (string) $orgTasks->where('status', 'completed')->count(),
            'overdue' => (string) $orgTasks->filter(fn (Task $task) => $task->isOverdue())->count(),
            'volunteers' => (string) $volunteerIds->count(),
        ];
    }

    private static function skillRows(Collection $tasks): array
    {
        $counts = [];
        foreach ($tasks as $task) {
            foreach ($task->skills as $skill) {
                $counts[$skill->name] = ($counts[$skill->name] ?? 0) + 1;
            }
        }
        arsort($counts);

        return collect($counts)->map(fn ($count, $name) => [$name, (string) $count])->values()->all();
    }

    private static function monthly(Carbon $start, Carbon $end, Collection $events, Collection $signups, ?int $attendanceUserId = null, ?Collection $attendanceTaskIds = null): array
    {
        $labels = [];
        $eventCounts = [];
        $attendance = [];
        $signupCounts = [];
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->min(Carbon::now()->endOfMonth());
        $guard = 0;

        while ($cursor->lte($last) && $guard < 18) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();
            $monthEvents = $events->filter(fn (Event $event) => $event->start_time && $event->start_time->between($monthStart, $monthEnd));
            $labels[] = $cursor->format('M Y');
            $eventCounts[] = $monthEvents->count();
            $attendance[] = $monthEvents->flatMap(function (Event $event) use ($attendanceUserId, $attendanceTaskIds) {
                return $event->assignments
                    ->whereIn('status', ['approved', 'submitted', 'completed'])
                    ->when($attendanceUserId, fn (Collection $assignments) => $assignments->where('user_id', $attendanceUserId))
                    ->when($attendanceTaskIds, fn (Collection $assignments) => $assignments->whereIn('task_id', $attendanceTaskIds))
                    ->pluck('user_id');
            })->unique()->count();
            $signupCounts[] = $signups->filter(fn (User $volunteer) => $volunteer->created_at && $volunteer->created_at->between($monthStart, $monthEnd))->count();
            $cursor->addMonth();
            $guard++;
        }

        return [$labels, $eventCounts, $attendance, $signupCounts];
    }

    private static function recordedHours(Collection $events, ?int $userId): float
    {
        return (float) $events->flatMap(function (Event $event) use ($userId) {
            return $event->assignments
                ->when($userId, fn (Collection $assignments) => $assignments->where('user_id', $userId))
                ->where('status', 'completed');
        })->sum('hours_logged');
    }

    private static function completedAssignmentCount(Collection $events): int
    {
        return $events->flatMap(fn (Event $event) => $event->assignments->where('status', 'completed'))->count();
    }

    private static function eventStatusKey(Event $event): string
    {
        return $event->oversightStatus();
    }

    private static function taskStatusKey(Task $task, string $status): string
    {
        if ($status === 'overdue' && $task->isOverdue()) {
            return 'overdue';
        }

        return $task->oversightStatus();
    }

    private static function eventMatches(Event $event, string $needle): bool
    {
        if (self::contains($event->title, $needle) || self::contains($event->location, $needle) || self::contains($event->organization->name ?? '', $needle)) {
            return true;
        }

        foreach ($event->tasks as $task) {
            if (self::contains($task->title, $needle)) {
                return true;
            }
        }

        foreach ($event->assignments as $assignment) {
            if (self::contains($assignment->user->name ?? '', $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function contains(?string $value, string $needle): bool
    {
        return $needle === '' || str_contains(mb_strtolower((string) $value), $needle);
    }
}
