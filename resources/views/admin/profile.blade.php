<x-layout.admin activeTab="profile">
    @php
        $initials = collect(explode(' ', $user->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('');
        $joined = $user->created_at?->timezone(config('audit.display_timezone', 'Asia/Manila'))->format('M Y');
        $showPasswordForm = isset($errors) && ($errors->has('current_password') || $errors->has('password'));
    @endphp

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-12">
        <section class="rounded-3xl border border-slate-200/80 bg-white px-6 py-8 text-center shadow-sm xl:col-span-4 dark:border-slate-800 dark:bg-slate-900">
            @if($user->profile_photo_path)
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="{{ $user->name }}" class="mx-auto h-28 w-28 rounded-2xl object-cover shadow-sm">
            @else
                <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-2xl bg-jci-blue text-3xl font-semibold text-white shadow-md shadow-sky-500/25">{{ strtoupper($initials) }}</div>
            @endif
            <h2 class="mt-5 text-xl font-semibold text-slate-900 dark:text-white">{{ $user->name }}</h2>
            <p class="mt-1 text-sm text-slate-400">Admin @if($joined) · Joined {{ $joined }} @endif</p>

            <div class="mt-7 grid grid-cols-3 gap-2">
                <div>
                    <p class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ number_format($actions) }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Actions</p>
                </div>
                <div>
                    <p class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ number_format($orgsVerified) }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Orgs verified</p>
                </div>
                <div>
                    <p class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ number_format($reports) }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Reports</p>
                </div>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="mt-7">
                @csrf
                <input type="hidden" name="section" value="profile">
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">
                <input type="hidden" name="phone" value="{{ $user->phone }}">
                <label class="flex w-full cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-600 transition hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    Upload Avatar
                    <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="this.form.submit()">
                </label>
                @error('profile_photo')
                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </form>
        </section>

        <div class="space-y-5 xl:col-span-8">
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Personal Info</h3>
                <form action="{{ route('profile.update') }}" method="POST" class="mt-5">
                    @csrf
                    <input type="hidden" name="section" value="profile">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="text-xs font-medium text-slate-500">
                            Full Name
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('name')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="text-xs font-medium text-slate-500">
                            Role
                            <input type="text" value="Admin" readonly class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        </label>
                        <label class="text-xs font-medium text-slate-500">
                            Email
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('email')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="text-xs font-medium text-slate-500">
                            Mobile
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('phone')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                        </label>
                    </div>
                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="rounded-xl bg-jci-blue px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-sky-500/20 transition hover:bg-jci-dark">Save</button>
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-white">
                    <i class="fa-solid fa-shield-halved text-sm text-jci-blue"></i> Security
                </h3>
                <div class="mt-4 flex items-center justify-between gap-4 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-800/60">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-jci-blue">
                            <i class="fa-solid fa-key text-sm"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Password</p>
                            <p class="text-xs text-slate-400">
                                @if($passwordChangedAt)
                                    Last changed {{ $passwordChangedAt->diffForHumans() }}
                                @else
                                    No password change recorded
                                @endif
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('admin-password-form').classList.toggle('hidden')" class="shrink-0 rounded-xl bg-jci-blue px-4 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Change</button>
                </div>

                <form id="admin-password-form" action="{{ route('profile.update') }}" method="POST" class="{{ $showPasswordForm ? '' : 'hidden' }} mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <input type="hidden" name="section" value="password">
                    <label class="text-xs font-medium text-slate-500">
                        Current password
                        <input type="password" name="current_password" autocomplete="current-password" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20">
                        @error('current_password')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="text-xs font-medium text-slate-500">
                        New password
                        <input type="password" name="password" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20">
                        @error('password')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="text-xs font-medium text-slate-500">
                        Confirm password
                        <input type="password" name="password_confirmation" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20">
                    </label>
                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-semibold text-white hover:bg-jci-dark">Update password</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <a href="{{ route('admin.dashboard', ['tab' => 'chatbot']) }}" class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-jci-blue text-white shadow-lg shadow-sky-500/30 transition hover:bg-jci-dark" title="Chatbot" aria-label="Open chatbot">
        <i class="fa-regular fa-comment-dots text-xl"></i>
    </a>
</x-layout.admin>
