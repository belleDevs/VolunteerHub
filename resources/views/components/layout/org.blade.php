<x-layout.app title="VolunteerHub - Organization Portal">
    <div class="flex-grow flex flex-col min-h-screen">
        <!-- JCI Corporate Style Top Header -->
        <header class="bg-jci-blue text-white shadow-md border-b-4 border-jci-accent sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-3 sm:py-4 flex justify-between items-center gap-3">
                <!-- Brand Section -->
                <div class="flex items-center gap-2.5 min-w-0">
                    @if(Auth::user()->profile_photo_path)
                        <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="{{ Auth::user()->name }}" class="h-10 w-10 rounded-lg object-cover bg-white shrink-0 border border-white/30">
                    @else
                        <div class="p-2 bg-white rounded-lg text-jci-blue shrink-0"><i class="fa-solid fa-building text-base sm:text-lg"></i></div>
                    @endif
                    <div class="min-w-0">
                        <span class="bg-jci-accent text-jci-dark text-[8px] sm:text-[9px] font-black uppercase px-2 py-0.5 rounded leading-none inline-block">Active Partner Org</span>
                        <h2 class="font-extrabold text-xs sm:text-base tracking-tight truncate">{{ Auth::user()->name }}</h2>
                    </div>
                </div>
                
                <!-- Navigation / Actions -->
                <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                    <a href="{{ route('profile.show') }}" class="text-xs text-white hover:text-jci-accent font-bold flex items-center gap-1.5 transition px-2 py-1 rounded-lg hover:bg-white/10">
                        <i class="fa-solid fa-user-pen text-sky-200 text-xs"></i>
                        <span class="hidden sm:inline">Edit Profile</span>
                    </a>
                    <div class="h-5 w-px bg-blue-600/50"></div>
                    <x-ui.notifications-bell color="white" />
                    <div class="h-5 w-px bg-blue-600/50"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-rose-500/20 hover:bg-rose-500/80 text-white border border-rose-500/30 text-[10px] font-bold px-2.5 py-1 rounded-lg transition duration-200 flex items-center gap-1">
                            <i class="fa-solid fa-sign-out-alt"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Org Main Dashboard Canvas -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 sm:py-8 grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 flex-grow w-full">
            {{ $slot }}
        </main>
    </div>
</x-layout.app>
