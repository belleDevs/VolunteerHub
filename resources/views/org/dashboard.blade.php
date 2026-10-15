<x-layout.org :active="$section">
    @if($section === 'dashboard')
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Organization Dashboard</h1>
                <p class="mt-1 text-sm text-slate-500">Welcome back! Here's your organization's overview.</p>
            </div>

            @php
                $summaryCards = [
                    ['Total Events', $myEventsCount, 'All event records', 'fa-calendar'],
                    ['Upcoming Events', $upcomingEventsCount, 'Start date still ahead', 'fa-calendar-day'],
                    ['Active Tasks', $activeTasksCount, 'Not yet completed', 'fa-list-check'],
                    ['Assigned Volunteers', $assignedVolunteersCount, 'Across your events', 'fa-user-group'],
                    ['Completed Tasks', $completedTasksCount, $completionRate.'% completion', 'fa-circle-check'],
                    ['Pending Applications', $taskApplications->count(), 'Awaiting review', 'fa-user-clock'],
                ];
            @endphp
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($summaryCards as [$label, $value, $caption, $icon])
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $label }}</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-jci-blue dark:bg-sky-950">
                                <i class="fa-solid {{ $icon }} text-sm"></i>
                            </span>
                        </div>
                        <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ number_format($value) }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $caption }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Upcoming Events</h2>
                        <a href="{{ route('org.dashboard', ['section' => 'events']) }}" class="text-xs font-semibold text-jci-blue hover:text-jci-dark">View all</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse($dashboardUpcoming as $event)
                            @php
                                $registered = $event->assignments->pluck('user_id')->filter()->unique()->count();
                                $statusLabel = $event->status === 'draft' ? 'Pending Approval' : $event->oversightLabel();
                                $statusClass = match ($statusLabel) {
                                    'Pending Approval' => 'bg-amber-100 text-amber-700',
                                    'Upcoming' => 'bg-blue-50 text-jci-blue',
                                    'Ongoing' => 'bg-emerald-100 text-emerald-700',
                                    default => 'bg-slate-100 text-slate-500',
                                };
                            @endphp
                            <article class="rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $event->title }}</h3>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ \App\Support\OrgPreferences::format($event->start_time, $organization, true) }} · {{ $event->location }}</p>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $registered }}@if($event->capacity) / {{ $event->capacity }}@endif volunteers registered
                                </p>
                                <button type="button"
                                        class="mt-3 text-xs font-semibold text-jci-blue hover:text-jci-dark"
                                        data-title="{{ $event->title }}"
                                        data-when="{{ $event->start_time->format('F j, Y g:i A') }} – {{ $event->end_time->format('F j, Y g:i A') }}"
                                        data-where="{{ $event->location }}"
                                        data-status="{{ $statusLabel }}"
                                        data-volunteers="{{ $registered }}@if($event->capacity) / {{ $event->capacity }}@endif"
                                        data-note="{{ $event->status === 'draft' ? 'This event is pending admin approval. Your organization cannot approve it.' : \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $event->description), 280) }}"
                                        onclick="openEventDetails(this)">View Details</button>
                            </article>
                        @empty
                            <div class="py-10 text-center">
                                <p class="text-sm text-slate-400">No upcoming events yet.</p>
                                <button type="button" onclick="openCreateEvent()" class="mt-3 text-xs font-semibold text-jci-blue hover:text-jci-dark">Create Event</button>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Task Progress</h2>
                    <p class="mt-1 text-xs text-slate-400">Tasks by status, from your events</p>
                    @php
                        $bars = [
                            ['Pending', $taskStatusCounts['pending'], 'bg-slate-300'],
                            ['Assigned', $taskStatusCounts['assigned'], 'bg-jci-blue'],
                            ['In Progress', $taskStatusCounts['in_progress'], 'bg-amber-400'],
                            ['Completed', $taskStatusCounts['completed'], 'bg-emerald-500'],
                            ['Overdue', $taskStatusCounts['overdue'], 'bg-rose-500'],
                        ];
                        $barMax = max(1, collect($bars)->max(fn ($bar) => $bar[1]));
                    @endphp
                    @if($dashboardTasks->count() === 0)
                        <p class="py-12 text-center text-sm text-slate-400">No tasks recorded yet.</p>
                    @else
                        <div class="mt-4 flex h-40 items-end gap-2">
                            @foreach($bars as [$barLabel, $barValue, $barColor])
                                <div class="flex h-full flex-1 flex-col items-center justify-end">
                                    <span class="mb-1 text-[10px] font-semibold text-slate-500">{{ $barValue }}</span>
                                    <div class="w-full rounded-t-lg {{ $barColor }}" style="height: {{ max(6, (int) round(($barValue / $barMax) * 100)) }}%"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2 grid grid-cols-5 gap-1 text-center text-[9px] font-medium leading-tight text-slate-400">
                            @foreach($bars as [$barLabel])
                                <span>{{ $barLabel }}</span>
                            @endforeach
                        </div>
                        <p class="mt-3 text-[10px] text-slate-400">Overdue is a deadline flag. Those tasks also stay in their saved status.</p>
                    @endif
                </section>
            </div>

            <section id="task-overview" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Task Overview</h2>
                        <p class="mt-1 text-xs text-slate-400">Open tasks on your events, with deadlines and assigned volunteers.</p>
                    </div>
                    <a href="{{ route('org.dashboard', ['section' => 'tasks']) }}" class="rounded-xl bg-jci-blue px-3.5 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Manage Tasks</a>
                </div>
                @if($completionReviews->isNotEmpty())
                    <a href="{{ route('org.dashboard', ['section' => 'certificates']) }}#submitted-tasks" class="mt-3 block rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                        {{ $completionReviews->count() }} submitted {{ $completionReviews->count() === 1 ? 'task is' : 'tasks are' }} waiting for completion verification.
                    </a>
                @endif
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($openDashboardTasks as $task)
                        <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $task->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $task->event->title ?? 'Event' }} · Due {{ $task->due_date ? \App\Support\OrgPreferences::format($task->due_date, $organization, true) : 'no deadline' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $task->assignedVolunteerNames() }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if($task->isOverdue())
                                    <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-semibold text-rose-700">Overdue</span>
                                @endif
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-semibold text-jci-blue">{{ $task->oversightLabel() }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-400">No open tasks. Create one and link it to an event.</p>
                    @endforelse
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Volunteer Overview</h2>
                        <a href="{{ route('org.dashboard', ['section' => 'volunteers']) }}" class="rounded-xl bg-jci-blue px-3.5 py-2 text-xs font-semibold text-white hover:bg-jci-dark">View Volunteers</a>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                            <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ $assignedVolunteersCount }}</p>
                            <p class="mt-1 text-[10px] uppercase tracking-wider text-slate-400">Assigned</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                            <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ $taskApplications->count() }}</p>
                            <p class="mt-1 text-[10px] uppercase tracking-wider text-slate-400">New applications</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                            <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ $awaitingAssignmentCount }}</p>
                            <p class="mt-1 text-[10px] uppercase tracking-wider text-slate-400">Awaiting assignment</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Recently joined</p>
                        <div class="mt-2 space-y-2">
                            @forelse($recentVolunteers as $member)
                                @php
                                    $availabilityLabel = $member->availability === 'inactive' ? 'Unavailable' : ($member->status === 'pending' ? 'Pending' : 'Available');
                                @endphp
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $member->name }}</p>
                                        <p class="truncate text-[11px] text-slate-400">{{ $member->skills->pluck('name')->take(3)->join(', ') ?: 'No skills listed' }}</p>
                                    </div>
                                    <span class="shrink-0 text-[11px] font-semibold text-jci-blue">{{ $availabilityLabel }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-slate-400">No volunteers have joined this organization yet.</p>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Activities</h2>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse($recentActivities as $activity)
                            <div class="border-b border-slate-100 pb-3 last:border-b-0 last:pb-0 dark:border-slate-800">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $activity['title'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $activity['at']?->diffForHumans() }}@if($activity['detail']) · {{ $activity['detail'] }}@endif</p>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-slate-400">No recorded activity for this organization yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Notifications & Reminders</h2>
                    <a href="{{ route('org.dashboard', ['section' => 'notifications']) }}" class="text-xs font-semibold text-jci-blue hover:text-jci-dark">View all</a>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                    @forelse($reminders as $reminder)
                        @php
                            $tone = match ($reminder['tone']) {
                                'rose' => 'border-rose-100 bg-rose-50/70',
                                'amber' => 'border-amber-100 bg-amber-50/70',
                                'blue' => 'border-blue-100 bg-blue-50/60',
                                default => 'border-slate-100 bg-slate-50',
                            };
                        @endphp
                        <article class="rounded-xl border px-4 py-3 {{ $tone }}">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-800">{{ $reminder['title'] }}</p>
                                <span class="shrink-0 text-[11px] text-slate-400">{{ $reminder['when'] }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-600">{{ $reminder['detail'] }}</p>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-400 md:col-span-2">No alerts right now.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @elseif($section === 'events')
        <div class="space-y-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Events</h1>
                    <p class="mt-1 text-sm text-slate-500">Create and manage volunteering events.</p>
                </div>
                <button type="button" onclick="openCreateEvent()" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-sky-500/20 hover:bg-jci-dark">
                    <i class="fa-solid fa-plus text-xs"></i> Create Event
                </button>
            </div>

            <label class="block">
                <span class="sr-only">Search events</span>
                <input id="org-event-search" type="search" placeholder="Search events..." class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
            </label>

            <div class="space-y-3">
                @forelse($events->sortByDesc('start_time') as $event)
                    @php
                        $eventStatusLabel = $event->status === 'draft' ? 'Pending Approval' : $event->oversightLabel();
                        $eventStatusClass = match ($eventStatusLabel) {
                            'Pending Approval' => 'bg-amber-100 text-amber-800',
                            'Upcoming' => 'bg-jci-blue text-white',
                            'Ongoing' => 'bg-emerald-600 text-white',
                            'Cancelled' => 'bg-rose-100 text-rose-700',
                            default => 'bg-slate-100 text-slate-600',
                        };
                        $eventSearch = strtolower($event->title.' '.$event->description.' '.$event->location.' '.$eventStatusLabel);
                        $eventPayload = [
                            'updateUrl' => route('org.events.update', $event),
                            'title' => $event->title,
                            'location' => $event->location,
                            'start' => $event->start_time->format('Y-m-d\TH:i'),
                            'end' => $event->end_time->format('Y-m-d\TH:i'),
                            'capacity' => $event->capacity,
                            'description' => $event->description,
                            'status' => $event->status,
                        ];
                    @endphp
                    <article data-event-card data-search="{{ $eventSearch }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $event->title }}</h2>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $eventStatusClass }}">{{ $eventStatusLabel }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $event->description }}</p>
                                <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400">
                                    <span><i class="fa-regular fa-clock mr-1"></i>{{ $event->start_time->format('M j, Y') }} · {{ $event->start_time->format('g:i A') }}</span>
                                    <span><i class="fa-solid fa-location-dot mr-1"></i>{{ $event->location }}</span>
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <script type="application/json" id="event-payload-{{ $event->id }}">@json($eventPayload)</script>
                                <button type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                        data-event-id="{{ $event->id }}"
                                        onclick="openEditEvent(this)">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>
                                <form action="{{ route('org.events.destroy', $event) }}" method="POST" onsubmit="return confirm('Delete this event? Its tasks will be removed with it.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:border-rose-300 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        <i class="fa-regular fa-trash-can"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No events yet</p>
                        <p class="mt-1 text-xs text-slate-400">Create an event to send it for admin approval.</p>
                    </div>
                @endforelse
                <p id="org-event-search-empty" class="hidden rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900">No events match that search.</p>
            </div>
        </div>
    @elseif($section === 'tasks')
        @php
            $roleChoices = \App\Support\RoleCatalog::roles();
            $taskEvents = $events->sortByDesc('start_time')->values();
        @endphp
        <div class="space-y-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Task Assignment</h1>
                    <p class="mt-1 text-sm text-slate-500">Assign a volunteer whose skills match the role. Reassign to see every volunteer, with skill matches listed first.</p>
                </div>
                <button type="button" onclick="document.getElementById('modal-new-role').classList.remove('hidden')" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-sky-500/20 hover:bg-jci-dark">
                    <i class="fa-solid fa-plus text-xs"></i> New Task
                </button>
            </div>

            @forelse($taskEvents as $event)
                @php
                    $eventTasks = $event->tasks->sortBy(fn ($task) => $task->due_date?->timestamp ?? $event->start_time?->timestamp ?? 0)->values();
                    $eventStatusLabel = $event->status === 'draft' ? 'Pending Approval' : $event->oversightLabel();
                @endphp
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $event->title }}</h2>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $event->start_time->format('M j, Y') }} · {{ $event->location }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{{ $eventStatusLabel }}</span>
                    </div>
                    @if($eventTasks->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-slate-400">No roles added for this event yet. Add only the roles this event needs.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="text-xs text-slate-400">
                                    <tr class="border-b border-slate-100 dark:border-slate-800">
                                        <th class="px-5 py-3 font-medium">Task</th>
                                        <th class="px-5 py-3 font-medium">Assigned Volunteer</th>
                                        <th class="px-5 py-3 font-medium">Deadline</th>
                                        <th class="px-5 py-3 font-medium">Status</th>
                                        <th class="px-5 py-3 font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($eventTasks as $task)
                                        @php
                                            $assignment = $event->assignments->first(fn ($row) => (int) $row->task_id === (int) $task->id && $row->status !== 'cancelled');
                                            $assignedName = $assignment?->user?->name;
                                            if ($assignment && $assignment->status === 'pending' && $assignedName) {
                                                $assignedName .= ' · waiting to accept';
                                            }
                                            $statusLabel = $task->oversightLabel();
                                            $statusClass = match ($statusLabel) {
                                                'Completed' => 'bg-slate-900 text-white',
                                                'In Progress' => 'bg-slate-100 text-slate-700',
                                                'Assigned' => 'border border-slate-200 bg-white text-slate-700',
                                                'Cancelled' => 'bg-rose-100 text-rose-700',
                                                default => 'bg-slate-100 text-slate-500',
                                            };
                                        @endphp
                                        <tr class="border-b border-slate-100 last:border-b-0 dark:border-slate-800">
                                            <td class="px-5 py-4 font-medium text-slate-800 dark:text-slate-100">
                                                {{ $task->title }}
                                                @php
                                                    $relatedSkills = collect(\App\Support\RoleCatalog::relatedSkillNames($task->title));
                                                    if ($relatedSkills->isEmpty()) {
                                                        $relatedSkills = $task->skills->pluck('name');
                                                    }
                                                @endphp
                                                @if($relatedSkills->isNotEmpty())
                                                    <p class="mt-0.5 text-[11px] font-normal text-slate-400">Matches {{ $relatedSkills->join(', ') }}</p>
                                                @endif
                                            </td>
                                            <td data-assigned-name data-saved-name="{{ $assignedName ?: 'Unassigned' }}" class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ $assignedName ?: 'Unassigned' }}</td>
                                            <td class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ ($task->due_date ?? $event->start_time)->format('M j, Y') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $roster = $assignableVolunteers
                                                        ->filter(function ($volunteer) use ($assignment) {
                                                            return (int) $volunteer->organization_id === (int) Auth::id()
                                                                || ($assignment && (int) $assignment->user_id === (int) $volunteer->id);
                                                        })
                                                        ->map(function ($volunteer) use ($task) {
                                                            $matchedSkills = $task->matchedSkillsFor($volunteer);

                                                            return [
                                                                'volunteer' => $volunteer,
                                                                'skills' => $matchedSkills->pluck('name')->join(', '),
                                                                'matched' => $matchedSkills->isNotEmpty(),
                                                            ];
                                                        })
                                                        ->sort(function ($left, $right) {
                                                            if ($left['matched'] !== $right['matched']) {
                                                                return $left['matched'] ? -1 : 1;
                                                            }

                                                            return strcasecmp($left['volunteer']->name, $right['volunteer']->name);
                                                        })
                                                        ->values();
                                                    $matchedVolunteers = $roster->where('matched', true)->values();
                                                    $otherVolunteers = $roster->where('matched', false)->values();
                                                    $canReassign = $assignment && !in_array($task->status, ['completed', 'cancelled'], true);
                                                @endphp
                                                @if($canReassign)
                                                    <div>
                                                        <button type="button" onclick="openReassign(this)" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200">Reassign</button>
                                                        <form action="{{ route('org.assignments.store') }}" method="POST" data-reassign-form class="hidden items-center gap-2" onsubmit="return !this.querySelector('[data-assign-ok]').disabled">
                                                            @csrf
                                                            <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                            <select name="user_id" data-saved="{{ $assignment->user_id }}" onchange="previewAssignment(this)" class="max-w-[16rem] rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                                                <option value="">Choose a volunteer</option>
                                                                @if($matchedVolunteers->isNotEmpty())
                                                                    <optgroup label="Matched skills">
                                                                        @foreach($matchedVolunteers as $match)
                                                                            <option value="{{ $match['volunteer']->id }}" data-name="{{ $match['volunteer']->name }}" @selected((int) $assignment->user_id === (int) $match['volunteer']->id)>{{ $match['volunteer']->name }} · {{ $match['skills'] }}</option>
                                                                        @endforeach
                                                                    </optgroup>
                                                                @endif
                                                                @if($otherVolunteers->isNotEmpty())
                                                                    <optgroup label="Other volunteers">
                                                                        @foreach($otherVolunteers as $match)
                                                                            <option value="{{ $match['volunteer']->id }}" data-name="{{ $match['volunteer']->name }}" @selected((int) $assignment->user_id === (int) $match['volunteer']->id)>{{ $match['volunteer']->name }}</option>
                                                                        @endforeach
                                                                    </optgroup>
                                                                @endif
                                                            </select>
                                                            <button type="submit" data-assign-ok disabled class="rounded-xl bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">OK</button>
                                                        </form>
                                                    </div>
                                                @elseif(!$assignment)
                                                    @if($matchedVolunteers->isEmpty())
                                                        <span class="text-xs text-slate-400">No volunteer matches this role</span>
                                                    @else
                                                        <form action="{{ route('org.assignments.store') }}" method="POST" class="flex items-center gap-2" onsubmit="return !this.querySelector('[data-assign-ok]').disabled">
                                                            @csrf
                                                            <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                            <select name="user_id" data-saved="" onchange="previewAssignment(this)" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                                                <option value="">Assign volunteer</option>
                                                                @foreach($matchedVolunteers as $match)
                                                                    <option value="{{ $match['volunteer']->id }}" data-name="{{ $match['volunteer']->name }}">{{ $match['volunteer']->name }} · {{ $match['skills'] }}</option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit" data-assign-ok disabled class="rounded-xl bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">OK</button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No events yet</p>
                    <p class="mt-1 text-xs text-slate-400">Create an event first, then add the roles it needs.</p>
                </div>
            @endforelse

            @if($taskApplications->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Pending applications</h2>
                    <p class="mt-1 text-xs text-slate-400">Volunteers who asked for a role. Approving assigns them to that task.</p>
                    <div class="mt-4 space-y-3">
                        @foreach($taskApplications as $application)
                            <div class="flex flex-col gap-3 rounded-xl border border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $application->user->name ?? 'Volunteer' }}</p>
                                    <p class="text-xs text-slate-400">{{ $application->task->title ?? 'Role' }} · {{ $application->event->title ?? 'Event' }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <form action="{{ route('org.applications.approve', $application->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Approve</button>
                                    </form>
                                    <form action="{{ route('org.applications.reject', $application->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="feedback" value="This application was not selected for the role.">
                                        <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-rose-300 hover:text-rose-600">Reject</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <x-ui.modal id="modal-new-role" title="New Task">
            @if($taskEvents->where('status', '!=', 'cancelled')->isEmpty())
                <p class="text-sm text-slate-500">Create an event first, then add the roles it needs.</p>
            @else
                <form action="{{ route('org.tasks.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Event</label>
                        <select name="event_id" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                            @foreach($taskEvents->where('status', '!=', 'cancelled') as $eventOption)
                                <option value="{{ $eventOption->id }}">{{ $eventOption->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Role</label>
                        <select name="role" required onchange="toggleCustomRole(this)" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                            <option value="" selected disabled>Choose a role for this event</option>
                            @foreach($roleChoices as $roleName => $relatedSkillNames)
                                <option value="{{ $roleName }}" data-skills="{{ implode(', ', $relatedSkillNames) }}">{{ $roleName }}</option>
                            @endforeach
                            <option value="__custom__">Add a role</option>
                        </select>
                    </div>
                    <div id="custom-role-field" class="hidden">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">New role</label>
                        <input id="custom-role-input" name="custom_role" type="text" maxlength="255" placeholder="Role this event needs" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Deadline</label>
                        <input name="due_date" type="datetime-local" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                    </div>
                    <p id="role-skill-hint" class="text-[10px] text-slate-400">Add only the roles this event needs. Each role is matched to the skills it relates to, such as Public Relations.</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('modal-new-role').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                        <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Add role</button>
                    </div>
                </form>
            @endif
        </x-ui.modal>
    @elseif($section === 'notifications')
        @php
            $noticeFilters = [
                'all' => 'All notifications',
                'unread' => 'Unread',
                'event' => 'Events',
                'task' => 'Tasks',
                'volunteer' => 'Volunteers',
                'account' => 'Account',
                'certificate' => 'Certificates',
                'system' => 'System',
            ];
        @endphp
        <div class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Notifications</h1>
                    <p class="mt-1 text-sm text-slate-500">Updates for this organization, recorded when something actually happens.</p>
                </div>
                <form action="{{ route('org.notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200">Mark all as read</button>
                </form>
            </div>
            <form method="GET" action="{{ route('org.dashboard') }}" class="flex flex-col gap-3 sm:flex-row">
                <input type="hidden" name="section" value="notifications">
                @if($orgNoticeBox === 'unread')
                    <input type="hidden" name="box" value="unread">
                @endif
                @if($orgNoticeCategory !== '')
                    <input type="hidden" name="category" value="{{ $orgNoticeCategory }}">
                @endif
                <input name="q" value="{{ $orgNoticeSearch }}" type="search" placeholder="Search title, message, or related record" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-900">
            </form>
            <div class="flex flex-wrap gap-2">
                @foreach($noticeFilters as $key => $label)
                    @php
                        $activeFilter = $key === 'all'
                            ? ($orgNoticeBox === 'all' && $orgNoticeCategory === '')
                            : ($key === 'unread' ? $orgNoticeBox === 'unread' && $orgNoticeCategory === '' : $orgNoticeCategory === $key);
                        $href = route('org.dashboard', array_filter([
                            'section' => 'notifications',
                            'box' => $key === 'unread' ? 'unread' : null,
                            'category' => in_array($key, ['all', 'unread'], true) ? null : $key,
                            'q' => $orgNoticeSearch !== '' ? $orgNoticeSearch : null,
                        ]));
                    @endphp
                    <a href="{{ $href }}" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $activeFilter ? 'bg-jci-blue text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-jci-blue dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="space-y-3">
                @forelse($orgNotices as $notice)
                    @php
                        $payload = $notice->payload;
                        $unread = $notice->read_at === null;
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $payload['title'] ?? 'Notification' }}</h2>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $unread ? 'bg-jci-blue text-white' : 'bg-slate-100 text-slate-500' }}">{{ $unread ? 'Unread' : 'Read' }}</span>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $payload['message'] ?? '' }}</p>
                        <p class="mt-3 text-xs text-slate-400">{{ \App\Support\OrgPreferences::format(\Carbon\Carbon::parse($notice->created_at), $organization, true) }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $notice->category_label }}@if(!empty($payload['related'])) · Related: {{ $payload['related'] }}@endif</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if(!empty($payload['url']))
                                <a href="{{ route('org.notifications.open', $notice->id) }}" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">{{ $payload['action'] ?? 'Open' }}</a>
                            @endif
                            @if($unread)
                                <form action="{{ route('org.notifications.read', $notice->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200">Mark as read</button>
                                </form>
                            @endif
                            <form action="{{ route('org.notifications.dismiss', $notice->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-500 hover:border-rose-300 hover:text-rose-600 dark:border-slate-700">Dismiss</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No notifications yet</p>
                        <p class="mt-1 text-xs text-slate-400">A notice appears here when an event is approved, a volunteer applies, a task is due, or a certificate is issued.</p>
                    </div>
                @endforelse
            </div>
            <details class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <summary class="cursor-pointer text-sm font-semibold text-slate-800 dark:text-slate-100">Message volunteers</summary>
                <div class="mt-4 grid items-start gap-5 lg:grid-cols-2">
                <form action="{{ route('org.notifications.send') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    @csrf
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Send Notification</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="notice-type" class="mb-1.5 block text-sm text-slate-600 dark:text-slate-300">Type</label>
                            <select id="notice-type" name="notice_type" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                                <option value="event" @selected(old('notice_type', 'event') === 'event')>Event</option>
                                <option value="task" @selected(old('notice_type') === 'task')>Task</option>
                                <option value="reminder" @selected(old('notice_type') === 'reminder')>Reminder</option>
                            </select>
                        </div>
                        <div>
                            <label for="notice-title" class="mb-1.5 block text-sm text-slate-600 dark:text-slate-300">Title</label>
                            <input id="notice-title" name="title" type="text" required maxlength="120" value="{{ old('title') }}" placeholder="e.g. Cleanup reminder" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label for="notice-message" class="mb-1.5 block text-sm text-slate-600 dark:text-slate-300">Message</label>
                            <textarea id="notice-message" name="message" required maxlength="1000" rows="4" placeholder="Write your message..." class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">{{ old('message') }}</textarea>
                        </div>
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-jci-blue py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">
                            <i class="fa-solid fa-paper-plane"></i> Send
                        </button>
                        <p class="text-[11px] text-slate-400">Each notice is emailed to the address saved on that volunteer's account, along with their new events and assigned tasks.</p>
                        <p class="text-[11px] text-slate-400">
                            @if($noticeRecipients->isEmpty())
                                No volunteer email is on file yet.
                            @else
                                Registered emails:
                                @foreach($noticeRecipients as $recipient)
                                    {{ $recipient->name }} ({{ $recipient->email }}){{ $loop->last ? '.' : ', ' }}
                                @endforeach
                            @endif
                        </p>
                    </div>
                </form>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">History</h2>
                    </div>
                    @if($sentNotices->isEmpty())
                        <p class="px-5 py-12 text-center text-sm text-slate-400">No notifications sent yet.</p>
                    @else
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($sentNotices as $notice)
                                <div class="flex items-start gap-3 px-5 py-4">
                                    <span class="mt-0.5 shrink-0 rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-jci-blue">{{ $notice->typeLabel() }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-slate-800 dark:text-slate-100">{{ $notice->title }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $notice->message }}</p>
                                        <p class="mt-1 text-[11px] text-slate-400">Platform {{ $notice->recipients }} · Email {{ $notice->emails_sent }}</p>
                                    </div>
                                    <span class="shrink-0 text-[11px] text-slate-400">{{ $notice->created_at->diffForHumans() }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            </details>
        </div>
    @elseif($section === 'volunteers')
        <div class="space-y-5">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Volunteers</h1>
                <p class="mt-1 text-sm text-slate-500">Browse and manage your volunteers.</p>
            </div>
            <input id="org-volunteer-search" type="search" placeholder="Search volunteers..." class="w-full max-w-md rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            @if($organizationVolunteers->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No volunteers yet</p>
                    <p class="mt-1 text-xs text-slate-400">Volunteers who join your organization or take a role on your events will appear here.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($organizationVolunteers as $member)
                        @php
                            $parts = preg_split('/\s+/', trim($member->name)) ?: [];
                            $initials = strtoupper(collect($parts)->filter()->take(2)->map(fn ($part) => substr($part, 0, 1))->join(''));
                            $eventCount = $member->assignments->pluck('event_id')->unique()->count();
                            $availabilityLabel = $member->status === 'pending'
                                ? 'Pending'
                                : ($member->availability === 'inactive' ? 'Unavailable' : 'Available');
                            $availabilityClass = $availabilityLabel === 'Available'
                                ? 'bg-jci-blue text-white'
                                : 'bg-slate-100 text-slate-500';
                            $eventGroups = $member->assignments->groupBy('event_id')->map(function ($rows) {
                                $event = $rows->first()->event;
                                return [
                                    'title' => $event->title ?? 'Event',
                                    'when' => $event?->start_time?->format('M j, Y'),
                                    'location' => $event->location ?? '',
                                    'tasks' => $rows->map(fn ($row) => [
                                        'title' => $row->task->title ?? 'Role',
                                        'status' => ucfirst(str_replace('_', ' ', (string) $row->status)),
                                        'hours' => $row->hours_logged,
                                        'note' => $row->completion_note,
                                    ])->values(),
                                ];
                            })->values();
                            $volunteerPayload = [
                                'name' => $member->name,
                                'initials' => $initials,
                                'photo' => $member->profile_photo_path ? asset('storage/'.$member->profile_photo_path) : null,
                                'email' => $member->email,
                                'phone' => $member->phone ?: 'Not provided',
                                'bio' => $member->bio ?: 'No bio yet.',
                                'availability' => $availabilityLabel,
                                'account' => ucfirst((string) $member->status),
                                'joined' => $member->created_at?->format('M j, Y'),
                                'skills' => $member->skills->pluck('name')->values(),
                                'eventsCount' => $eventCount,
                                'events' => $eventGroups,
                                'certificates' => $member->certificates->map(fn ($certificate) => [
                                    'event' => $certificate->event->title ?? 'Event',
                                    'code' => $certificate->certificate_code,
                                    'issued' => $certificate->issued_at?->format('M j, Y'),
                                ])->values(),
                                'applications' => $member->taskApplications->map(fn ($application) => [
                                    'task' => $application->task->title ?? 'Role',
                                    'event' => $application->event->title ?? 'Event',
                                    'status' => ucfirst((string) $application->status),
                                ])->values(),
                            ];
                        @endphp
                        <script type="application/json" id="volunteer-payload-{{ $member->id }}">@json($volunteerPayload)</script>
                        <button type="button" data-volunteer-card data-volunteer-id="{{ $member->id }}" data-search="{{ strtolower($member->name.' '.$member->email.' '.$member->phone.' '.$member->skills->pluck('name')->join(' ')) }}" onclick="openVolunteer(this)" class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-jci-blue dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-start gap-3">
                                    @if($member->profile_photo_path)
                                        <img src="{{ asset('storage/'.$member->profile_photo_path) }}" alt="" class="h-11 w-11 shrink-0 rounded-full object-cover">
                                    @else
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{{ $initials }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $member->name }}</p>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @forelse($member->skills as $skill)
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">{{ $skill->name }}</span>
                                            @empty
                                                <span class="text-[11px] text-slate-400">No skills listed</span>
                                            @endforelse
                                        </div>
                                        <p class="mt-2 text-xs text-slate-400">{{ $eventCount }} {{ $eventCount === 1 ? 'event' : 'events' }} participated</p>
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $availabilityClass }}">{{ $availabilityLabel }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
                <p id="org-volunteer-empty" class="hidden rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center text-sm text-slate-400">No volunteers match that search.</p>
            @endif
        </div>

        <x-ui.modal id="modal-volunteer" title="Volunteer details">
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <img id="volunteer-detail-photo" alt="" class="hidden h-12 w-12 rounded-full object-cover">
                    <span id="volunteer-detail-initials" class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-500"></span>
                    <div class="min-w-0">
                        <h4 id="volunteer-detail-name" class="text-base font-semibold text-slate-900"></h4>
                        <p id="volunteer-detail-availability" class="mt-1 text-xs font-semibold text-jci-blue"></p>
                    </div>
                </div>
                <dl class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold uppercase tracking-wider text-slate-400">Email</dt>
                        <dd id="volunteer-detail-email" class="mt-1 text-sm text-slate-700"></dd>
                    </div>
                    <div>
                        <dt class="font-semibold uppercase tracking-wider text-slate-400">Phone</dt>
                        <dd id="volunteer-detail-phone" class="mt-1 text-sm text-slate-700"></dd>
                    </div>
                    <div>
                        <dt class="font-semibold uppercase tracking-wider text-slate-400">Account</dt>
                        <dd id="volunteer-detail-account" class="mt-1 text-sm text-slate-700"></dd>
                    </div>
                    <div>
                        <dt class="font-semibold uppercase tracking-wider text-slate-400">Joined</dt>
                        <dd id="volunteer-detail-joined" class="mt-1 text-sm text-slate-700"></dd>
                    </div>
                </dl>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">About</p>
                    <p id="volunteer-detail-bio" class="mt-1 text-sm leading-relaxed text-slate-600"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Skills</p>
                    <div id="volunteer-detail-skills" class="mt-2 flex flex-wrap gap-1.5"></div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Events</p>
                    <div id="volunteer-detail-events" class="mt-2 space-y-2"></div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Certificates</p>
                    <div id="volunteer-detail-certificates" class="mt-2 space-y-2"></div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Applications</p>
                    <div id="volunteer-detail-applications" class="mt-2 space-y-2"></div>
                </div>
            </div>
        </x-ui.modal>
    @elseif($section === 'messages')
        <div class="space-y-5">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Messages</h1>
                <p class="mt-1 text-sm text-slate-500">Chat with your volunteers.</p>
            </div>
            <div class="grid min-h-[32rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:grid-cols-[280px_minmax(0,1fr)]">
                <div class="border-b border-slate-100 dark:border-slate-800 lg:border-b-0 lg:border-r">
                    <div class="p-3">
                        <input id="org-message-search" type="search" placeholder="Search..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                    </div>
                    <div class="max-h-[28rem] overflow-y-auto">
                        @forelse($messageThreads as $thread)
                            @php
                                $parts = preg_split('/\s+/', trim($thread->name)) ?: [];
                                $initials = strtoupper(collect($parts)->filter()->take(2)->map(fn ($part) => substr($part, 0, 1))->join(''));
                                $activeThread = $selectedMessageVolunteer && (int) $selectedMessageVolunteer->id === (int) $thread->id;
                            @endphp
                            <a href="{{ route('org.dashboard', ['section' => 'messages', 'volunteer' => $thread->id]) }}" data-message-thread data-search="{{ strtolower($thread->name) }}" class="flex items-start gap-3 px-3 py-3 {{ $activeThread ? 'bg-sky-50 dark:bg-sky-950/40' : '' }}">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-500">{{ $initials }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $thread->name }}</span>
                                        <span class="shrink-0 text-[10px] text-slate-400">{{ $thread->message_at?->diffForHumans() }}</span>
                                    </span>
                                    <span class="mt-0.5 block truncate text-xs text-slate-400">{{ $thread->message_preview }}</span>
                                    @if($thread->needs_takeover)
                                        <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Take over</span>
                                    @endif
                                </span>
                            </a>
                        @empty
                            <p class="px-4 py-8 text-center text-xs text-slate-400">No conversations yet. They appear when a volunteer messages you.</p>
                        @endforelse
                    </div>
                </div>
                <div class="flex min-h-[28rem] flex-col">
                    @if(!$selectedMessageVolunteer)
                        <div class="flex flex-1 items-center justify-center p-8 text-center text-sm text-slate-400">Select a conversation when a volunteer writes in.</div>
                    @else
                        @php
                            $parts = preg_split('/\s+/', trim($selectedMessageVolunteer->name)) ?: [];
                            $initials = strtoupper(collect($parts)->filter()->take(2)->map(fn ($part) => substr($part, 0, 1))->join(''));
                            $availabilityLabel = $selectedMessageVolunteer->status === 'pending' ? 'Pending' : ($selectedMessageVolunteer->availability === 'inactive' ? 'Unavailable' : 'Available');
                        @endphp
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-500">{{ $initials }}</span>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $selectedMessageVolunteer->name }}</p>
                                    <p class="text-[11px] text-slate-400">Volunteer · {{ $availabilityLabel }}{{ $selectedConversation?->withAgent() ? ' · You are replying' : '' }}</p>
                                </div>
                            </div>
                            @if($selectedConversation?->needs_takeover)
                                <form action="{{ route('org.messages.takeover', $selectedMessageVolunteer) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="rounded-xl bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Take over</button>
                                </form>
                            @endif
                        </div>
                        <div class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
                            @forelse($messageEntries as $entry)
                                @if($entry->message !== '')
                                    <div class="flex items-end gap-2">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-500">{{ $initials }}</span>
                                        <div class="max-w-[75%] rounded-2xl bg-slate-100 px-3 py-2 text-sm text-slate-700">
                                            {{ $entry->message }}
                                            <span class="mt-1 block text-[10px] text-slate-400">{{ $entry->created_at->format('g:i A') }}</span>
                                        </div>
                                    </div>
                                @endif
                                @if($entry->answered_by === 'assistant' && $entry->response !== '')
                                    <div class="flex items-end justify-end">
                                        <div class="max-w-[75%] rounded-2xl border border-slate-200 px-3 py-2 text-sm text-slate-600">
                                            <span class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Assistant</span>
                                            {{ $entry->response }}
                                        </div>
                                    </div>
                                @elseif($entry->needs_takeover && $entry->response !== '')
                                    <div class="flex items-end justify-end">
                                        <div class="max-w-[75%] rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-slate-700">
                                            <span class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-amber-700">Assistant handed this over</span>
                                            {{ $entry->response }}
                                        </div>
                                    </div>
                                @endif
                                @if($entry->admin_reply)
                                    <div class="flex justify-end">
                                        <div class="max-w-[75%] rounded-2xl bg-jci-blue px-3 py-2 text-sm text-white">
                                            {{ $entry->admin_reply }}
                                            <span class="mt-1 block text-right text-[10px] text-sky-100">{{ ($entry->admin_replied_at ?? $entry->created_at)->format('g:i A') }}</span>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <p class="text-center text-sm text-slate-400">No messages in this conversation yet.</p>
                            @endforelse
                        </div>
                        <form action="{{ route('org.messages.reply', $selectedMessageVolunteer) }}" method="POST" class="flex items-center gap-2 border-t border-slate-100 p-3 dark:border-slate-800">
                            @csrf
                            <input name="message" required maxlength="2000" placeholder="Type a message..." class="flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                            <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-xl bg-jci-blue text-white hover:bg-jci-dark" aria-label="Send message">
                                <i class="fa-solid fa-paper-plane text-sm"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        <script>
            const messageSearch = document.getElementById('org-message-search');
            if (messageSearch) {
                messageSearch.addEventListener('input', function () {
                    const query = messageSearch.value.trim().toLowerCase();
                    document.querySelectorAll('[data-message-thread]').forEach(function (row) {
                        row.classList.toggle('hidden', query !== '' && !(row.dataset.search || '').includes(query));
                    });
                });
            }
            setInterval(function () {
                fetch("{{ route('org.messages.presence') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                }).catch(function () {});
            }, 45000);
        </script>
    @elseif($section === 'certificates')
        @php
            $certificateVolunteers = $certificateChoices->unique('volunteer_id')->values();
            $duplicateCertificateNames = $certificateChoices->pluck('volunteer_name')->duplicates();
        @endphp
        <div class="space-y-5">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Certificates</h1>
                <p class="mt-1 text-sm text-slate-500">Issue and manage volunteer certificates.</p>
            </div>

            @if($completionReviews->isNotEmpty())
                <div id="submitted-tasks" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/40">
                    <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Submitted tasks</h2>
                    <p class="mt-1 text-xs text-amber-800 dark:text-amber-200">Approve the finished work first. A certificate can be rendered after every task on that event is complete.</p>
                    <div class="mt-4 space-y-3">
                        @foreach($completionReviews as $review)
                            <div class="rounded-xl border border-amber-100 bg-white p-4 dark:border-amber-900 dark:bg-slate-900">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $review->user->name ?? 'Volunteer' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $review->task->title ?? 'Task' }} · {{ $review->event->title ?? 'Event' }}</p>
                                    </div>
                                    @if($review->completion_proof_path)
                                        <a href="{{ route('org.assignments.proof', $review->id) }}" class="text-xs font-semibold text-jci-blue hover:underline">Download proof</a>
                                    @endif
                                </div>
                                @if($review->completion_note)
                                    <p class="mt-2 text-xs text-slate-600 dark:text-slate-300">{{ $review->completion_note }}</p>
                                @endif
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <form action="{{ route('org.assignments.approve', $review->id) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="number" name="hours_logged" min="0.25" max="24" step="0.25" required placeholder="Hours" class="w-24 rounded-lg border border-slate-200 px-2.5 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                                        <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Approve completion</button>
                                    </form>
                                    <form action="{{ route('org.assignments.reject', $review->id) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="text" name="feedback" required maxlength="1000" placeholder="What needs to be revised" class="min-w-0 flex-1 rounded-lg border border-slate-200 px-2.5 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                                        <button type="submit" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Return</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid items-start gap-5 lg:grid-cols-2">
                <form action="{{ route('org.certificates.store') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    @csrf
                    <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-white">
                        <i class="fa-solid fa-award text-jci-blue"></i> Generate Certificate
                    </h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="certificate-volunteer" class="mb-1.5 block text-sm text-slate-600 dark:text-slate-300">Volunteer</label>
                            <select id="certificate-volunteer" name="volunteer_id" required @disabled($certificateVolunteers->isEmpty()) class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue disabled:bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                                <option value="">Select volunteer</option>
                                @foreach($certificateVolunteers as $choice)
                                    <option value="{{ $choice['volunteer_id'] }}">
                                        {{ $choice['volunteer_name'] }}@if($duplicateCertificateNames->contains($choice['volunteer_name'])) ({{ $choice['email'] }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="certificate-event" class="mb-1.5 block text-sm text-slate-600 dark:text-slate-300">Event</label>
                            <select id="certificate-event" name="event_id" required @disabled($certificateVolunteers->isEmpty()) class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue disabled:bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                                <option value="">Select event</option>
                            </select>
                            <p id="certificate-task-note" class="mt-1.5 text-[11px] text-slate-400"></p>
                        </div>
                        <div>
                            <label for="certificate-hours" class="mb-1.5 block text-sm text-slate-600 dark:text-slate-300">Hours rendered</label>
                            <input id="certificate-hours" name="hours_rendered" type="number" min="0.25" max="999" step="0.25" required @disabled($certificateVolunteers->isEmpty()) placeholder="Hours" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue disabled:bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <button type="submit" @disabled($certificateVolunteers->isEmpty()) class="flex w-full items-center justify-center gap-2 rounded-xl bg-jci-blue py-2.5 text-sm font-semibold text-white hover:bg-jci-dark disabled:cursor-not-allowed disabled:opacity-50">
                            <i class="fa-solid fa-award"></i> Generate
                        </button>
                        <p class="text-[11px] text-slate-400">Only volunteers who finished every assigned task on an event are listed.</p>
                    </div>
                </form>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Issued Certificates</h2>
                    </div>
                    @if($issuedCertificates->isEmpty())
                        <p class="px-5 py-12 text-center text-sm text-slate-400">No certificates issued yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs text-slate-400">
                                    <tr>
                                        <th class="px-5 py-3 font-medium">Volunteer</th>
                                        <th class="px-3 py-3 font-medium">Event</th>
                                        <th class="px-3 py-3 font-medium">Issued</th>
                                        <th class="px-5 py-3 font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($issuedCertificates as $certificate)
                                        <tr class="border-t border-slate-100 dark:border-slate-800">
                                            <td class="px-5 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $certificate->user->name ?? 'Volunteer' }}</td>
                                            <td class="px-3 py-3 text-slate-600 dark:text-slate-300">{{ $certificate->event->title ?? 'Event' }}</td>
                                            <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $certificate->issued_at?->format('M d, Y') }}</td>
                                            <td class="px-5 py-3">
                                                <a href="{{ route('org.certificates.download', $certificate) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200">
                                                    <i class="fa-solid fa-download"></i> PDF
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <script type="application/json" id="certificate-choices">@json($certificateChoices)</script>
        <script>
            (function () {
                const choices = JSON.parse(document.getElementById('certificate-choices').textContent || '[]');
                const volunteerSelect = document.getElementById('certificate-volunteer');
                const eventSelect = document.getElementById('certificate-event');
                const hoursInput = document.getElementById('certificate-hours');
                const taskNote = document.getElementById('certificate-task-note');
                if (!volunteerSelect || !eventSelect) {
                    return;
                }

                function fillEvents() {
                    const volunteerId = volunteerSelect.value;
                    const matches = choices.filter(function (choice) {
                        return String(choice.volunteer_id) === String(volunteerId);
                    });
                    eventSelect.innerHTML = '<option value="">Select event</option>';
                    matches.forEach(function (choice) {
                        const option = document.createElement('option');
                        option.value = choice.event_id;
                        option.textContent = choice.event_title;
                        option.dataset.hours = choice.hours || '';
                        option.dataset.tasks = (choice.tasks || []).join(', ');
                        eventSelect.appendChild(option);
                    });
                    if (matches.length === 1) {
                        eventSelect.value = String(matches[0].event_id);
                    }
                    applyEvent();
                }

                function applyEvent() {
                    const option = eventSelect.selectedOptions[0];
                    const hours = option && option.dataset.hours ? option.dataset.hours : '';
                    const tasks = option && option.dataset.tasks ? option.dataset.tasks : '';
                    hoursInput.value = hours;
                    taskNote.textContent = tasks ? 'Completed: ' + tasks : '';
                }

                volunteerSelect.addEventListener('change', fillEvents);
                eventSelect.addEventListener('change', applyEvent);
            })();
        </script>
    @elseif($section === 'officers')
        @include('org.officers')
    @elseif($section === 'gallery')
        @include('org.gallery')
    @elseif($section === 'settings')
        @include('org.settings')
    @else
        @php
            $sectionCopy = [
                'events' => ['Events', 'Create events, update schedules, and keep compliance files with the event.'],
                'tasks' => ['Task Assignment', 'Review volunteers who applied before you assign them.'],
                'volunteers' => ['Volunteers', 'People who joined your organization.'],
                'matching' => ['Skill Matching', 'Rank approved volunteers against the skills a task requires.'],
                'certificates' => ['Certificates', 'Approve submitted work to log hours and issue a certificate.'],
            ];
            $heading = $sectionCopy[$section] ?? ['Workspace', ''];
        @endphp
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $heading[0] }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $heading[1] }}</p>
        </div>
        <style>
            .org-legacy[data-focus="events"] [data-panel]:not([data-panel="events"]),
            .org-legacy[data-focus="tasks"] [data-panel]:not([data-panel="tasks"]),
            .org-legacy[data-focus="volunteers"] [data-panel]:not([data-panel="volunteers"]),
            .org-legacy[data-focus="matching"] [data-panel]:not([data-panel="matching"]),
            .org-legacy[data-focus="certificates"] [data-panel]:not([data-panel="certificates"]) { display: none; }
            .org-legacy[data-focus="matching"] .org-main-column,
            .org-legacy[data-focus="events"] .org-side-column,
            .org-legacy[data-focus="tasks"] .org-side-column,
            .org-legacy[data-focus="volunteers"] .org-side-column,
            .org-legacy[data-focus="certificates"] .org-side-column { display: none; }
            .org-legacy[data-focus="matching"] .org-side-column,
            .org-legacy[data-focus="events"] .org-main-column,
            .org-legacy[data-focus="tasks"] .org-main-column,
            .org-legacy[data-focus="volunteers"] .org-main-column,
            .org-legacy[data-focus="certificates"] .org-main-column { grid-column: 1 / -1; }
            .org-legacy[data-focus="matching"] .org-side-column .glass-card { max-height: none; }
        </style>
        <div class="org-legacy grid grid-cols-1 gap-6 lg:grid-cols-12" data-focus="{{ $section }}">
            @include('org.operations')
        </div>
    @endif

    <x-ui.modal id="modal-edit-event" title="Edit Event">
        <form id="event-edit-form" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Event Title</label>
                <input name="title" type="text" required maxlength="255" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Location</label>
                <input name="location" type="text" required class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Start</label>
                    <input name="start_time" type="datetime-local" required class="w-full rounded-lg border border-slate-200 p-2 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">End</label>
                    <input name="end_time" type="datetime-local" required class="w-full rounded-lg border border-slate-200 p-2 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Capacity</label>
                    <input name="capacity" type="number" min="1" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                    <select name="status" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue"></select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Description</label>
                <textarea name="description" required rows="4" class="w-full resize-none rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue"></textarea>
            </div>
            <p id="event-edit-note" class="text-[10px] text-slate-400"></p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-edit-event').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Save Event</button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="modal-new-event" title="Create Event">
        <form action="{{ route('org.events.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Event Title</label>
                <input name="title" type="text" required placeholder="e.g., Clean Beach Reschedule" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Location Address</label>
                <input name="location" type="text" required placeholder="e.g., Surigao Boulevard" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Start Date & Time</label>
                    <input name="start_time" type="datetime-local" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">End Date & Time</label>
                    <input name="end_time" type="datetime-local" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Max Capacity (Vols)</label>
                <input name="capacity" type="number" min="1" placeholder="e.g. 50" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Description</label>
                <textarea name="description" required placeholder="Describe details of the outreach event..." class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-20 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
            </div>
            <div class="pt-3 border-t border-slate-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[10px] text-slate-400">Admin reviews this before volunteers can see it. Add roles later from Task Assignment.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-new-event').classList.add('hidden')" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition duration-200">Cancel</button>
                    <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs px-4 py-2.5 rounded-xl transition duration-200 shadow-md">Submit for review</button>
                </div>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="modal-event-details" title="Event Details">
        <div class="space-y-3 text-sm">
            <h4 id="event-detail-title" class="text-base font-semibold text-slate-900"></h4>
            <p id="event-detail-status" class="text-xs font-semibold text-jci-blue"></p>
            <p id="event-detail-when" class="text-xs text-slate-500"></p>
            <p id="event-detail-where" class="text-xs text-slate-500"></p>
            <p id="event-detail-volunteers" class="text-xs text-slate-500"></p>
            <p id="event-detail-note" class="text-xs leading-relaxed text-slate-600"></p>
            <div class="flex justify-end pt-2">
                <a href="{{ route('org.dashboard', ['section' => 'events']) }}" class="rounded-xl bg-jci-blue px-4 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Manage Events</a>
            </div>
        </div>
    </x-ui.modal>

    <x-ui.modal id="modal-create-task" title="Create Task">
        @if($events->whereNotIn('status', ['cancelled'])->count() > 0)
            <form action="{{ route('org.tasks.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Event</label>
                    <select name="event_id" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        @foreach($events->whereNotIn('status', ['cancelled']) as $eventOption)
                            <option value="{{ $eventOption->id }}">{{ $eventOption->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Task title</label>
                    <input name="title" type="text" required maxlength="255" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Priority</label>
                        <select name="priority" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Deadline</label>
                        <input name="due_date" type="datetime-local" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs">
                    </div>
                </div>
                <p class="text-[10px] text-slate-400">The task stays pending until a volunteer is assigned. A draft event still needs admin approval.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-create-task').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Create Task</button>
                </div>
            </form>
        @else
            <p class="text-sm text-slate-500">Create an event first, then link a task to it.</p>
        @endif
    </x-ui.modal>

    <x-ui.modal id="modal-assign-task" title="Assign a Task">
        @if($allOrgTasks->count() > 0 && $assignableVolunteers->count() > 0)
            <form action="{{ route('org.assignments.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Task</label>
                    <select name="task_id" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        @foreach($allOrgTasks as $task)
                            <option value="{{ $task->id }}">{{ $task->title }} ({{ $task->event->title ?? 'Event' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Volunteer</label>
                    <select name="user_id" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        @foreach($assignableVolunteers as $volunteer)
                            <option value="{{ $volunteer->id }}">{{ $volunteer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modal-assign-task').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Assign</button>
                </div>
            </form>
        @else
            <p class="text-sm text-slate-500">Add an open task and an approved volunteer before assigning someone.</p>
        @endif
    </x-ui.modal>

    <x-ui.modal id="modal-send-notification" title="Send Notification">
        <form action="{{ route('org.notifications.send') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Type</label>
                <select name="notice_type" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                    <option value="event">Event</option>
                    <option value="task">Task</option>
                    <option value="reminder">Reminder</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Title</label>
                <input name="title" type="text" required maxlength="120" placeholder="Schedule update" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Message</label>
                <textarea name="message" required maxlength="1000" rows="4" placeholder="Write a short notice for your volunteers" class="w-full resize-none rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue"></textarea>
            </div>
            <p class="text-[10px] text-slate-400">This reaches the volunteer platform and their email, including their new events and assigned tasks.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-send-notification').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Send</button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="modal-generate-certificate" title="Submitted work">
        @if($completionReviews->count() > 0)
            <p class="mb-3 text-xs text-slate-500">Approve the hours first. Render the certificate from Certificates after every task on the event is finished.</p>
            <div class="space-y-3">
                @foreach($completionReviews->take(3) as $review)
                    <form action="{{ route('org.assignments.approve', $review->id) }}" method="POST" class="space-y-2 rounded-xl border border-slate-200 p-3">
                        @csrf
                        <p class="text-xs font-bold text-slate-800">{{ $review->user->name ?? 'Volunteer' }}</p>
                        <p class="text-[11px] text-slate-400">{{ $review->task->title ?? 'Task' }} · {{ $review->event->title ?? 'Event' }}</p>
                        <input type="number" name="hours_logged" min="0.25" max="24" step="0.25" required placeholder="Hours" class="w-full rounded-lg border border-slate-200 p-2 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        <button type="submit" class="w-full rounded-lg bg-jci-blue py-2 text-xs font-bold text-white hover:bg-jci-dark">Approve completion</button>
                    </form>
                @endforeach
            </div>
            @if($completionReviews->count() > 3)
                <a href="{{ route('org.dashboard', ['section' => 'certificates']) }}#submitted-tasks" class="mt-3 inline-block text-xs font-bold text-jci-blue">Review all {{ $completionReviews->count() }} submissions</a>
            @endif
        @else
            <p class="text-sm text-slate-500">No submitted work is waiting. Render a certificate after a volunteer finishes every task on an event.</p>
            <a href="{{ route('org.dashboard', ['section' => 'certificates']) }}" class="mt-3 inline-block text-xs font-bold text-jci-blue">Open certificates</a>
        @endif
    </x-ui.modal>

    <script>
        function openEventDetails(button) {
            document.getElementById('event-detail-title').textContent = button.dataset.title || '';
            document.getElementById('event-detail-status').textContent = button.dataset.status || '';
            document.getElementById('event-detail-when').textContent = button.dataset.when || '';
            document.getElementById('event-detail-where').textContent = button.dataset.where || '';
            document.getElementById('event-detail-volunteers').textContent = (button.dataset.volunteers || '0') + ' volunteers registered';
            document.getElementById('event-detail-note').textContent = button.dataset.note || '';
            document.getElementById('modal-event-details').classList.remove('hidden');
        }

        function openEditEvent(button) {
            const event = JSON.parse(document.getElementById('event-payload-' + button.dataset.eventId).textContent);
            const form = document.getElementById('event-edit-form');
            form.action = event.updateUrl;
            form.querySelector('[name="title"]').value = event.title || '';
            form.querySelector('[name="location"]').value = event.location || '';
            form.querySelector('[name="start_time"]').value = event.start || '';
            form.querySelector('[name="end_time"]').value = event.end || '';
            form.querySelector('[name="capacity"]').value = event.capacity || '';
            form.querySelector('[name="description"]').value = event.description || '';
            const status = form.querySelector('[name="status"]');
            const choices = event.status === 'draft'
                ? [['draft', 'Pending Approval'], ['cancelled', 'Cancelled']]
                : [['published', 'Published'], ['completed', 'Completed'], ['cancelled', 'Cancelled']];
            status.innerHTML = '';
            choices.forEach(function (choice) {
                const option = document.createElement('option');
                option.value = choice[0];
                option.textContent = choice[1];
                option.selected = choice[0] === event.status;
                status.appendChild(option);
            });
            document.getElementById('event-edit-note').textContent = event.status === 'draft'
                ? 'This event is pending admin approval. Your organization cannot approve it.'
                : '';
            document.getElementById('modal-edit-event').classList.remove('hidden');
        }

        function openReassign(button) {
            button.classList.add('hidden');
            const form = button.parentElement.querySelector('[data-reassign-form]');
            if (!form) {
                return;
            }
            form.classList.remove('hidden');
            form.classList.add('flex');
        }

        function previewAssignment(select) {
            const row = select.closest('tr');
            const nameCell = row ? row.querySelector('[data-assigned-name]') : null;
            const button = select.form ? select.form.querySelector('[data-assign-ok]') : null;
            if (!nameCell || !button) {
                return;
            }
            const saved = String(select.dataset.saved || '');
            const option = select.options[select.selectedIndex];
            const picked = select.value !== '' && select.value !== saved;
            nameCell.textContent = select.value
                ? (option.dataset.name || option.textContent)
                : (nameCell.dataset.savedName || 'Unassigned');
            nameCell.classList.toggle('font-semibold', picked);
            nameCell.classList.toggle('text-jci-blue', picked);
            button.disabled = !picked;
        }

        function toggleCustomRole(select) {
            const field = document.getElementById('custom-role-field');
            const input = document.getElementById('custom-role-input');
            if (!field || !input) {
                return;
            }
            const custom = select.value === '__custom__';
            field.classList.toggle('hidden', !custom);
            input.required = custom;
            if (!custom) {
                input.value = '';
            }
            const hint = document.getElementById('role-skill-hint');
            const option = select.options[select.selectedIndex];
            if (!hint) {
                return;
            }
            if (custom) {
                hint.textContent = 'Type the role this event needs. It is matched to related skills, such as Public Relations.';
            } else if (option && option.dataset.skills) {
                hint.textContent = option.value + ' matches volunteers with ' + option.dataset.skills + '.';
            } else {
                hint.textContent = 'Add only the roles this event needs. Each role is matched to the skills it relates to, such as Public Relations.';
            }
        }

        function openVolunteer(button) {
            const payload = document.getElementById('volunteer-payload-' + button.dataset.volunteerId);
            if (!payload) {
                return;
            }
            const volunteer = JSON.parse(payload.textContent);
            const photo = document.getElementById('volunteer-detail-photo');
            const initials = document.getElementById('volunteer-detail-initials');
            document.getElementById('volunteer-detail-name').textContent = volunteer.name || '';
            document.getElementById('volunteer-detail-availability').textContent = volunteer.availability || '';
            document.getElementById('volunteer-detail-email').textContent = volunteer.email || '';
            document.getElementById('volunteer-detail-phone').textContent = volunteer.phone || '';
            document.getElementById('volunteer-detail-account').textContent = volunteer.account || '';
            document.getElementById('volunteer-detail-joined').textContent = volunteer.joined || '';
            document.getElementById('volunteer-detail-bio').textContent = volunteer.bio || '';
            if (volunteer.photo) {
                photo.src = volunteer.photo;
                photo.classList.remove('hidden');
                initials.classList.add('hidden');
            } else {
                photo.classList.add('hidden');
                initials.classList.remove('hidden');
                initials.textContent = volunteer.initials || '';
            }
            const skills = document.getElementById('volunteer-detail-skills');
            skills.innerHTML = '';
            (volunteer.skills || []).forEach(function (skill) {
                const pill = document.createElement('span');
                pill.className = 'rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600';
                pill.textContent = skill;
                skills.appendChild(pill);
            });
            if (!volunteer.skills || volunteer.skills.length === 0) {
                skills.textContent = 'No skills listed.';
            }
            const events = document.getElementById('volunteer-detail-events');
            events.innerHTML = '';
            if (!volunteer.events || volunteer.events.length === 0) {
                events.textContent = 'No events with this organization yet.';
            } else {
                volunteer.events.forEach(function (event) {
                    const block = document.createElement('div');
                    block.className = 'rounded-xl border border-slate-100 px-3 py-2';
                    const title = document.createElement('p');
                    title.className = 'text-sm font-semibold text-slate-800';
                    title.textContent = event.title;
                    const meta = document.createElement('p');
                    meta.className = 'text-[11px] text-slate-400';
                    meta.textContent = [event.when, event.location].filter(Boolean).join(' · ');
                    block.appendChild(title);
                    block.appendChild(meta);
                    (event.tasks || []).forEach(function (task) {
                        const line = document.createElement('p');
                        line.className = 'mt-1 text-xs text-slate-600';
                        const hours = Number(task.hours) > 0 ? ' · ' + task.hours + ' hours' : '';
                        line.textContent = task.title + ' · ' + task.status + hours;
                        block.appendChild(line);
                    });
                    events.appendChild(block);
                });
            }
            const certificates = document.getElementById('volunteer-detail-certificates');
            certificates.innerHTML = '';
            if (!volunteer.certificates || volunteer.certificates.length === 0) {
                certificates.textContent = 'No certificates issued yet.';
            } else {
                volunteer.certificates.forEach(function (certificate) {
                    const line = document.createElement('p');
                    line.className = 'text-sm text-slate-700';
                    line.textContent = certificate.event + (certificate.issued ? ' · ' + certificate.issued : '') + (certificate.code ? ' · ' + certificate.code : '');
                    certificates.appendChild(line);
                });
            }
            const applications = document.getElementById('volunteer-detail-applications');
            applications.innerHTML = '';
            if (!volunteer.applications || volunteer.applications.length === 0) {
                applications.textContent = 'No applications.';
            } else {
                volunteer.applications.forEach(function (application) {
                    const line = document.createElement('p');
                    line.className = 'text-sm text-slate-700';
                    line.textContent = application.task + ' · ' + application.event + ' · ' + application.status;
                    applications.appendChild(line);
                });
            }
            document.getElementById('modal-volunteer').classList.remove('hidden');
        }

        const volunteerSearch = document.getElementById('org-volunteer-search');
        if (volunteerSearch) {
            volunteerSearch.addEventListener('input', function () {
                const query = volunteerSearch.value.trim().toLowerCase();
                let shown = 0;
                document.querySelectorAll('[data-volunteer-card]').forEach(function (card) {
                    const hit = (card.dataset.search || '').includes(query);
                    card.classList.toggle('hidden', !hit);
                    if (hit) {
                        shown++;
                    }
                });
                const empty = document.getElementById('org-volunteer-empty');
                if (empty) {
                    empty.classList.toggle('hidden', shown !== 0);
                }
            });
        }

        function openCreateEvent() {
            document.getElementById('modal-new-event').classList.remove('hidden');
        }

        const eventSearch = document.getElementById('org-event-search');
        if (eventSearch) {
            eventSearch.addEventListener('input', function () {
                const query = eventSearch.value.trim().toLowerCase();
                let shown = 0;
                document.querySelectorAll('[data-event-card]').forEach(function (card) {
                    const hit = (card.dataset.search || '').includes(query);
                    card.classList.toggle('hidden', !hit);
                    if (hit) {
                        shown++;
                    }
                });
                const empty = document.getElementById('org-event-search-empty');
                if (empty) {
                    empty.classList.toggle('hidden', query === '' || shown > 0);
                }
            });
        }
    </script>
</x-layout.org>
