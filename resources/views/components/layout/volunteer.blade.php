@props(['active' => null])

@php
    $navUser = Auth::user();
    $navInitial = strtoupper(substr($navUser->name, 0, 1));
    $navActive = $active ?? (request()->routeIs('profile.show') ? 'profile' : request('section', 'dashboard'));
    $navUnread = $navUser->unreadNotifications()->count();
    $nav = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-table-cells', 'href' => route('volunteer.dashboard')],
        ['key' => 'events', 'label' => 'My Events', 'icon' => 'fa-calendar-days', 'href' => route('volunteer.dashboard', ['section' => 'events'])],
        ['key' => 'tasks', 'label' => 'Assigned Tasks', 'icon' => 'fa-list-check', 'href' => route('volunteer.dashboard', ['section' => 'tasks'])],
        ['key' => 'availability', 'label' => 'Availability', 'icon' => 'fa-clock', 'href' => route('volunteer.dashboard', ['section' => 'availability'])],
        ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'fa-bell', 'href' => route('volunteer.dashboard', ['section' => 'notifications'])],
        ['key' => 'certificates', 'label' => 'Certificates', 'icon' => 'fa-certificate', 'href' => route('volunteer.dashboard', ['section' => 'certificates'])],
        ['key' => 'skills', 'label' => 'Skill Recommendations', 'icon' => 'fa-wand-magic-sparkles', 'href' => route('volunteer.dashboard', ['section' => 'skills'])],
        ['key' => 'chatbot', 'label' => 'Chatbot Assistance', 'icon' => 'fa-robot', 'href' => route('volunteer.dashboard', ['section' => 'chatbot'])],
        ['key' => 'messages', 'label' => 'Messages', 'icon' => 'fa-envelope', 'href' => route('volunteer.dashboard', ['section' => 'messages'])],
        ['key' => 'profile', 'label' => 'Profile', 'icon' => 'fa-user', 'href' => route('profile.show')],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'fa-gear', 'href' => route('volunteer.dashboard', ['section' => 'settings'])],
    ];
@endphp

<x-layout.app title="VolunteerHub - Volunteer Portal">
    <style>
        @media (min-width: 768px) {
            html.vol-collapsed #vol-sidebar {
                width: 0;
                overflow: visible;
                border: 0;
                background: transparent;
            }
            html.vol-collapsed #vol-sidebar > :not(#vol-sidebar-foot) { display: none; }
            html.vol-collapsed #vol-sidebar-foot {
                position: fixed;
                right: 1rem;
                bottom: 1rem;
                z-index: 40;
                width: auto;
                border: 0;
                padding: 0;
                background: transparent;
            }
            html.vol-collapsed #vol-sidebar-foot form { display: none; }
            html.vol-collapsed #vol-sidebar-foot .mt-1 { margin: 0; }
            html.vol-collapsed #vol-collapse-toggle {
                border: 1px solid #e2e8f0;
                background: #fff;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            }
            html.dark.vol-collapsed #vol-collapse-toggle {
                border-color: #334155;
                background: #0f172a;
            }
            html.vol-collapsed #vol-page { max-width: none; }
        }
    </style>
    <script>
        try {
            if (localStorage.getItem('volSidebarCollapsed') === '1' && window.matchMedia('(min-width: 768px)').matches) {
                document.documentElement.classList.add('vol-collapsed');
            }
        } catch (e) {}
    </script>
    <div class="flex h-screen overflow-hidden bg-[#f5f7fb] text-slate-800 dark:bg-[#0b1120] dark:text-slate-100">
        <div id="vol-sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/40 md:hidden"></div>

        <aside id="vol-sidebar" class="flex h-full w-64 shrink-0 flex-col border-r border-sky-100 bg-[#e8f5fc] dark:border-slate-800 dark:bg-[#0c1222] max-md:fixed max-md:inset-y-0 max-md:left-0 max-md:z-50 max-md:-translate-x-full">
            <div class="flex items-center gap-3 px-5 py-5">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-jci-blue text-base font-black text-white shadow-sm shadow-sky-500/30">V</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold tracking-tight text-slate-900 dark:text-white">VolunteerHub</p>
                    <p class="text-[11px] font-medium text-slate-400">Volunteer</p>
                </div>
            </div>

            <div class="px-4 pb-2">
                <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Workspace</p>
            </div>

            <nav class="custom-scrollbar flex-1 space-y-0.5 overflow-y-auto px-3 pb-4">
                @foreach($nav as $item)
                    @php $isActive = $navActive === $item['key']; @endphp
                    <a href="{{ $item['href'] }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-medium {{ $isActive ? 'bg-jci-blue text-white shadow-sm shadow-sky-500/20' : 'text-slate-600 dark:text-slate-300' }}">
                        <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-sm {{ $isActive ? 'text-white' : 'text-slate-400' }}"></i>
                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                        @if($item['key'] === 'notifications' && $navUnread > 0)
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ $isActive ? 'bg-white text-jci-blue' : 'bg-jci-blue text-white' }}">{{ $navUnread > 99 ? '99+' : $navUnread }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div id="vol-sidebar-foot" class="border-t border-slate-200 p-3 dark:border-slate-800">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-medium text-slate-600 dark:text-slate-300">
                        <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center text-sm text-slate-400"></i>
                        Logout
                    </button>
                </form>
                <div class="mt-1 flex justify-end">
                    <button type="button" id="vol-collapse-toggle" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white hover:text-jci-blue dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-sky-300" aria-label="Collapse sidebar">
                        <i id="vol-collapse-icon" class="fa-solid fa-compress text-sm"></i>
                    </button>
                </div>
            </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto">
            <header class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur dark:border-slate-800 dark:bg-[#0c1222]/90 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" id="vol-sidebar-toggle" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 md:hidden" title="Toggle sidebar">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Volunteer</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui.notifications-bell />
                    <button type="button" id="vol-theme-toggle" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-jci-blue dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-sky-300" title="Switch to dark mode" aria-label="Switch color theme">
                        <i id="vol-theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    <a href="{{ route('profile.show') }}" class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-slate-200 text-slate-500 transition hover:border-jci-blue hover:text-jci-blue dark:border-slate-600 dark:text-slate-300" title="{{ $navUser->name }}">
                        @if($navUser->profile_photo_path)
                            <img src="{{ asset('storage/'.$navUser->profile_photo_path) }}" alt="{{ $navUser->name }}" class="h-full w-full object-cover">
                        @else
                            <span class="text-xs font-bold text-jci-blue">{{ $navInitial }}</span>
                        @endif
                    </a>
                </div>
            </header>

            <main class="flex-1">
                <div id="vol-page" class="mx-auto w-full max-w-[1200px] px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <x-ui.volunteer-chatbot />

    <script>
        (function () {
            const sidebar = document.getElementById('vol-sidebar');
            const backdrop = document.getElementById('vol-sidebar-backdrop');
            const toggle = document.getElementById('vol-sidebar-toggle');
            const collapseButton = document.getElementById('vol-collapse-toggle');
            const collapseIcon = document.getElementById('vol-collapse-icon');
            const themeButton = document.getElementById('vol-theme-toggle');
            const themeIcon = document.getElementById('vol-theme-icon');
            const root = document.documentElement;

            function paintCollapse() {
                const collapsed = root.classList.contains('vol-collapsed');
                if (collapseIcon) {
                    collapseIcon.className = collapsed ? 'fa-solid fa-expand text-sm' : 'fa-solid fa-compress text-sm';
                }
                if (collapseButton) {
                    collapseButton.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
                }
            }

            function paintTheme() {
                const dark = root.classList.contains('dark');
                if (themeIcon) {
                    themeIcon.className = dark ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
                }
                if (themeButton) {
                    const label = dark ? 'Switch to light mode' : 'Switch to dark mode';
                    themeButton.title = label;
                    themeButton.setAttribute('aria-label', label);
                }
            }

            function closeMobile() {
                sidebar.classList.add('max-md:-translate-x-full');
                backdrop.classList.add('hidden');
            }

            function openMobile() {
                sidebar.classList.remove('max-md:-translate-x-full');
                backdrop.classList.remove('hidden');
            }

            paintTheme();
            paintCollapse();

            themeButton.addEventListener('click', function () {
                const dark = root.classList.toggle('dark');
                localStorage.setItem('theme', dark ? 'dark' : 'light');
                paintTheme();
            });

            toggle.addEventListener('click', function () {
                sidebar.classList.add('max-md:transition-transform', 'max-md:duration-200');
                if (sidebar.classList.contains('max-md:-translate-x-full')) {
                    openMobile();
                } else {
                    closeMobile();
                }
            });

            collapseButton.addEventListener('click', function () {
                const collapsed = root.classList.toggle('vol-collapsed');
                localStorage.setItem('volSidebarCollapsed', collapsed ? '1' : '0');
                paintCollapse();
            });

            backdrop.addEventListener('click', closeMobile);
        })();
    </script>
</x-layout.app>
