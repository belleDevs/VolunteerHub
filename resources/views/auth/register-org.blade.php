<x-layout.app title="VolunteerHub - Partner Organization Registration">
    <div class="flex-grow flex items-center justify-center p-3 sm:p-6 md:p-12 min-h-screen bg-gradient-to-tr from-slate-900 via-jci-dark to-jci-blue py-6 sm:py-12">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-5xl w-full grid grid-cols-1 md:grid-cols-12 my-auto">
            
            <!-- Left Banner: Organization Focus -->
            <div class="md:col-span-5 bg-gradient-to-b from-jci-blue to-jci-dark p-6 sm:p-8 md:p-12 text-white flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-jci-accent/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <span class="bg-jci-accent text-jci-dark text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Partner Application</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold mt-3 sm:mt-4 tracking-tight leading-tight">Partner with JCI Surigao Wensies.</h2>
                    <p class="text-xs sm:text-sm text-slate-200 mt-2 sm:mt-3 leading-relaxed">Register your socio-civic organization, NGO, or municipal partner to post community projects, recruit skilled volunteers, and issue certified credentials.</p>
                </div>

                <div class="mt-6 sm:mt-8 relative z-10 border-t border-white/10 pt-4 sm:pt-6">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 sm:p-3 bg-white/10 rounded-2xl shrink-0">
                            <i class="fa-solid fa-shield-halved text-jci-accent text-xl sm:text-2xl"></i>
                        </div>
                        <div>
                            <h5 class="text-xs font-bold text-white">Compliance & Security Notice</h5>
                            <p class="text-[10px] text-slate-300 mt-0.5 leading-normal">Organization registrations undergo mandatory compliance audit by the Admin team to ensure authenticity before dashboard activation.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Form: Organization Registration -->
            <div class="md:col-span-7 p-6 sm:p-8 md:p-12 flex flex-col justify-center">
                
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900">Organization Registration</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Apply for a partner organization node.</p>
                    </div>
                    <a href="{{ route('login') }}" class="text-xs font-bold text-jci-blue hover:underline">
                        <i class="fa-solid fa-arrow-left"></i> Log In
                    </a>
                </div>

                <!-- Registration Type Selector Tabs -->
                <div class="grid grid-cols-2 gap-2 mb-6">
                    <a href="{{ route('register.volunteer') }}" class="border border-slate-200 text-slate-600 rounded-xl p-2.5 flex items-center justify-center gap-2 font-bold text-xs hover:border-jci-blue hover:text-jci-blue hover:bg-blue-50/20 transition">
                        <i class="fa-solid fa-handshake-angle text-sm"></i>
                        <span>Volunteer</span>
                    </a>
                    <a href="{{ route('register.org') }}" class="border-2 border-jci-blue bg-blue-50/50 text-jci-blue rounded-xl p-2.5 flex items-center justify-center gap-2 font-bold text-xs shadow-sm">
                        <i class="fa-solid fa-building-ngo text-sm"></i>
                        <span>Organization</span>
                    </a>
                </div>

                <form action="{{ route('register.org') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="hidden" aria-hidden="true">
                        <label for="organization-website">Website</label>
                        <input id="organization-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- Organization Name -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Organization Name <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-building text-xs"></i>
                            </span>
                            <input type="text" name="name" value="{{ old('name') }}" required 
                                   class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('name') border-rose-500 @enderror" 
                                   placeholder="e.g. Surigao Environmental Alliance">
                        </div>
                        @error('name')
                            <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email & Phone Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Official Email <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-envelope text-xs"></i>
                                </span>
                                <input type="email" name="email" value="{{ old('email') }}" required 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('email') border-rose-500 @enderror" 
                                       placeholder="contact@org.ph">
                            </div>
                            @error('email')
                                <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Contact Phone <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-phone text-xs"></i>
                                </span>
                                <input type="text" name="phone" value="{{ old('phone') }}" required 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('phone') border-rose-500 @enderror" 
                                       placeholder="09123456789">
                            </div>
                            @error('phone')
                                <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Password & Password Confirmation -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Account Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                </span>
                                <input type="password" name="password" required 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('password') border-rose-500 @enderror" 
                                       placeholder="At least 8 characters">
                            </div>
                            @error('password')
                                <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Confirm Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                </span>
                                <input type="password" name="password_confirmation" required 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none" 
                                       placeholder="Re-enter password">
                            </div>
                        </div>
                    </div>

                    <!-- Bio / Organization Profile -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Organization Profile / Mission Statement <span class="text-rose-500">*</span></label>
                        <textarea name="bio" rows="3" required 
                                  class="w-full border border-slate-200 rounded-xl p-3 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none @error('bio') border-rose-500 @enderror" 
                                  placeholder="Describe your organization's mission, civic initiatives, and background...">{{ old('bio') }}</textarea>
                        @error('bio')
                            <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Compliance Review Callout -->
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-2.5 text-amber-800 text-xs font-semibold">
                        <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm shrink-0"></i>
                        <span>Upon submission, your application will enter the Admin compliance review queue before activation.</span>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs py-3.5 rounded-xl transition duration-300 flex items-center justify-center gap-2 shadow-lg shadow-sky-500/20">
                            <i class="fa-solid fa-paper-plane"></i> Submit Application for Audit
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center text-xs text-slate-400">
                    Already registered? <a href="{{ route('login') }}" class="text-jci-blue font-bold hover:underline">Log in to your account</a>
                </div>

            </div>
        </div>
    </div>
</x-layout.app>
