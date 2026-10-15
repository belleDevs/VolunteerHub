@php
    $panes = [
        'profile' => 'Organization Profile',
        'security' => 'Account & Security',
        'notifications' => 'Notification Preferences',
        'preferences' => 'Preferences',
        'documents' => 'Verification & Documents',
    ];
    $field = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100';
    $verificationClass = match ($organization->verification_status) {
        'verified' => 'bg-emerald-50 text-emerald-700',
        'rejected' => 'bg-rose-50 text-rose-700',
        'additional_information' => 'bg-amber-50 text-amber-700',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp
<div class="grid items-start gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
    <nav class="rounded-2xl border border-slate-200 bg-white p-2 dark:border-slate-800 dark:bg-slate-900">
        @foreach($panes as $key => $label)
            <a href="{{ route('org.dashboard', ['section' => 'settings', 'pane' => $key]) }}"
               class="block rounded-xl px-3 py-2.5 text-sm font-medium {{ $settingsPane === $key ? 'bg-jci-blue text-white' : 'text-slate-600 hover:text-jci-blue dark:text-slate-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ $panes[$settingsPane] }}</h1>
        @if(session('success'))
            <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        @endif
        @if($errors->any())
            <div class="mt-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if($settingsPane === 'profile')
            <p class="mt-1 text-sm text-slate-500">Update this organization's public details. The login email is changed under Account & Security.</p>
            <form action="{{ route('org.settings.profile') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div class="flex items-center gap-4">
                    @if($organization->profile_photo_path)
                        <img src="{{ asset('storage/'.$organization->profile_photo_path) }}" alt="{{ $organization->name }}" class="h-16 w-16 rounded-2xl object-cover">
                    @else
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-jci-blue text-xl font-semibold text-white">{{ strtoupper(substr($organization->name, 0, 1)) }}</span>
                    @endif
                    <label class="text-sm text-slate-600 dark:text-slate-300">
                        Organization logo
                        <input name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block text-xs">
                    </label>
                </div>
                <label class="block text-sm text-slate-600 dark:text-slate-300">Organization name
                    <input name="name" value="{{ old('name', $organization->name) }}" required maxlength="255" class="{{ $field }} mt-1">
                </label>
                <label class="block text-sm text-slate-600 dark:text-slate-300">Organization type
                    <select name="organization_type" required class="{{ $field }} mt-1">
                        @foreach(\App\Models\User::ORGANIZATION_TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('organization_type', $organization->organization_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm text-slate-600 dark:text-slate-300">Description and mission
                    <textarea name="bio" rows="4" maxlength="2000" class="{{ $field }} mt-1">{{ old('bio', $organization->bio) }}</textarea>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm text-slate-600 dark:text-slate-300">Authorized representative
                        <input name="representative_name" value="{{ old('representative_name', $organization->representative_name) }}" maxlength="255" class="{{ $field }} mt-1">
                    </label>
                    <label class="block text-sm text-slate-600 dark:text-slate-300">Contact email
                        <input name="contact_email" type="email" value="{{ old('contact_email', $organization->contact_email) }}" maxlength="255" placeholder="{{ $organization->email }}" class="{{ $field }} mt-1">
                    </label>
                    <label class="block text-sm text-slate-600 dark:text-slate-300">Phone number
                        <input name="phone" value="{{ old('phone', $organization->phone) }}" maxlength="30" class="{{ $field }} mt-1">
                    </label>
                    <label class="block text-sm text-slate-600 dark:text-slate-300">Website
                        <input name="website" type="url" value="{{ old('website', $organization->website) }}" maxlength="255" placeholder="https://" class="{{ $field }} mt-1">
                    </label>
                </div>
                <label class="block text-sm text-slate-600 dark:text-slate-300">Office address
                    <input name="address" value="{{ old('address', $organization->address) }}" maxlength="500" class="{{ $field }} mt-1">
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm text-slate-600 dark:text-slate-300">Facebook
                        <input name="facebook_url" type="url" value="{{ old('facebook_url', $organization->facebook_url) }}" maxlength="255" placeholder="https://" class="{{ $field }} mt-1">
                    </label>
                    <label class="block text-sm text-slate-600 dark:text-slate-300">Instagram
                        <input name="instagram_url" type="url" value="{{ old('instagram_url', $organization->instagram_url) }}" maxlength="255" placeholder="https://" class="{{ $field }} mt-1">
                    </label>
                </div>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save Changes</button>
            </form>
        @elseif($settingsPane === 'security')
            <p class="mt-1 text-sm text-slate-500">The login account stays an organization account. It cannot be changed to an administrator.</p>
            <form action="{{ route('org.settings.security') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <label class="block text-sm text-slate-600 dark:text-slate-300">Login email
                    <input name="email" type="email" required value="{{ old('email', $organization->email) }}" class="{{ $field }} mt-1">
                </label>
                <label class="block text-sm text-slate-600 dark:text-slate-300">Current password
                    <input name="current_password" type="password" required autocomplete="current-password" class="{{ $field }} mt-1">
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm text-slate-600 dark:text-slate-300">New password
                        <input name="password" type="password" autocomplete="new-password" minlength="8" class="{{ $field }} mt-1">
                    </label>
                    <label class="block text-sm text-slate-600 dark:text-slate-300">Confirm new password
                        <input name="password_confirmation" type="password" autocomplete="new-password" class="{{ $field }} mt-1">
                    </label>
                </div>
                <p class="text-xs text-slate-400">Leave the new password blank to keep the current one. Verification status: <span class="font-semibold text-slate-600 dark:text-slate-200">{{ $organization->verificationLabel() }}</span></p>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save Changes</button>
            </form>
            <div class="mt-8 border-t border-slate-100 pt-5 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Active sessions</h2>
                <p class="mt-1 text-xs text-slate-400">Two-factor authentication is not available on VolunteerHub.</p>
                <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($accountSessions as $accountSession)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::limit($accountSession->user_agent ?: 'Unknown device', 80) }}</p>
                                <p class="text-xs text-slate-400">{{ $accountSession->ip_address ?: 'Unknown address' }} · {{ \App\Support\OrgPreferences::format(\Carbon\Carbon::createFromTimestamp($accountSession->last_activity), $organization, true) }}</p>
                            </div>
                            @if($accountSession->id === session()->getId())
                                <span class="shrink-0 rounded-full bg-jci-blue px-2.5 py-1 text-[11px] font-semibold text-white">This device</span>
                            @else
                                <form action="{{ route('org.settings.sessions.destroy', $accountSession->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600">Sign out</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="py-3 text-sm text-slate-400">No other sessions are recorded.</p>
                    @endforelse
                </div>
            </div>
        @elseif($settingsPane === 'notifications')
            <p class="mt-1 text-sm text-slate-500">Choose which updates are emailed to the login address. The inbox still records every notice.</p>
            <form action="{{ route('org.settings.notifications') }}" method="POST" class="mt-5 space-y-3">
                @csrf
                @foreach(\App\Support\OrgPreferences::NOTIFICATION_KEYS as $key => $label)
                    <input type="hidden" name="{{ $key }}" value="0">
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-3 text-sm text-slate-700 dark:border-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked(filter_var(old($key, $notificationPreferences[$key]), FILTER_VALIDATE_BOOLEAN)) class="rounded border-slate-300 text-jci-blue focus:ring-jci-blue">
                        {{ $label }}
                    </label>
                @endforeach
                <p class="text-xs text-slate-400">SMS notifications are not available.</p>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save Changes</button>
            </form>
        @elseif($settingsPane === 'preferences')
            <p class="mt-1 text-sm text-slate-500">These choices change how dates appear for this organization. VolunteerHub is available in English.</p>
            <form action="{{ route('org.settings.preferences') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <label class="block text-sm text-slate-600 dark:text-slate-300">Language
                    <select name="locale" class="{{ $field }} mt-1">
                        <option value="en" selected>English</option>
                    </select>
                </label>
                <label class="block text-sm text-slate-600 dark:text-slate-300">Time zone
                    <select name="timezone" required class="{{ $field }} mt-1">
                        @foreach(\App\Support\OrgPreferences::TIMEZONES as $value => $label)
                            <option value="{{ $value }}" @selected(old('timezone', $organization->timezone ?: 'Asia/Manila') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm text-slate-600 dark:text-slate-300">Date format
                    <select name="date_format" required class="{{ $field }} mt-1">
                        @foreach(\App\Support\OrgPreferences::DATE_FORMATS as $value => $label)
                            <option value="{{ $value }}" @selected(old('date_format', $organization->date_format ?: 'M j, Y') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="text-xs text-slate-400">Today looks like {{ \App\Support\OrgPreferences::format(now(), $organization, true) }}.</p>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save Changes</button>
            </form>
        @else
            <p class="mt-1 text-sm text-slate-500">Verification is decided by the administrator. This organization can submit documents, and cannot approve itself.</p>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $verificationClass }}">{{ $organization->verificationLabel() }}</span>
                @if($organization->verification_updated_at)
                    <span class="text-xs text-slate-400">Updated {{ \App\Support\OrgPreferences::format($organization->verification_updated_at, $organization, true) }}</span>
                @endif
            </div>
            @if($organization->documents_requested)
                <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-semibold">Documents requested</p>
                    <p class="mt-1">{{ $organization->documents_requested }}</p>
                </div>
            @endif
            @if($organization->verification_feedback)
                <div class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <p class="font-semibold">Administrator feedback</p>
                    <p class="mt-1">{{ $organization->verification_feedback }}</p>
                </div>
            @endif
            <form action="{{ route('org.documents.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <input type="hidden" name="return" value="settings">
                <label class="block flex-1 text-sm text-slate-600 dark:text-slate-300">Upload or resubmit a document
                    <input name="document_file" type="file" required accept=".pdf,.docx,.xlsx" class="mt-1 block w-full text-xs">
                </label>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Upload</button>
            </form>
            <div class="mt-5 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($complianceDocuments as $document)
                    @php
                        $file = $document->original_data ?? [];
                        $rawSubmitted = $file['uploaded_at'] ?? null;
                        $submittedAt = $rawSubmitted ? \Carbon\Carbon::parse($rawSubmitted) : $document->created_at;
                        $submittedHasTime = is_string($rawSubmitted) && preg_match('/\d{1,2}:\d{2}/', $rawSubmitted);
                    @endphp
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $file['filename'] ?? 'Document' }}</p>
                            <p class="text-xs text-slate-400">Submitted {{ \App\Support\OrgPreferences::format($submittedAt, $organization, $submittedHasTime || !$rawSubmitted) }}@if(!empty($file['size'])) · {{ $file['size'] }}@endif</p>
                        </div>
                        @if(!empty($file['file_path']))
                            <a href="{{ route('org.documents.download', $document) }}" class="shrink-0 text-xs font-semibold text-jci-blue">Download</a>
                        @endif
                    </div>
                @empty
                    <p class="py-6 text-sm text-slate-400">No documents have been submitted yet.</p>
                @endforelse
            </div>
        @endif
    </section>
</div>
