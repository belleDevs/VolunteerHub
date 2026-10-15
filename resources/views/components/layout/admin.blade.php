@props(['activeTab' => 'dashboard'])

<x-layout.app title="VolunteerHub - Admin Portal">
    <div id="admin-shell" class="flex-grow flex flex-col md:flex-row min-h-screen md:h-screen md:overflow-hidden bg-[#f4f7fb] dark:bg-[#0b1120]">
        <style>
            @media (min-width: 768px) {
                #admin-shell.is-sidebar-hidden #admin-sidebar { display: none; }
                #admin-shell.is-sidebar-hidden #admin-sidebar-show { display: inline-flex; }
            }
        </style>

        <!-- Mobile top bar -->
        <div class="md:hidden bg-white text-slate-800 p-4 flex items-center justify-between border-b border-slate-200 sticky top-0 z-40">
            <div class="flex items-center gap-2.5">
                <span class="h-9 w-9 rounded-xl bg-jci-blue text-white flex items-center justify-center shadow-sm">
                    <i class="fa-solid fa-shield-halved text-sm"></i>
                </span>
                <div>
                    <h2 class="font-extrabold text-sm tracking-tight leading-none">VolunteerHub</h2>
                    <p class="text-[9px] text-jci-blue uppercase tracking-widest font-bold mt-0.5">Admin</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('admin-sidebar').classList.toggle('hidden')" class="p-2 bg-slate-100 rounded-lg text-slate-600 hover:text-slate-900 text-xs">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
        </div>

        <!-- Sidebar -->
        <aside id="admin-sidebar" class="hidden md:flex w-full md:w-60 md:h-screen bg-white text-slate-600 flex-col justify-between border-r border-slate-200 shrink-0 overflow-y-auto dark:bg-[#0c1222] dark:border-slate-800">
            <div>
                <div class="px-5 py-5 border-b border-slate-200 hidden md:flex items-center gap-3 dark:border-slate-800">
                    <span class="h-10 w-10 rounded-xl bg-jci-blue text-white flex items-center justify-center shadow-md shadow-sky-500/30">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                    <div>
                        <h2 class="font-extrabold text-sm tracking-tight text-slate-900 leading-none">VolunteerHub</h2>
                        <p class="text-[10px] text-jci-blue uppercase tracking-[0.18em] font-bold mt-1">Admin</p>
                    </div>
                </div>

                <nav class="p-3 space-y-1">
                    <a href="{{ route('admin.dashboard', ['tab' => 'dashboard']) }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'dashboard' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-chart-line text-sm w-4"></i> Dashboard
                    </a>

                    <a href="{{ route('admin.dashboard', ['tab' => 'audits']) }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'audits' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-clipboard-check text-sm w-4"></i> Org Registrations
                        @php
                            $pendingCount = \App\Models\User::where('role', 'organization')->where('status', 'pending')->count();
                        @endphp
                        @if ($pendingCount > 0)
                            <span class="ml-auto {{ $activeTab === 'audits' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' }} text-[10px] px-2 py-0.5 rounded-full font-black">{{ $pendingCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.dashboard', ['tab' => 'users']) }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'users' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-users-gear text-sm w-4"></i> User Lists
                    </a>

                    <a href="{{ route('admin.dashboard', ['tab' => 'volunteers']) }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'volunteers' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-user-group text-sm w-4"></i> Volunteers
                        @php
                            $volunteerTotal = \App\Models\User::where('role', 'volunteer')->count();
                        @endphp
                        @if ($volunteerTotal > 0)
                            <span class="ml-auto {{ $activeTab === 'volunteers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }} text-[10px] px-2 py-0.5 rounded-full font-black">{{ $volunteerTotal }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.dashboard', ['tab' => 'events']) }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'events' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-calendar-days text-sm w-4"></i> Events
                        @php
                            $postedEventTotal = \App\Models\Event::count();
                            $eventsAwaitingReview = \App\Models\Event::where('status', 'draft')->count();
                        @endphp
                        <span class="ml-auto flex items-center gap-1">
                            @if ($eventsAwaitingReview > 0)
                                <span class="{{ $activeTab === 'events' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' }} text-[10px] px-2 py-0.5 rounded-full font-black" title="Waiting for approval">{{ $eventsAwaitingReview }}</span>
                            @endif
                            @if ($postedEventTotal > 0)
                                <span class="{{ $activeTab === 'events' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }} text-[10px] px-2 py-0.5 rounded-full font-black">{{ $postedEventTotal }}</span>
                            @endif
                        </span>
                    </a>

                    <a href="{{ route('admin.dashboard', ['tab' => 'tasks']) }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'tasks' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-list-check text-sm w-4"></i> Tasks
                        @php
                            $postedTaskTotal = \App\Models\Task::count();
                        @endphp
                        @if ($postedTaskTotal > 0)
                            <span class="ml-auto {{ $activeTab === 'tasks' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }} text-[10px] px-2 py-0.5 rounded-full font-black">{{ $postedTaskTotal }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.dashboard', ['tab' => 'notifications']) }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'notifications' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-bell text-sm w-4"></i> Notifications
                        @php
                            $unreadNotices = \Illuminate\Support\Facades\DB::table('notifications')
                                ->where('notifiable_type', \App\Models\User::class)
                                ->where('notifiable_id', Auth::id())
                                ->whereNull('read_at')
                                ->count();
                        @endphp
                        @if ($unreadNotices > 0)
                            <span class="ml-auto {{ $activeTab === 'notifications' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-600' }} text-[10px] px-2 py-0.5 rounded-full font-black">{{ $unreadNotices }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.reports') }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'reports' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-chart-column text-sm w-4"></i> Reports
                    </a>

                    <a href="{{ route('admin.audit') }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'audit' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-clipboard-list text-sm w-4"></i> Audit Logs
                    </a>

                    <a href="{{ route('admin.dashboard', ['tab' => 'broadcast']) }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'broadcast' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-tower-broadcast text-sm w-4"></i> System Notices
                    </a>

                    <a href="{{ route('profile.show') }}"
                       class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-all {{ $activeTab === 'profile' ? 'text-white bg-jci-blue shadow-md shadow-sky-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        <i class="fa-solid fa-user text-sm w-4"></i> Profile
                    </a>
                </nav>
            </div>

            <div class="p-3 border-t border-slate-200 space-y-0.5 dark:border-slate-800">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-slate-500 hover:text-rose-600 hover:bg-rose-50 text-[13px] font-semibold py-2.5 px-3 rounded-xl transition duration-200 flex items-center gap-2.5">
                        <i class="fa-solid fa-arrow-right-from-bracket text-sm w-4"></i> Logout
                    </button>
                </form>
                <button type="button" id="admin-sidebar-hide" class="hidden md:flex w-full text-slate-500 hover:text-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-[13px] font-semibold py-2.5 px-3 rounded-xl transition duration-200 items-center gap-2.5">
                    <i class="fa-solid fa-angles-left text-sm w-4"></i> Collapse
                </button>
            </div>
        </aside>

        <!-- Main workspace -->
        <main class="flex-grow flex flex-col min-w-0 bg-gradient-to-br from-[#f7f9fc] via-[#f3f7ff] to-[#e7f2fb] md:h-screen md:overflow-hidden dark:bg-none dark:bg-[#0b1120]">
            <header class="bg-white/80 backdrop-blur border-b border-slate-200/80 py-3.5 px-4 sm:px-6 md:px-8 flex items-center justify-between gap-4 shrink-0 z-30 dark:bg-[#0c1222]/90 dark:border-slate-800">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" id="admin-sidebar-show" class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:text-jci-blue hover:border-jci-blue" title="Show sidebar">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                    <div class="min-w-0">
                    @if($activeTab === 'volunteers')
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">Volunteers</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Events joined, completed tasks, rewards, and membership</p>
                    @elseif($activeTab === 'users')
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">User Lists</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">All organization and volunteer accounts, including activity and organization links.</p>
                    @elseif($activeTab === 'organizations')
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">Organizations</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Verify and manage socio-civic organizations</p>
                    @elseif($activeTab === 'events')
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">Events</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Review, approve, and monitor events from every organization</p>
                    @elseif($activeTab === 'tasks')
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">Tasks</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Monitor deadlines, assigned volunteers, and task completion</p>
                    @elseif($activeTab === 'reports')
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">Reports</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Period, category, and export from recorded activity</p>
                    @elseif($activeTab === 'audit')
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">Audit Logs</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Immutable record of actions across the portal</p>
                    @elseif($activeTab === 'notifications')
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">Notifications</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Full notices from registrations, events, tasks, and documents</p>
                    @elseif($activeTab === 'profile')
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">My Profile</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">{{ Auth::user()->name }} · System Admin</p>
                    @else
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">Admin Command Center</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Region XIII Surigao</p>
                    @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <form action="{{ route('admin.dashboard') }}" method="GET" class="relative hidden lg:block">
                        <input type="hidden" name="tab" value="users">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-300"></i>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search volunteers, events..." class="w-64 rounded-full border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20">
                    </form>
                    <button type="button" id="admin-theme-toggle" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-jci-blue dark:text-slate-300 dark:hover:bg-slate-800" title="Switch to dark mode" aria-label="Switch color theme">
                        <i id="admin-theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    <x-ui.notifications-bell />
                    <div class="h-8 w-px bg-slate-200 hidden sm:block dark:bg-slate-700"></div>
                    @php
                        $adminUser = Auth::user();
                        $initials = collect(explode(' ', $adminUser->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                    @endphp
                    <a href="{{ route('profile.show') }}" class="flex items-center gap-2.5 group rounded-xl px-1.5 py-1 hover:bg-slate-50 transition">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-slate-800 leading-tight group-hover:text-jci-blue transition">{{ $adminUser->name }}</p>
                            <p class="text-[10px] text-slate-400 font-medium">System Admin</p>
                        </div>
                        @if($adminUser->profile_photo_path)
                            <img src="{{ asset('storage/' . $adminUser->profile_photo_path) }}" alt="{{ $adminUser->name }}" class="h-9 w-9 rounded-full object-cover shrink-0 border border-slate-200">
                        @else
                            <div class="h-9 w-9 rounded-full bg-jci-blue text-white flex items-center justify-center font-bold text-xs shrink-0">
                                {{ strtoupper($initials) }}
                            </div>
                        @endif
                    </a>
                </div>
            </header>

            <div class="p-4 sm:p-6 md:p-8 space-y-6 flex-grow overflow-y-auto">
                {{ $slot }}
            </div>
        </main>
    </div>
    <script>
        (function () {
            const shell = document.getElementById('admin-shell');
            const hideButton = document.getElementById('admin-sidebar-hide');
            const showButton = document.getElementById('admin-sidebar-show');
            if (!shell || !hideButton || !showButton) {
                return;
            }
            const apply = function (hidden) {
                shell.classList.toggle('is-sidebar-hidden', hidden);
                localStorage.setItem('admin-sidebar-hidden', hidden ? '1' : '0');
            };
            if (localStorage.getItem('admin-sidebar-hidden') === '1') {
                apply(true);
            }
            hideButton.addEventListener('click', function () { apply(true); });
            showButton.addEventListener('click', function () { apply(false); });
        })();
        (function () {
            const root = document.documentElement;
            const button = document.getElementById('admin-theme-toggle');
            const icon = document.getElementById('admin-theme-icon');
            if (!button || !icon) return;
            function paint() {
                const dark = root.classList.contains('dark');
                icon.className = dark ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
                const label = dark ? 'Switch to light mode' : 'Switch to dark mode';
                button.title = label;
                button.setAttribute('aria-label', label);
            }
            paint();
            button.addEventListener('click', function () {
                const dark = root.classList.toggle('dark');
                localStorage.setItem('theme', dark ? 'dark' : 'light');
                paint();
            });
        })();
    </script>
</x-layout.app>
