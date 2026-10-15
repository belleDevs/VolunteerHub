@php
    $clock = \App\Support\ScheduleClock::class;
    $duty = \App\Support\VolunteerDuty::class;
    $taskStatus = (string) request('task_status', 'all');
    $filters = [
        'all' => 'All',
        'pending' => 'Pending Response',
        'accepted' => 'Accepted',
        'in_progress' => 'In Progress',
        'submitted' => 'Submitted for Review',
        'completed' => 'Completed',
        'declined' => 'Declined',
        'cancelled' => 'Cancelled',
        'uncompleted' => 'Uncompleted',
    ];
    $visible = $taskBoard->filter(function ($assign) use ($taskStatus, $duty) {
        $label = $duty::label($assign->status, $assign->schedule_phase, $assign->feedback);
        return match ($taskStatus) {
            'pending' => $label === 'Pending Response',
            'accepted' => $label === 'Accepted',
            'in_progress' => $label === 'In Progress',
            'submitted' => $label === 'Submitted for Review',
            'declined' => $label === 'Declined',
            'cancelled' => $label === 'Cancelled',
            'uncompleted' => $label === 'Uncompleted',
            'completed' => false,
            default => true,
        };
    });
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Assigned Tasks</h1>
        <p class="mt-1 text-sm text-slate-500">Accept or decline a task while the event is still ahead. After you accept, submit proof for review. You cannot mark a task completed yourself.</p>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($filters as $value => $label)
            <a href="{{ route('volunteer.dashboard', ['section' => 'tasks', 'task_status' => $value]) }}" class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $taskStatus === $value ? 'border-jci-blue bg-jci-blue text-white' : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($taskStatus !== 'completed')
        <section class="space-y-4">
            @forelse($visible as $assign)
                @continue(!$assign->task || !$assign->event)
                @php
                    $phase = $assign->schedule_phase;
                    $label = $duty::label($assign->status, $phase, $assign->feedback);
                    $canRespond = $duty::canRespond($assign->status, $phase);
                @endphp
                <article id="duty-{{ $assign->id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $assign->task->title }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $assign->event->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $clock::range($assign->event->start_time, $assign->event->end_time) }}</p>
                            @if($assign->task->due_date)
                                <p class="mt-1 text-xs text-slate-400">Due {{ $clock::format($assign->task->due_date) }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 rounded-lg border border-slate-200 px-2.5 py-1 text-[11px] font-medium text-slate-600">{{ $label }}</span>
                    </div>

                    @if($canRespond)
                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <form action="{{ route('volunteer.assignments.respond', $assign->id) }}" method="POST" onsubmit="return confirm('Accept this task?');">
                                @csrf
                                <input type="hidden" name="decision" value="accept">
                                <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white">Accept Task</button>
                            </form>
                            <form action="{{ route('volunteer.assignments.respond', $assign->id) }}" method="POST" class="space-y-2" onsubmit="return confirm('Decline this task?');">
                                @csrf
                                <input type="hidden" name="decision" value="decline">
                                <label class="block text-xs text-slate-500">
                                    Reason for declining, optional
                                    <textarea name="reason" rows="2" maxlength="500" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                                </label>
                                <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Decline Task</button>
                            </form>
                        </div>
                    @elseif($assign->status === 'submitted')
                        <p class="mt-4 text-sm text-sky-700">Submitted for review. The organization confirms when it is completed.</p>
                    @elseif(in_array($assign->status, ['approved', 'rejected'], true))
                        @if($assign->status === 'rejected' && $assign->feedback)
                            <p class="mt-4 rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $assign->feedback }}</p>
                        @endif
                        <form action="{{ route('volunteer.tasks.complete', $assign->id) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-2">
                            @csrf
                            <textarea name="completion_note" required rows="3" maxlength="1000" placeholder="Describe what you did, where, and any result." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">{{ old('completion_note') }}</textarea>
                            <input type="file" name="completion_proof" accept=".jpg,.jpeg,.png,.pdf,.docx" class="block w-full text-xs text-slate-500">
                            <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white">Submit for review</button>
                        </form>
                    @elseif($duty::declined($assign->feedback) && $assign->feedback)
                        <p class="mt-4 text-sm text-slate-500">{{ $assign->feedback }}</p>
                    @endif

                    <details class="mt-4">
                        <summary class="cursor-pointer text-xs font-semibold text-jci-blue">View details</summary>
                        <div class="mt-3 space-y-1 text-sm text-slate-600 dark:text-slate-300">
                            <p>{{ $assign->task->description ?: 'No task description was saved.' }}</p>
                            <p class="text-xs text-slate-400">Assigned {{ $clock::stamp($assign->created_at) }}</p>
                            @if($assign->responded_at)
                                <p class="text-xs text-slate-400">Response {{ $clock::stamp($assign->responded_at) }}</p>
                            @endif
                            <p class="text-xs text-slate-400">Required skills: {{ $assign->task->skills->pluck('name')->join(', ') ?: 'None listed' }}</p>
                            <p class="text-xs text-slate-400">Event instructions: {{ $assign->event->description ?: 'None saved.' }}</p>
                        </div>
                    </details>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-400 dark:border-slate-800 dark:bg-slate-900">No tasks match this filter.</p>
            @endforelse
        </section>
    @endif

    @if(in_array($taskStatus, ['all', 'completed'], true) && $completedAssignments->count() > 0)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Completed</h2>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($completedAssignments as $assign)
                    @continue(!$assign->task || !$assign->event)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div>
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $assign->task->title }}</p>
                            <p class="text-xs text-slate-400">{{ $assign->event->title }} · {{ $clock::range($assign->event->start_time, $assign->event->end_time) }} · {{ number_format($assign->hours_logged, 1) }} hours</p>
                        </div>
                        <span class="rounded-lg border border-emerald-200 px-2.5 py-1 text-[11px] font-medium text-emerald-700">Completed</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Open tasks you can apply for</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            @forelse($openTasks as $task)
                @php $application = $task->applications->first(); @endphp
                <article class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $task->title }}</h3>
                    <p class="mt-1 text-xs text-slate-400">{{ $task->event->title }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $clock::range($task->event->start_time, $task->event->end_time) }} · {{ $task->match_score }}% skill match</p>
                    @if($application)
                        <p class="mt-3 text-xs font-medium text-jci-blue">Application {{ $application->status }}</p>
                    @else
                        <form action="{{ route('volunteer.tasks.apply', $task->id) }}" method="POST" class="mt-3 space-y-2">
                            @csrf
                            <textarea name="message" rows="2" maxlength="1000" placeholder="Optional note" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                            <button type="submit" class="rounded-lg bg-jci-blue px-3 py-1.5 text-xs font-semibold text-white">Apply for task</button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="text-sm text-slate-400 md:col-span-2">No open tasks are accepting applications right now.</p>
            @endforelse
        </div>
    </section>
</div>
