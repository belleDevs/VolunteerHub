@props(['active' => 'dashboard'])

@php
    $orgUser = Auth::user();
    $orgInitial = strtoupper(substr($orgUser->name, 0, 1));
    $orgUnreadCount = \App\Support\OrgNotifier::unreadCount((int) $orgUser->id);
    $nav = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-table-cells', 'href' => route('org.dashboard')],
        ['key' => 'volunteers', 'label' => 'Volunteers', 'icon' => 'fa-users', 'href' => route('org.dashboard', ['section' => 'volunteers'])],
        ['key' => 'officers', 'label' => 'Officers', 'icon' => 'fa-user-tie', 'href' => route('org.dashboard', ['section' => 'officers'])],
        ['key' => 'events', 'label' => 'Events', 'icon' => 'fa-calendar', 'href' => route('org.dashboard', ['section' => 'events'])],
        ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'fa-images', 'href' => route('org.dashboard', ['section' => 'gallery'])],
        ['key' => 'tasks', 'label' => 'Task Assignment', 'icon' => 'fa-list-check', 'href' => route('org.dashboard', ['section' => 'tasks'])],
        ['key' => 'messages', 'label' => 'Messages', 'icon' => 'fa-comment', 'href' => route('org.dashboard', ['section' => 'messages'])],
        ['key' => 'certificates', 'label' => 'Certificates', 'icon' => 'fa-certificate', 'href' => route('org.dashboard', ['section' => 'certificates'])],
        ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'fa-bell', 'href' => route('org.dashboard', ['section' => 'notifications'])],
        ['key' => 'reports', 'label' => 'Reports', 'icon' => 'fa-chart-column', 'href' => route('org.reports')],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'fa-gear', 'href' => route('org.dashboard', ['section' => 'settings'])],
    ];
@endphp

<x-layout.app title="VolunteerHub - Organization Portal">
    <style>
        @media (min-width: 768px) {
            html.org-collapsed #org-sidebar {
                width: 0;
                overflow: visible;
                border: 0;
                background: transparent;
            }
            html.org-collapsed #org-sidebar > :not(#org-sidebar-foot) { display: none; }
            html.org-collapsed #org-sidebar-foot {
                position: fixed;
                right: 1rem;
                bottom: 1rem;
                z-index: 40;
                width: auto;
                border: 0;
                padding: 0;
                background: transparent;
            }
            html.org-collapsed #org-sidebar-foot form { display: none; }
            html.org-collapsed #org-sidebar-foot .mt-1 { margin: 0; }
            html.org-collapsed #org-collapse-toggle {
                border: 1px solid #e2e8f0;
                background: #fff;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            }
            html.dark.org-collapsed #org-collapse-toggle {
                border-color: #334155;
                background: #0f172a;
            }
            html.org-collapsed #org-page { max-width: none; }
        }
    </style>
    <script>
        try {
            if (localStorage.getItem('orgSidebarCollapsed') === '1' && window.matchMedia('(min-width: 768px)').matches) {
                document.documentElement.classList.add('org-collapsed');
            }
        } catch (e) {}
    </script>
    <div id="org-shell" class="org-shell flex h-screen overflow-hidden bg-[#f5f7fb] text-slate-800 dark:bg-[#0b1120] dark:text-slate-100">
        <div id="org-sidebar-backdrop" class="fixed inset-0 z-40 bg-slate-900/40 hidden md:hidden"></div>

        <aside id="org-sidebar" class="flex h-full w-64 shrink-0 flex-col border-r border-sky-100 bg-[#e8f5fc] dark:border-slate-800 dark:bg-[#0c1222] max-md:fixed max-md:inset-y-0 max-md:left-0 max-md:z-50 max-md:-translate-x-full">
            <div class="flex items-center gap-3 px-5 py-5">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-jci-blue text-base font-black text-white shadow-sm shadow-sky-500/30">V</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold tracking-tight text-slate-900 dark:text-white">VolunteerHub</p>
                    <p class="text-[11px] font-medium text-slate-400">Organization</p>
                </div>
            </div>

            <div class="px-4 pb-2">
                <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Workspace</p>
            </div>

            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 pb-4 custom-scrollbar">
                @foreach($nav as $item)
                    @php $isActive = $active === $item['key']; @endphp
                    <a href="{{ $item['href'] }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-medium {{ $isActive ? 'bg-jci-blue text-white shadow-sm shadow-sky-500/20' : 'text-slate-600 dark:text-slate-300' }}">
                        <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-sm {{ $isActive ? 'text-white' : 'text-slate-400' }}"></i>
                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                        @if($item['key'] === 'notifications' && ($orgUnreadCount ?? 0) > 0)
                            <span class="rounded-full {{ $isActive ? 'bg-white text-jci-blue' : 'bg-jci-blue text-white' }} px-1.5 py-0.5 text-[10px] font-semibold">{{ $orgUnreadCount }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div id="org-sidebar-foot" class="border-t border-slate-200 p-3 dark:border-slate-800">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-medium text-slate-600 dark:text-slate-300">
                        <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center text-sm text-slate-400"></i>
                        Logout
                    </button>
                </form>
                <div class="mt-1 flex justify-end">
                    <button type="button" id="org-collapse-toggle" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white hover:text-jci-blue dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-sky-300" aria-label="Collapse sidebar">
                        <i id="org-collapse-icon" class="fa-solid fa-compress text-sm"></i>
                    </button>
                </div>
            </div>
        </aside>

        <div id="org-content" class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto">
            <header class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur dark:border-slate-800 dark:bg-[#0c1222]/90 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" id="org-sidebar-toggle" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 md:hidden" title="Toggle sidebar">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Organization</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="org-theme-toggle" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-jci-blue dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-sky-300" title="Switch to dark mode" aria-label="Switch color theme">
                        <i id="org-theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    <a href="{{ route('profile.show') }}" class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-slate-200 text-slate-500 transition hover:border-jci-blue hover:text-jci-blue dark:border-slate-600 dark:text-slate-300" title="{{ $orgUser->name }}">
                        @if($orgUser->profile_photo_path)
                            <img src="{{ asset('storage/' . $orgUser->profile_photo_path) }}" alt="{{ $orgUser->name }}" class="h-full w-full object-cover">
                        @else
                            <span class="text-xs font-bold text-jci-blue">{{ $orgInitial }}</span>
                        @endif
                    </a>
                </div>
            </header>

            <main class="flex-1">
                <div id="org-page" class="mx-auto w-full max-w-[1200px] px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <script>
        (function () {
            const sidebar = document.getElementById('org-sidebar');
            const backdrop = document.getElementById('org-sidebar-backdrop');
            const toggle = document.getElementById('org-sidebar-toggle');
            const collapseButton = document.getElementById('org-collapse-toggle');
            const collapseIcon = document.getElementById('org-collapse-icon');
            const themeButton = document.getElementById('org-theme-toggle');
            const themeIcon = document.getElementById('org-theme-icon');
            const root = document.documentElement;

            function paintCollapse() {
                const collapsed = root.classList.contains('org-collapsed');
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
                const collapsed = root.classList.toggle('org-collapsed');
                localStorage.setItem('orgSidebarCollapsed', collapsed ? '1' : '0');
                paintCollapse();
            });

            backdrop.addEventListener('click', closeMobile);
        })();
    </script>
</x-layout.app>
