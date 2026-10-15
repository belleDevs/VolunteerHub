<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Skill Recommendations</h1>
            <p class="mt-1 text-sm text-slate-500">Open tasks and events in your organization, compared with the skills on your profile.</p>
        </div>
        <a href="{{ route('profile.show') }}" class="text-xs font-semibold text-jci-blue">Update profile</a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Your skills</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse($mySkills as $skill)
                <form action="{{ route('volunteer.skills.destroy', $skill->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600" title="Remove {{ $skill->name }}">{{ $skill->name }} ×</button>
                </form>
            @empty
                <p class="text-sm text-slate-400">No skills saved yet.</p>
            @endforelse
        </div>
        <form action="{{ route('volunteer.skills.store') }}" method="POST" class="mt-4 flex flex-wrap gap-2">
            @csrf
            <input type="text" name="skill_name" maxlength="80" required placeholder="Add a skill" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white">Add skill</button>
        </form>
    </section>

    <section class="space-y-3">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recommended tasks and events</h2>
        @forelse($openTasks->sortByDesc('match_score') as $task)
            @php
                $required = $task->skills->pluck('name');
                $mine = $mySkills->pluck('name');
                $missing = $required->reject(fn ($name) => $mine->contains($name))->values();
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $task->title }}</h3>
                        <p class="mt-1 text-xs text-slate-400">{{ $task->event->title ?? 'Event' }} · {{ \App\Support\ScheduleClock::range($task->event->start_time, $task->event->end_time) }}</p>
                    </div>
                    <span class="rounded-lg border border-sky-200 px-2.5 py-1 text-[11px] font-medium text-sky-700">{{ $task->match_score }}% match</span>
                </div>
                <p class="mt-3 text-xs text-slate-500">Required skills: {{ $required->join(', ') ?: 'None listed' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $missing->isEmpty() ? 'No missing skills.' : 'Additional skills: '.$missing->join(', ') }}</p>
                <a href="{{ route('volunteer.dashboard', ['section' => 'tasks']) }}" class="mt-3 inline-block text-xs font-semibold text-jci-blue">View assigned tasks</a>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-slate-500">No suitable recommendations right now.</p>
                <a href="{{ route('volunteer.dashboard', ['section' => 'skills']) }}#skills" class="mt-3 inline-block text-xs font-semibold text-jci-blue">Update your skills</a>
            </div>
        @endforelse
    </section>

    @if(count($recommendedSkills) > 0)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Skills showing up on open work</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                @foreach($recommendedSkills as $item)
                    <li>{{ $item['skill']->name }} · used on {{ $item['count'] }} open {{ $item['count'] === 1 ? 'task' : 'tasks' }}, including {{ $item['event_title'] }}</li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
