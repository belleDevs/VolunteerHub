@php
    $clock = \App\Support\ScheduleClock::class;
    $buckets = [
        'all' => 'All',
        'upcoming' => 'Upcoming',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">My Events</h1>
        <p class="mt-1 text-sm text-slate-500">Events connected to your assignments. Times are the saved schedule.</p>
    </div>

    <form method="GET" action="{{ route('volunteer.dashboard') }}" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:grid-cols-2 lg:grid-cols-5">
        <input type="hidden" name="section" value="events">
        <label class="block text-xs text-slate-500 lg:col-span-2">
            Search
            <input type="search" name="q" value="{{ $eventSearch }}" placeholder="Title, place, or description" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <label class="block text-xs text-slate-500">
            Status
            <select name="state" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                @foreach($buckets as $value => $label)
                    <option value="{{ $value }}" @selected($eventState === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs text-slate-500">
            From
            <input type="date" name="from" value="{{ $eventFrom }}" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <label class="block text-xs text-slate-500">
            To
            <input type="date" name="to" value="{{ $eventTo }}" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <div class="sm:col-span-2 lg:col-span-5">
            <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white">Apply filters</button>
        </div>
    </form>

    <section class="space-y-4">
        @forelse($eventList as $event)
            @php
                $bucket = $event->event_bucket;
                $bucketLabel = $buckets[$bucket] ?? ucfirst($bucket);
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $event->title }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $clock::range($event->start_time, $event->end_time) }}</p>
                        @if($event->location)
                            <p class="mt-1 text-xs text-slate-400">{{ $event->location }}</p>
                        @endif
                    </div>
                    <span class="rounded-lg border border-slate-200 px-2.5 py-1 text-[11px] font-medium text-slate-500">{{ $bucketLabel }}</span>
                </div>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">Your role: {{ $event->duty_titles->join(', ') ?: 'Assigned' }}</p>
                <p class="mt-1 text-xs text-slate-400">Participation: {{ $event->participation }}</p>
                <details class="mt-4">
                    <summary class="cursor-pointer text-xs font-semibold text-jci-blue">View details</summary>
                    <div class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                        <p>{{ $event->description ?: 'No description or instructions were saved for this event.' }}</p>
                    </div>
                </details>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-400 dark:border-slate-800 dark:bg-slate-900">No events match your participation or the current filters.</p>
        @endforelse
    </section>
</div>
