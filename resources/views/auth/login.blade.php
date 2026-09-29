<x-layout.app title="VolunteerHub - Secure Login">
    <div class="flex-grow flex items-center justify-center p-3 sm:p-6 md:p-12 min-h-screen bg-gradient-to-tr from-slate-900 via-jci-dark to-jci-blue py-6 sm:py-12">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-5xl w-full grid grid-cols-1 md:grid-cols-12 min-h-[550px] my-auto">
            
            <!-- Left Banner: Information, Mission, & Aesthetics -->
            <div class="md:col-span-5 bg-gradient-to-b from-jci-blue to-jci-dark p-6 sm:p-8 md:p-12 text-white flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-jci-accent/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <span class="bg-jci-accent text-jci-dark text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Surigao City, Caraga</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold mt-3 sm:mt-4 tracking-tight leading-tight">Empowering leaders, impacting lives.</h2>
                    <p class="text-xs sm:text-sm text-slate-300 mt-2 sm:mt-3 leading-relaxed">Join the JCI Surigao Wensies digital volunteer platform to plan events, track certifications, and connect with local communities.</p>
                </div>

                <div class="mt-6 sm:mt-8 relative z-10 border-t border-white/10 pt-4 sm:pt-6">
                    <p class="text-[11px] sm:text-xs text-slate-400 italic">"Socio-civic leadership starts with simple actions. Coordinate, volunteer, and make a persistent difference."</p>
                    <div class="flex items-center gap-3 mt-3 sm:mt-4">
                        <i class="fa-solid fa-hands-holding-child text-jci-accent text-lg sm:text-xl"></i>
                        <div>
                            <h5 class="text-xs font-bold text-white">JCI Surigao Wensies Chapter</h5>
                            <p class="text-[10px] text-slate-400">Official VolunteerHub Portal</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Form: Actual Role Selection & Access Credentials -->
            <div class="md:col-span-7 p-6 sm:p-8 md:p-12 flex flex-col justify-center">
                <div class="mb-6">
                    <h3 class="text-2xl font-black text-slate-900">Welcome to VolunteerHub</h3>
                    <p class="text-sm text-slate-500 mt-1">Please select your account type and enter credentials to continue.</p>
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

                <!-- Role Selector -->
                <div class="grid grid-cols-3 gap-2 mb-6">
                    <button type="button" onclick="selectAuthRole('admin')" id="authRole-admin" class="auth-role-btn border-2 border-jci-blue bg-blue-50/50 text-jci-blue rounded-xl p-3 flex flex-col items-center justify-center text-center transition duration-200 hover:bg-blue-50">
                        <i class="fa-solid fa-user-shield text-lg mb-1"></i>
                        <span class="text-[11px] font-bold">Admin</span>
                    </button>
                    <button type="button" onclick="selectAuthRole('org')" id="authRole-org" class="auth-role-btn border border-slate-200 text-slate-600 rounded-xl p-3 flex flex-col items-center justify-center text-center transition duration-200 hover:border-jci-blue hover:text-jci-blue hover:bg-blue-50/20">
                        <i class="fa-solid fa-building-ngo text-lg mb-1"></i>
                        <span class="text-[11px] font-bold">Organization</span>
                    </button>
                    <button type="button" onclick="selectAuthRole('volunteer')" id="authRole-volunteer" class="auth-role-btn border border-slate-200 text-slate-600 rounded-xl p-3 flex flex-col items-center justify-center text-center transition duration-200 hover:border-jci-blue hover:text-jci-blue hover:bg-blue-50/20">
                        <i class="fa-solid fa-handshake-angle text-lg mb-1"></i>
                        <span class="text-[11px] font-bold">Volunteer</span>
                    </button>
                </div>

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
                                <span class="text-slate-300">•</span>
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

    <!-- Auth JS to handle UI interaction -->
    <script>
        function selectAuthRole(role) {
            // Remove active classes from all buttons
            document.querySelectorAll('.auth-role-btn').forEach(btn => {
                btn.classList.remove('border-2', 'border-jci-blue', 'bg-blue-50/50', 'text-jci-blue');
                btn.classList.add('border-slate-200', 'text-slate-600');
            });
            
            // Add active classes to selected button
            const activeBtn = document.getElementById('authRole-' + role);
            if (activeBtn) {
                activeBtn.classList.remove('border-slate-200', 'text-slate-600');
                activeBtn.classList.add('border-2', 'border-jci-blue', 'bg-blue-50/50', 'text-jci-blue');
            }
        }
    </script>
</x-layout.app>
