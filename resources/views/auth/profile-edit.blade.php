<x-layout.app title="VolunteerHub - Edit Profile">
    @php
        $dashboardRoute = route('volunteer.dashboard');
        if ($user->role === 'admin') {
            $dashboardRoute = route('admin.dashboard');
        } elseif ($user->role === 'organization') {
            $dashboardRoute = route('org.dashboard');
        }
    @endphp

    <div class="flex-grow bg-slate-50 py-8 px-4 md:px-8">
        <div class="max-w-4xl mx-auto space-y-6">
            
            <!-- Top Navigation / Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                <div class="flex items-center gap-4">
                    @php
                        $initials = collect(explode(' ', $user->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                    @endphp
                    <label for="profile-photo-input" class="relative shrink-0 cursor-pointer group" title="Change profile photo">
                        @if($user->profile_photo_path)
                            <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="{{ $user->name }}" class="h-14 w-14 rounded-2xl object-cover shadow-md border border-slate-200">
                        @else
                            <div class="h-14 w-14 rounded-2xl bg-jci-blue text-white flex items-center justify-center font-black text-xl shadow-md">
                                {{ strtoupper($initials) }}
                            </div>
                        @endif
                        <span class="absolute -bottom-1 -right-1 h-6 w-6 rounded-full bg-white border border-slate-200 text-jci-blue shadow-sm flex items-center justify-center group-hover:bg-blue-50 transition">
                            <i class="fa-solid fa-camera text-[10px]"></i>
                        </span>
                    </label>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-800">{{ $user->name }}</h2>
                            <span class="bg-blue-100 text-jci-blue text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full">
                                {{ ucfirst($user->role) }} Profile
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $user->email }} • Joined {{ $user->created_at->format('M Y') }}</p>
                    </div>
                </div>

                <a href="{{ $dashboardRoute }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition duration-200 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Return to Dashboard
                </a>
            </div>

            <!-- Flash Alert Messages -->
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2 animate-fade-in-up">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Profile Edit Form -->
            <div class="glass-card premium-shadow rounded-3xl p-6 md:p-8 bg-white border border-slate-200">
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <input id="profile-photo-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="hidden">

                    <div>
                        <h3 class="text-base font-extrabold text-slate-800 border-b border-slate-100 pb-3 flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-user-gear text-jci-blue"></i> General Information
                            </span>
                            <span class="text-[10px] font-bold text-slate-400">Click avatar to change photo</span>
                        </h3>
                        @error('profile_photo')
                            <p class="text-[10px] text-rose-500 mt-2 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                                {{ $user->role === 'organization' ? 'Organization Name' : 'Full Name' }} <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid {{ $user->role === 'organization' ? 'fa-building' : 'fa-user' }} text-xs"></i>
                                </span>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('name') border-rose-500 @enderror">
                            </div>
                            @error('name')
                                <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-envelope text-xs"></i>
                                </span>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('email') border-rose-500 @enderror">
                            </div>
                            @error('email')
                                <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Phone -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                                Phone / Contact Number
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-phone text-xs"></i>
                                </span>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none" 
                                       placeholder="e.g. 09123456789">
                            </div>
                            @error('phone')
                                <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Role status display -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                                Account Status
                            </label>
                            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold flex items-center justify-between text-slate-700">
                                <span class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Verified {{ ucfirst($user->role) }} Node
                                </span>
                                <span class="text-[10px] text-emerald-600 font-extrabold uppercase bg-emerald-100 px-2 py-0.5 rounded">Active</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bio / Statement of Intent -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                            {{ $user->role === 'organization' ? 'Organization Profile & Mission Statement' : 'Bio & Civic Intent Statement' }}
                        </label>
                        <textarea name="bio" rows="3" 
                                  class="w-full border border-slate-200 rounded-xl p-3 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none" 
                                  placeholder="Share information about your background or civic interests...">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')
                            <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Update Section -->
                    <div class="pt-4 border-t border-slate-100">
                        <h3 class="text-base font-extrabold text-slate-800 pb-3 flex items-center gap-2">
                            <i class="fa-solid fa-key text-jci-blue"></i> Change Password <span class="text-xs font-normal text-slate-400">(Optional)</span>
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">New Password</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fa-solid fa-lock text-xs"></i>
                                    </span>
                                    <input type="password" name="password" 
                                           class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('password') border-rose-500 @enderror" 
                                           placeholder="Leave blank to keep current">
                                </div>
                                @error('password')
                                    <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Confirm New Password</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fa-solid fa-lock text-xs"></i>
                                    </span>
                                    <input type="password" name="password_confirmation" 
                                           class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none" 
                                           placeholder="Re-enter new password">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ $dashboardRoute }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs px-6 py-2.5 rounded-xl transition duration-200 flex items-center gap-2 shadow-lg shadow-sky-500/20">
                            <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-layout.app>
