@props(['activeTab' => 'dashboard'])

<x-layout.app title="VolunteerHub - Admin Portal">
    <div class="flex-grow flex flex-col md:flex-row min-h-screen">
        
        <!-- Mobile Top Navbar for Admin -->
        <div class="md:hidden bg-slate-950 text-white p-4 flex items-center justify-between border-b border-slate-800 sticky top-0 z-40">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-shield-halved text-jci-accent text-lg"></i>
                <h2 class="font-extrabold text-xs tracking-wide">Admin Panel</h2>
            </div>
            <button type="button" onclick="document.getElementById('admin-sidebar').classList.toggle('hidden')" class="p-2 bg-slate-800 rounded-lg text-slate-300 hover:text-white text-xs">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
        </div>

        <!-- Sidebar Navigation -->
        <aside id="admin-sidebar" class="hidden md:flex w-full md:w-64 bg-slate-900 text-white flex-col justify-between border-r border-slate-800 shrink-0">
            <div>
                <!-- Sidebar Header branding -->
                <div class="p-6 bg-slate-950 border-b border-slate-800 hidden md:flex items-center gap-3">
                    <i class="fa-solid fa-shield-halved text-jci-accent text-xl"></i>
                    <div>
                        <h2 class="font-extrabold text-sm tracking-wide">VolunteerHub</h2>
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">Admin Panel</p>
                    </div>
                </div>
                
                <!-- Sidebar Links -->
                <nav class="p-4 space-y-1">
                    <a href="{{ route('admin.dashboard', ['tab' => 'dashboard']) }}" 
                       class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all {{ $activeTab === 'dashboard' ? 'text-white bg-jci-blue shadow-lg shadow-sky-500/20' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-chart-line text-sm w-4"></i> Dashboard
                    </a>
                    
                    <a href="{{ route('admin.dashboard', ['tab' => 'audits']) }}" 
                       class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all {{ $activeTab === 'audits' ? 'text-white bg-jci-blue shadow-lg shadow-sky-500/20' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-clipboard-check text-sm w-4"></i> Org Registrations
                        @php
                            $pendingCount = \App\Models\User::where('role', 'organization')->where('status', 'pending')->count();
                        @endphp
                        @if ($pendingCount > 0)
                            <span class="ml-auto bg-amber-500 text-jci-dark text-[9px] px-2 py-0.5 rounded-full font-black animate-pulse">{{ $pendingCount }}</span>
                        @endif
                    </a>
                    
                    <a href="{{ route('admin.dashboard', ['tab' => 'chatbot']) }}" 
                       class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all {{ $activeTab === 'chatbot' ? 'text-white bg-jci-blue shadow-lg shadow-sky-500/20' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-robot text-sm w-4"></i> Chatbot Setup
                    </a>
                    
                    <a href="{{ route('admin.dashboard', ['tab' => 'broadcast']) }}" 
                       class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all {{ $activeTab === 'broadcast' ? 'text-white bg-jci-blue shadow-lg shadow-sky-500/20' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-tower-broadcast text-sm w-4"></i> Broadcaster
                    </a>
                </nav>
            </div>

            <!-- Sidebar Footer Info / Logout -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                <a href="{{ route('profile.show') }}" class="flex items-center gap-3 mb-4 group hover:bg-slate-800/60 p-2 rounded-xl transition">
                    @php
                        $adminUser = Auth::user();
                        $initials = collect(explode(' ', $adminUser->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                    @endphp
                    @if($adminUser->profile_photo_path)
                        <img src="{{ asset('storage/' . $adminUser->profile_photo_path) }}" alt="{{ $adminUser->name }}" class="h-8 w-8 rounded-full object-cover shrink-0 border border-slate-700">
                    @else
                        <div class="h-8 w-8 rounded-full bg-jci-accent text-jci-dark flex items-center justify-center font-bold text-xs shrink-0">
                            {{ strtoupper($initials) }}
                        </div>
                    @endif
                    <div class="flex-grow min-w-0">
                        <h4 class="text-xs font-bold text-white truncate group-hover:text-jci-accent transition">{{ Auth::user()->name }}</h4>
                        <p class="text-[10px] text-slate-400 font-semibold group-hover:text-white flex items-center gap-1">
                            <i class="fa-solid fa-user-pen text-[9px]"></i> Edit Profile
                        </p>
                    </div>
                </a>
                
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-rose-600/20 hover:bg-rose-600 text-rose-500 hover:text-white text-[11px] font-bold py-2 rounded-lg transition duration-200 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-power-off text-xs"></i> Log Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <main class="flex-grow flex flex-col bg-slate-100 min-w-0">
            <!-- Topbar indicator -->
            <header class="bg-white border-b border-slate-200 py-3.5 px-4 sm:px-6 md:px-8 flex items-center justify-between sticky top-0 z-30 shadow-sm">
                <h3 class="font-extrabold text-xs sm:text-sm text-slate-800 flex items-center gap-2 truncate">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span> Active Administrative Node
                </h3>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-xs text-slate-400 font-semibold hidden sm:inline">Region XIII Surigao</span>
                    <div class="h-5 w-px bg-slate-200 hidden sm:block"></div>
                    <x-ui.notifications-bell />
                </div>
            </header>

            <!-- Workspace Content -->
            <div class="p-4 sm:p-6 md:p-8 space-y-6 flex-grow overflow-y-auto">
                {{ $slot }}
            </div>
        </main>
    </div>
</x-layout.app>
