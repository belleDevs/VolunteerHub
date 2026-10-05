<x-layout.app title="VolunteerHub - Secure Login">
    <div class="flex-grow flex items-center justify-center p-3 sm:p-6 md:p-12 min-h-screen bg-gradient-to-tr from-slate-900 via-jci-dark to-jci-blue py-6 sm:py-12">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-5xl w-full grid grid-cols-1 md:grid-cols-12 min-h-[550px] my-auto">
            
            <!-- Left Banner: Information, Mission, & Aesthetics -->
            <div class="md:col-span-5 bg-gradient-to-b from-jci-blue to-jci-dark p-6 sm:p-8 md:p-12 text-white flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-jci-accent/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <span class="bg-jci-accent text-jci-dark text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">VolunteerHub Portal</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold mt-3 sm:mt-4 tracking-tight leading-tight">Coordinate service, track impact.</h2>
                    <p class="text-xs sm:text-sm text-slate-300 mt-2 sm:mt-3 leading-relaxed">A shared volunteer management platform for organizations, volunteers, and administrators to plan events, manage duties, and verify community work.</p>
                </div>

                <div class="mt-6 sm:mt-8 relative z-10 border-t border-white/10 pt-4 sm:pt-6">
                    <p class="text-[11px] sm:text-xs text-slate-400 italic">"Community impact grows when people, tasks, and records stay connected."</p>
                    <div class="flex items-center gap-3 mt-3 sm:mt-4">
                        <i class="fa-solid fa-hands-holding-child text-jci-accent text-lg sm:text-xl"></i>
                        <div>
                            <h5 class="text-xs font-bold text-white">VolunteerHub</h5>
                            <p class="text-[10px] text-slate-400">Unified access for approved platform users</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Form: Unified Access Credentials -->
            <div class="md:col-span-7 p-6 sm:p-8 md:p-12 flex flex-col justify-center">
                <div class="mb-6">
                    <h3 class="text-2xl font-black text-slate-900">Welcome to VolunteerHub</h3>
                    <p class="text-sm text-slate-500 mt-1">Sign in once. VolunteerHub will open the correct dashboard for your account role.</p>
                </div>

                @if (session('status'))
                    <div class="mb-4 p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-800 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-sky-600 text-sm shrink-0"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif
                @if (session('success'))
                    <div class="mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm shrink-0"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Laravel Authentication Form -->
                <form action="{{ route('login') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-envelope text-xs"></i>
                            </span>
                            <input type="email" name="email" id="auth-email" value="{{ old('email') }}" required 
                                   class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none" placeholder="Enter your email">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </span>
                            <input type="password" name="password" id="auth-password" required 
                                   class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none" placeholder="Enter your password">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 space-y-3">
                        <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs py-3 rounded-xl transition duration-300 flex items-center justify-center gap-2 shadow-lg shadow-sky-500/20">
                            <i class="fa-solid fa-right-to-bracket"></i> Secure Log In
                        </button>
                        
                        <!-- Registration Options -->
                        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
                            <span class="text-slate-400">Don't have an account?</span>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('register.volunteer') }}" class="text-jci-blue hover:underline font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-user-plus text-[10px]"></i> Volunteer
                                </a>
                                <span class="text-slate-300">/</span>
                                <a href="{{ route('register.org') }}" class="text-jci-blue hover:underline font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-building-ngo text-[10px]"></i> Organization
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-layout.app>
