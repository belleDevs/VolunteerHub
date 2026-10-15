@php
    $schedule = $volunteer->availability_schedule ?? [];
    $days = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Availability</h1>
        <p class="mt-1 text-sm text-slate-500">This tells the organization when you can take new work. It does not cancel tasks you already accepted.</p>
    </div>

    <form action="{{ route('volunteer.availability.toggle') }}" method="POST" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf
        <fieldset>
            <legend class="text-sm font-semibold text-slate-900 dark:text-white">Status</legend>
            <div class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-200">
                <label class="flex items-center gap-2"><input type="radio" name="availability" value="active" @checked(old('availability', $volunteer->availability) === 'active' || !in_array($volunteer->availability, ['limited', 'inactive'], true))> Available</label>
                <label class="flex items-center gap-2"><input type="radio" name="availability" value="limited" @checked(old('availability', $volunteer->availability) === 'limited')> Busy / Limited Availability</label>
                <label class="flex items-center gap-2"><input type="radio" name="availability" value="inactive" @checked(old('availability', $volunteer->availability) === 'inactive')> Unavailable</label>
            </div>
        </fieldset>

        <fieldset>
            <legend class="text-sm font-semibold text-slate-900 dark:text-white">Schedule, optional</legend>
            <div class="mt-3 space-y-3">
                @foreach($days as $key => $label)
                    @php $day = $schedule[$key] ?? []; @endphp
                    <div class="grid grid-cols-1 items-center gap-2 sm:grid-cols-[10rem_1fr_1fr]">
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="days[{{ $key }}][on]" value="1" @checked(!empty($day['on']))>
                            {{ $label }}
                        </label>
                        <input type="time" name="days[{{ $key }}][from]" value="{{ $day['from'] ?? '' }}" aria-label="{{ $label }} start" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <input type="time" name="days[{{ $key }}][to]" value="{{ $day['to'] ?? '' }}" aria-label="{{ $label }} end" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>
                @endforeach
            </div>
        </fieldset>

        <label class="block text-sm font-semibold text-slate-900 dark:text-white">
            Notes
            <textarea name="availability_notes" rows="3" maxlength="1000" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal dark:border-slate-700 dark:bg-slate-950">{{ old('availability_notes', $volunteer->availability_notes) }}</textarea>
        </label>

        <button type="submit" class="rounded-lg bg-jci-blue px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
    </form>
</div>
