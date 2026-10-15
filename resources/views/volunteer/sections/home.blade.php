@php
    $clock = \App\Support\ScheduleClock::class;
    $duty = \App\Support\VolunteerDuty::class;
    $firstName = \Illuminate\Support\Str::before($volunteer->name, ' ');
    $cards = [
        ['Pending responses', $pendingResponses->count(), 'Waiting for your answer', 'fa-hourglass-half', route('volunteer.dashboard', ['section' => 'tasks'])],
        ['Upcoming events', $upcomingEvents->count(), $nextEvent ? $clock::format($nextEvent->start_time) : 'None scheduled now', 'fa-calendar-days', route('volunteer.dashboard', ['section' => 'events'])],
        ['Active tasks', $activeTasks->count(), 'Accepted and still open', 'fa-list-check', route('volunteer.dashboard', ['section' => 'tasks'])],
        ['Completed tasks', $completedAssignments->count(), 'Reviewed and confirmed', 'fa-circle-check', route('volunteer.dashboard', ['section' => 'tasks', 'task_status' => 'completed'])],
        ['Verified hours', number_format($verifiedHours, 1), 'Approved volunteer hours', 'fa-clock', route('volunteer.dashboard', ['section' => 'certificates'])],
    ];
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Welcome back, {{ $firstName }}</h1>
        <p class="mt-1 text-sm text-slate-500">Here's what's happening now · {{ $clock::now()->format('M j, Y g:i A') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($cards as [$label, $value, $caption, $icon, $href])
            <a href="{{ $href }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-jci-blue dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                    <i class="fa-solid {{ $icon }} text-jci-blue"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-slate-900 dark:text-white">{{ $value }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $caption }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([
            ['My Events', 'events', 'fa-calendar-days'],
            ['Assigned Tasks', 'tasks', 'fa-list-check'],
            ['Availability', 'availability', 'fa-clock'],
            ['Notifications', 'notifications', 'fa-bell'],
        ] as [$label, $sectionName, $icon])
            <a href="{{ route('volunteer.dashboard', ['section' => $sectionName]) }}" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700 shadow-sm hover:border-jci-blue dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                <i class="fa-solid {{ $icon }} text-jci-blue"></i>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Tasks waiting for you</h2>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($pendingResponses as $assign)
                    <div class="py-3">
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $assign->task->title }}</p>
                        <p class="text-xs text-slate-400">{{ $assign->event->title }} · {{ $clock::format($assign->task->due_date ?? $assign->event->start_time) }}</p>
                    </div>
                @empty
                    <p class="py-6 text-sm text-slate-400">No tasks are waiting for a response.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Upcoming events</h2>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($upcomingEvents->take(4) as $event)
                    <div class="py-3">
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $event->title }}</p>
                        <p class="text-xs text-slate-400">{{ $clock::range($event->start_time, $event->end_time) }}{{ $event->location ? ' · '.$event->location : '' }}</p>
                    </div>
                @empty
                    <p class="py-6 text-sm text-slate-400">No upcoming events on your accepted assignments.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recommended opportunities</h2>
                <a href="{{ route('volunteer.dashboard', ['section' => 'skills']) }}" class="text-xs font-semibold text-jci-blue">See all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($recommendedOpportunities as $task)
                    <div class="py-3">
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $task->title }}</p>
                        <p class="text-xs text-slate-400">{{ $task->event->title ?? 'Event' }} · {{ $task->match_score }}% skill match</p>
                    </div>
                @empty
                    <p class="py-6 text-sm text-slate-400">No open opportunities match your organization right now.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent notifications</h2>
                <a href="{{ route('volunteer.dashboard', ['section' => 'notifications']) }}" class="text-xs font-semibold text-jci-blue">Open</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($notices as $notice)
                    @php $data = $notice->data; @endphp
                    <div class="py-3">
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $data['title'] ?? 'Notification' }}</p>
                        <p class="text-xs text-slate-400">{{ $clock::stamp($notice->created_at) }}{{ $notice->read_at ? '' : ' · Unread' }}</p>
                    </div>
                @empty
                    <p class="py-6 text-sm text-slate-400">No notifications yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent activity</h2>
        <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($recentActivity as $assign)
                <div class="flex flex-wrap items-center justify-between gap-2 py-3">
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $assign->task->title ?? 'Task' }}</p>
                        <p class="text-xs text-slate-400">{{ $assign->event->title ?? 'Event' }} · {{ $clock::stamp($assign->updated_at) }}</p>
                    </div>
                    <span class="rounded-lg border border-slate-200 px-2.5 py-1 text-[11px] font-medium text-slate-500">{{ $duty::label($assign->status, $assign->schedule_phase, $assign->feedback) }}</span>
                </div>
            @empty
                <p class="py-6 text-sm text-slate-400">No activity yet.</p>
            @endforelse
        </div>
    </section>
</div>
