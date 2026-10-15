@php
    $preferences = $volunteer->notification_preferences ?? [];
    $requested = $preferences['deactivation_requested_at'] ?? null;
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Account choices for your volunteer profile. Organization and administrator settings stay on those accounts.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Account status</h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ ucfirst($volunteer->status ?: 'active') }}</p>
        @if($requested)
            <p class="mt-2 text-sm text-slate-500">Deactivation requested {{ \Illuminate\Support\Carbon::parse($requested)->timezone(\App\Support\ScheduleClock::ZONE)->format('M j, Y g:i A') }}. The account remains active until it is reviewed.</p>
        @else
            <form action="{{ route('volunteer.settings.update') }}" method="POST" class="mt-4" onsubmit="return confirm('Send a deactivation request? Your account stays active until it is reviewed.');">
                @csrf
                <input type="hidden" name="section" value="deactivate">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700">Request deactivation</button>
            </form>
        @endif
    </section>

    <form action="{{ route('profile.update') }}" method="POST" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf
        @method('PUT')
        <input type="hidden" name="section" value="password">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Change password</h2>
        <label class="block text-xs text-slate-500">Current password
            <input type="password" name="current_password" required autocomplete="current-password" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <label class="block text-xs text-slate-500">New password
            <input type="password" name="password" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <label class="block text-xs text-slate-500">Confirm new password
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <button type="submit" class="rounded-lg bg-jci-blue px-4 py-2 text-sm font-semibold text-white">Save password</button>
    </form>

    <form action="{{ route('volunteer.settings.update') }}" method="POST" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf
        <input type="hidden" name="section" value="email">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Update email</h2>
        <p class="text-xs text-slate-400">Email verification is not turned on, so the address changes after your password is confirmed.</p>
        <label class="block text-xs text-slate-500">Email
            <input type="email" name="email" required value="{{ old('email', $volunteer->email) }}" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <label class="block text-xs text-slate-500">Current password
            <input type="password" name="current_password" required autocomplete="current-password" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <button type="submit" class="rounded-lg bg-jci-blue px-4 py-2 text-sm font-semibold text-white">Save email</button>
    </form>

    <form action="{{ route('volunteer.settings.update') }}" method="POST" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf
        <input type="hidden" name="section" value="preferences">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Notification preferences</h2>
        @foreach(['assignments' => 'Task assignments', 'events' => 'Event notices', 'certificates' => 'Certificates', 'reminders' => 'Reminders and other notices'] as $key => $label)
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="checkbox" name="{{ $key }}" value="1" @checked($preferences[$key] ?? true)>
                {{ $label }}
            </label>
        @endforeach
        <button type="submit" class="rounded-lg bg-jci-blue px-4 py-2 text-sm font-semibold text-white">Save preferences</button>
    </form>
</div>
