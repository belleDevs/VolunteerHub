<x-layout.app title="VolunteerHub - Volunteer Portal">
    <div class="flex-grow flex flex-col min-h-screen bg-slate-50">
        <!-- Custom volunteer top nav -->
        <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-3.5 sticky top-0 z-40 shadow-sm">
            <div class="max-w-7xl mx-auto flex justify-between items-center gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                    @php
                        $navUser = Auth::user();
                        $initials = collect(explode(' ', $navUser->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                    @endphp
                    @if($navUser->profile_photo_path)
                        <img src="{{ asset('storage/' . $navUser->profile_photo_path) }}" alt="{{ $navUser->name }}" class="h-9 w-9 sm:h-10 sm:w-10 rounded-full object-cover shrink-0 border border-slate-200">
                    @else
                        <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-jci-blue text-white flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                            {{ strtoupper($initials) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h3 class="font-black text-xs sm:text-sm text-slate-800 truncate">{{ Auth::user()->name }}</h3>
                        <p class="text-[9px] sm:text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> <span class="hidden sm:inline">Active </span>Volunteer
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                    <a href="{{ route('profile.show') }}" class="text-slate-600 hover:text-jci-blue text-xs font-bold flex items-center gap-1.5 transition px-2 py-1 rounded-lg hover:bg-slate-50">
                        <i class="fa-solid fa-user-pen text-slate-400 text-xs"></i>
                        <span class="hidden sm:inline">Profile</span>
                    </a>
                    <div class="h-5 w-px bg-slate-200"></div>
                    <x-ui.notifications-bell />
                    <div class="h-5 w-px bg-slate-200"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-rose-600 text-xs font-bold flex items-center gap-1 px-1.5 py-1 rounded-lg transition" title="Logout">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 sm:py-8 grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 w-full flex-grow">
            {{ $slot }}
        </main>

        <!-- Floating AI Chatbot Widget -->
        <x-ui.volunteer-chatbot />
    </div>
</x-layout.app>
