<x-layout.admin :activeTab="$activeTab">

    <!-- TAB 1: ADMIN DASHBOARD -->
    <div class="{{ $activeTab === 'dashboard' ? '' : 'hidden' }} space-y-5">
        @php
            $pendingOrgCount = $pendingOrgs->count();
            $reviewWork = $pendingOrgCount + $pendingCompletionReviews + $eventsAwaitingReview;
            $activityItems = [
                ['label' => 'Completion Reviews', 'value' => $pendingCompletionReviews, 'caption' => 'Waiting for organization approval', 'color' => '#f59e0b'],
                ['label' => 'Issued Certificates', 'value' => $issuedCertificates, 'caption' => 'Generated after verified tasks', 'color' => '#10b981'],
                ['label' => 'Chatbot Messages', 'value' => $chatbotMessageCount, 'caption' => 'Volunteer assistant history', 'color' => '#0097d6'],
                ['label' => 'System Notices', 'value' => $broadcastLogCount, 'caption' => 'Portal announcements logged', 'color' => '#a855f7'],
            ];
            $activityTotal = array_sum(array_column($activityItems, 'value'));
        @endphp

        <div class="rounded-3xl bg-gradient-to-r from-jci-blue to-sky-400 text-white px-5 py-5 sm:px-7 sm:py-6 shadow-lg shadow-sky-500/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-4 min-w-0">
                <span class="h-11 w-11 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                </span>
                <div class="min-w-0">
                    @if($reviewWork > 0)
                        <h2 class="text-lg sm:text-xl font-extrabold tracking-tight">{{ $reviewWork }} {{ $reviewWork === 1 ? 'item still needs' : 'items still need' }} review</h2>
                        <p class="text-sm text-white/85 mt-1">{{ $pendingOrgCount }} organization {{ Str::plural('registration', $pendingOrgCount) }}, {{ $pendingCompletionReviews }} completion {{ Str::plural('review', $pendingCompletionReviews) }}, and {{ $eventsAwaitingReview }} {{ Str::plural('event', $eventsAwaitingReview) }} are waiting.</p>
                    @else
                        <h2 class="text-lg sm:text-xl font-extrabold tracking-tight">Nothing is waiting for review</h2>
                        <p class="text-sm text-white/85 mt-1">{{ $approvedOrgs }} approved {{ Str::plural('organization', $approvedOrgs) }}, {{ $issuedCertificates }} issued {{ Str::plural('certificate', $issuedCertificates) }}, and {{ $broadcastLogCount }} system {{ Str::plural('notice', $broadcastLogCount) }} are on record.</p>
                    @endif
                </div>
            </div>
            @if($pendingOrgCount > 0)
                <a href="{{ route('admin.dashboard', ['tab' => 'audits']) }}" class="shrink-0 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 border border-white/20 text-white text-sm font-bold px-5 py-2.5 rounded-full transition">
                    Review registrations
                </a>
            @elseif($eventsAwaitingReview > 0)
                <a href="{{ route('admin.dashboard', ['tab' => 'events', 'event_status' => 'review']) }}" class="shrink-0 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 border border-white/20 text-white text-sm font-bold px-5 py-2.5 rounded-full transition">
                    Review events
                </a>
            @elseif($reviewWork === 0)
                <a href="{{ route('admin.dashboard', ['tab' => 'broadcast']) }}" class="shrink-0 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 border border-white/20 text-white text-sm font-bold px-5 py-2.5 rounded-full transition">
                    Open notices
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach([
                ['key' => 'volunteers', 'label' => 'Approved Volunteers', 'value' => $totalVolunteers, 'icon' => 'fa-users', 'iconBg' => 'bg-blue-50', 'iconColor' => 'text-jci-blue'],
                ['key' => 'events', 'label' => 'Current Events', 'value' => $activeEvents, 'icon' => 'fa-calendar-check', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
                ['key' => 'orgs', 'label' => 'Approved Orgs', 'value' => $approvedOrgs, 'icon' => 'fa-building', 'iconBg' => 'bg-purple-50', 'iconColor' => 'text-purple-600'],
                ['key' => 'tasks', 'label' => 'Current Open Tasks', 'value' => $openTasks, 'icon' => 'fa-list-check', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-600'],
            ] as $card)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-5">
                    <div class="flex items-start justify-between gap-3">
                        <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                            <i class="fa-solid {{ $card['icon'] }}"></i>
                        </span>
                        @if($metricTrends[$card['key']])
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-1 rounded-full {{ $metricTrends[$card['key']]['direction'] === 'up' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                <i class="fa-solid {{ $metricTrends[$card['key']]['direction'] === 'up' ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} text-[10px]"></i>
                                {{ $metricTrends[$card['key']]['label'] }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-5 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h4 class="font-extrabold text-slate-900">Volunteer Growth</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Approved signups over the last 6 months</p>
                    </div>
                    @if($volunteerGrowthTrend)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0 {{ $volunteerGrowthTrend['direction'] === 'up' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                            <i class="fa-solid {{ $volunteerGrowthTrend['direction'] === 'up' ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} text-[10px]"></i>
                            {{ $volunteerGrowthTrend['label'] }} MoM
                        </span>
                    @endif
                </div>
                <div class="h-72">
                    <canvas id="volunteerGrowthChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <div class="mb-2">
                    <h4 class="font-extrabold text-slate-900">System Activity</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Live snapshot</p>
                </div>
                <div class="h-52 relative">
                    <canvas id="activitySnapshotChart"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($activityTotal) }}</span>
                        <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Logged</span>
                    </div>
                </div>
                <ul class="mt-4 space-y-2.5">
                    @foreach($activityItems as $item)
                        <li class="flex items-start gap-2.5">
                            <span class="mt-1.5 h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $item['color'] }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="text-xs font-bold text-slate-700">{{ $item['label'] }}</span>
                                    <span class="text-xs font-black text-slate-900">{{ number_format($item['value']) }}</span>
                                </div>
                                <p class="text-[10px] text-slate-400">{{ $item['caption'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        @php
            $eventStatusTotal = array_sum(array_column($dashboardEventStatus, 'value'));
            $taskStatusTotal = collect($dashboardTaskStatus)->where('key', '!=', 'overdue')->sum('value');
        @endphp

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <h4 class="font-extrabold text-slate-900">Event status</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Counted from each event's saved status</p>
                    </div>
                    <a href="{{ route('admin.dashboard', ['tab' => 'events']) }}" class="text-xs font-bold text-jci-blue hover:underline shrink-0">Open events</a>
                </div>
                @if($eventStatusTotal === 0)
                    <p class="py-16 text-center text-sm text-slate-400">No events are on record yet.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-[220px_1fr] gap-4 items-center">
                        <div class="h-52 relative">
                            <canvas id="eventStatusChart"></canvas>
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-black text-slate-900">{{ number_format($eventStatusTotal) }}</span>
                                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Events</span>
                            </div>
                        </div>
                        <ul class="space-y-2">
                            @foreach($dashboardEventStatus as $item)
                                <li>
                                    <a href="{{ route('admin.dashboard', ['tab' => 'events', 'event_status' => $item['key']]) }}" class="flex items-center justify-between gap-3 rounded-xl px-2 py-1.5 hover:bg-slate-50">
                                        <span class="flex items-center gap-2 min-w-0">
                                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $item['color'] }}"></span>
                                            <span class="text-xs font-bold text-slate-700 truncate">{{ $item['label'] }}</span>
                                        </span>
                                        <span class="text-xs font-black text-slate-900">{{ number_format($item['value']) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <h4 class="font-extrabold text-slate-900">Task status</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Overdue is a deadline flag. Those tasks also stay in their saved status.</p>
                    </div>
                    <a href="{{ route('admin.dashboard', ['tab' => 'tasks']) }}" class="text-xs font-bold text-jci-blue hover:underline shrink-0">Open tasks</a>
                </div>
                @if($taskStatusTotal === 0)
                    <p class="py-16 text-center text-sm text-slate-400">No tasks are on record yet.</p>
                @else
                    <div class="h-64">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
            <div class="xl:col-span-3 bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <h4 class="font-extrabold text-slate-900">Participation</h4>
                <p class="text-xs text-slate-400 mt-0.5">Events that started in each of the last 6 months, and the volunteers recorded on those events</p>
                <div class="h-72 mt-4">
                    <canvas id="participationChart"></canvas>
                </div>
            </div>

            <div class="xl:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <h4 class="font-extrabold text-slate-900">Organizations</h4>
                <p class="text-xs text-slate-400 mt-0.5">Events, tasks, and volunteers recorded for each organization</p>
                @if($dashboardOrganizations->isEmpty())
                    <p class="py-16 text-center text-sm text-slate-400">No organizations are on record yet.</p>
                @else
                    <div class="h-56 mt-4">
                        <canvas id="organizationChart"></canvas>
                    </div>
                    <ul class="mt-4 space-y-2">
                        @foreach($dashboardOrganizations as $org)
                            <li>
                                <a href="{{ route('admin.dashboard', ['tab' => 'events', 'org' => $org['id']]) }}" class="flex items-center justify-between gap-3 rounded-xl px-2 py-1.5 hover:bg-slate-50">
                                    <span class="text-xs font-bold text-slate-700 truncate">{{ $org['name'] }}</span>
                                    <span class="text-[11px] font-semibold text-slate-400 shrink-0">{{ $org['events'] }} events · {{ $org['tasks'] }} tasks · {{ $org['volunteers'] }} volunteers</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    @if($activeTab === 'dashboard')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const growthCanvas = document.getElementById('volunteerGrowthChart');
                const growthGradient = growthCanvas.getContext('2d').createLinearGradient(0, 0, 0, 280);
                growthGradient.addColorStop(0, 'rgba(0, 151, 214, 0.28)');
                growthGradient.addColorStop(1, 'rgba(0, 151, 214, 0)');

                new Chart(growthCanvas, {
                    type: 'line',
                    data: {
                        labels: @json($volunteerGrowthLabels),
                        datasets: [{
                            label: 'Approved signups',
                            data: @json($volunteerGrowthValues),
                            borderColor: '#0097d6',
                            backgroundColor: growthGradient,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointBackgroundColor: '#0097d6',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { displayColors: false },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#94a3b8', font: { size: 11 } },
                                border: { display: false },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 },
                                grid: { color: 'rgba(148, 163, 184, 0.18)' },
                                border: { display: false },
                            },
                        },
                    },
                });

                const activityItems = @json($activityItems);
                const activityTotal = activityItems.reduce((sum, item) => sum + Number(item.value), 0);

                new Chart(document.getElementById('activitySnapshotChart'), {
                    type: 'doughnut',
                    data: {
                        labels: activityTotal === 0 ? ['No activity yet'] : activityItems.map(item => item.label),
                        datasets: [{
                            data: activityTotal === 0 ? [1] : activityItems.map(item => item.value),
                            backgroundColor: activityTotal === 0 ? ['#e2e8f0'] : activityItems.map(item => item.color),
                            borderWidth: 0,
                            hoverOffset: 4,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                enabled: activityTotal !== 0,
                                displayColors: false,
                            },
                        },
                    },
                });

                const axis = {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 },
                        grid: { color: 'rgba(148, 163, 184, 0.18)' },
                        border: { display: false },
                    },
                };

                const eventStatusCanvas = document.getElementById('eventStatusChart');
                if (eventStatusCanvas) {
                    const slices = @json(collect($dashboardEventStatus)->where('value', '>', 0)->values());
                    new Chart(eventStatusCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: slices.map(item => item.label),
                            datasets: [{
                                data: slices.map(item => item.value),
                                backgroundColor: slices.map(item => item.color),
                                borderWidth: 0,
                                hoverOffset: 4,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '72%',
                            plugins: { legend: { display: false } },
                        },
                    });
                }

                const taskStatusCanvas = document.getElementById('taskStatusChart');
                if (taskStatusCanvas) {
                    const bars = @json($dashboardTaskStatus);
                    new Chart(taskStatusCanvas, {
                        type: 'bar',
                        data: {
                            labels: bars.map(item => item.label),
                            datasets: [{
                                label: 'Tasks',
                                data: bars.map(item => item.value),
                                backgroundColor: bars.map(item => item.color),
                                borderRadius: 6,
                                maxBarThickness: 28,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: axis.y,
                                y: { ...axis.x, ticks: { color: '#475569', font: { size: 11, weight: '600' } } },
                            },
                        },
                    });
                }

                const participationCanvas = document.getElementById('participationChart');
                if (participationCanvas) {
                    new Chart(participationCanvas, {
                        type: 'bar',
                        data: {
                            labels: @json($dashboardMonthLabels),
                            datasets: [
                                { label: 'Events', data: @json($dashboardMonthEvents), backgroundColor: '#0097d6', borderRadius: 6, maxBarThickness: 28 },
                                { label: 'Volunteers', data: @json($dashboardMonthVolunteers), backgroundColor: '#7dd3fc', borderRadius: 6, maxBarThickness: 28 },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                            scales: axis,
                        },
                    });
                }

                const organizationCanvas = document.getElementById('organizationChart');
                if (organizationCanvas) {
                    const organizations = @json($dashboardOrganizations);
                    new Chart(organizationCanvas, {
                        type: 'bar',
                        data: {
                            labels: organizations.map(org => org.name),
                            datasets: [
                                { label: 'Events', data: organizations.map(org => org.events), backgroundColor: '#0097d6', borderRadius: 6, maxBarThickness: 16 },
                                { label: 'Tasks', data: organizations.map(org => org.tasks), backgroundColor: '#0284c7', borderRadius: 6, maxBarThickness: 16 },
                                { label: 'Volunteers', data: organizations.map(org => org.volunteers), backgroundColor: '#7dd3fc', borderRadius: 6, maxBarThickness: 16 },
                            ],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                            scales: {
                                x: axis.y,
                                y: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11 } }, border: { display: false } },
                            },
                        },
                    });
                }
            });
        </script>
    @endif

    <!-- TAB 2: ORG AUDITS -->
    <div class="{{ $activeTab === 'audits' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-building-circle-check text-jci-blue"></i> Organization Registration Audits
            </h4>
            <span class="bg-amber-100 text-amber-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">
                {{ $pendingOrgs->count() }} Pending Reviews
            </span>
        </div>

        @if($pendingOrgs->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($pendingOrgs as $org)
                    <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-200/80 hover-lift premium-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h5 class="font-bold text-sm text-slate-800">{{ $org->name }}</h5>
                                <p class="text-[10px] text-slate-400">Email: {{ $org->email }}</p>
                                <p class="text-[10px] text-jci-blue font-bold mt-1">{{ $org->organizationTypeLabel() ?? 'Type not set' }}</p>
                            </div>
                            <span class="bg-amber-500 text-white text-[9px] px-2 py-0.5 rounded font-bold">Pending Review</span>
                        </div>
                        <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                            {{ $org->bio ?? 'No description provided.' }}
                        </p>
                        <div class="flex space-x-2">
                            <!-- Approve Action -->
                            <form action="{{ route('admin.orgs.approve', $org->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white text-xs py-1.5 rounded-lg font-bold transition">
                                    Approve
                                </button>
                            </form>
                            <!-- Reject Action -->
                            <form action="{{ route('admin.orgs.reject', $org->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full text-slate-500 hover:text-red-600 bg-slate-200 hover:bg-slate-300 text-xs py-1.5 rounded-lg font-semibold transition">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                <i class="fa-solid fa-circle-check text-slate-300 text-4xl mb-2"></i>
                <p class="text-xs text-slate-400 font-medium">No pending organization registrations to review.</p>
            </div>
        @endif
    </div>

    <!-- TAB: ORGANIZATIONS -->
    <div class="{{ $activeTab === 'organizations' ? '' : 'hidden' }} space-y-5">
        @php
            $approvedOrganizationCount = $organizations->where('status', 'approved')->count();
            $pendingOrganizationCount = $organizations->where('status', 'pending')->count();
            $rejectedOrganizationCount = $organizations->where('status', 'rejected')->count();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Approved', 'value' => $approvedOrganizationCount, 'icon' => 'fa-circle-check', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
                ['label' => 'Pending', 'value' => $pendingOrganizationCount, 'icon' => 'fa-clock', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-500'],
                ['label' => 'Rejected', 'value' => $rejectedOrganizationCount, 'icon' => 'fa-circle-xmark', 'iconBg' => 'bg-rose-50', 'iconColor' => 'text-rose-500'],
                ['label' => 'Total Orgs', 'value' => $organizations->count(), 'icon' => 'fa-building', 'iconBg' => 'bg-blue-50', 'iconColor' => 'text-jci-blue'],
            ] as $card)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-5">
                    <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                    <p class="mt-4 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 p-5 border-b border-slate-100">
                <div>
                    <h4 class="font-extrabold text-slate-900">Organization Directory</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Pending applications are listed first. Approve or reject them from Org Registrations.</p>
                </div>
                <label class="relative w-full lg:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input id="org-list-search" type="search" placeholder="Search organizations..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-jci-blue/30 focus:border-jci-blue">
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-[11px] uppercase tracking-wider text-slate-400">
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-3 font-semibold">Organization</th>
                            <th class="px-4 py-3 font-semibold">Type</th>
                            <th class="px-4 py-3 font-semibold">Events</th>
                            <th class="px-4 py-3 font-semibold">Volunteers</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($organizations as $org)
                            @php
                                $typeLabel = $org->organizationTypeLabel();
                                $location = $organizationLocations[$org->id] ?? null;
                                $volunteerCount = $organizationVolunteerCounts[$org->id] ?? 0;
                                $initials = collect(explode(' ', $org->name))->map(fn($part) => substr($part, 0, 1))->take(2)->join('');
                                $statusClass = match ($org->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-600',
                                    'pending' => 'bg-amber-50 text-amber-600',
                                    'rejected' => 'bg-rose-50 text-rose-600',
                                    default => 'bg-slate-100 text-slate-500',
                                };
                                $searchText = strtolower(trim($org->name . ' ' . $org->email . ' ' . ($typeLabel ?? '') . ' ' . ($location ?? '')));
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition" data-org-row data-search="{{ $searchText }}">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3 min-w-56">
                                        @if($org->profile_photo_path)
                                            <img src="{{ asset('storage/' . $org->profile_photo_path) }}" alt="{{ $org->name }}" class="h-9 w-9 rounded-full object-cover shrink-0">
                                        @else
                                            <span class="h-9 w-9 rounded-full bg-jci-blue text-white flex items-center justify-center text-[11px] font-bold shrink-0">
                                                {{ strtoupper($initials) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 truncate">{{ $org->name }}</div>
                                            <div class="text-xs text-slate-400 truncate flex items-center gap-1">
                                                @if($location)
                                                    <i class="fa-solid fa-location-dot text-[10px]"></i> {{ $location }}
                                                @else
                                                    <i class="fa-solid fa-envelope text-[10px]"></i> {{ $org->email }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap">{{ $typeLabel ?? 'Not set' }}</td>
                                <td class="px-4 py-3.5 text-slate-700 font-semibold">{{ number_format($org->events_count) }}</td>
                                <td class="px-4 py-3.5 text-slate-700 font-semibold">{{ number_format($volunteerCount) }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="{{ $statusClass }} text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wide">
                                        {{ $org->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-end">
                                        <button type="button" data-org-toggle="{{ $org->id }}" title="View details" class="h-8 w-8 rounded-full border border-slate-200 text-slate-400 hover:text-jci-blue hover:border-jci-blue flex items-center justify-center transition">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr id="org-detail-{{ $org->id }}" class="hidden bg-slate-50/80" data-org-detail>
                                <td colspan="6" class="px-5 py-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs text-slate-600">
                                        <div>
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Email</span>
                                            {{ $org->email }}
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Phone</span>
                                            {{ $org->phone ?: 'No phone listed' }}
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Socio-civic type</span>
                                            {{ $typeLabel ?? 'Not set' }}
                                        </div>
                                        <div class="md:col-span-3">
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Mission</span>
                                            {{ $org->bio ?: 'No mission statement provided.' }}
                                        </div>
                                        <form action="{{ route('admin.orgs.documents', $org->id) }}" method="POST" class="md:col-span-3 grid gap-2 md:grid-cols-2">
                                            @csrf
                                            <div class="md:col-span-2">
                                                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Request documents</span>
                                                <p class="text-[11px] text-slate-400">Only an administrator can change verification. This asks the organization for more information.</p>
                                            </div>
                                            <textarea name="documents_requested" required maxlength="1000" rows="2" placeholder="Document to submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">{{ $org->documents_requested }}</textarea>
                                            <textarea name="feedback" maxlength="1000" rows="2" placeholder="Feedback for the organization" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">{{ $org->verification_feedback }}</textarea>
                                            <div>
                                                <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Request documents</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">No organizations found.</td>
                            </tr>
                        @endforelse
                        <tr id="org-list-empty" class="hidden">
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">No organizations match this search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($activeTab === 'organizations')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const search = document.getElementById('org-list-search');
                const rows = Array.from(document.querySelectorAll('[data-org-row]'));
                const empty = document.getElementById('org-list-empty');

                function applySearch() {
                    const query = search.value.trim().toLowerCase();
                    let visible = 0;
                    rows.forEach(function (row) {
                        const show = query === '' || (row.dataset.search || '').includes(query);
                        row.classList.toggle('hidden', !show);
                        const detailId = row.querySelector('[data-org-toggle]')?.dataset.orgToggle;
                        const detail = detailId ? document.getElementById('org-detail-' + detailId) : null;
                        if (detail && !show) detail.classList.add('hidden');
                        if (show) visible += 1;
                    });
                    if (empty) empty.classList.toggle('hidden', visible !== 0 || rows.length === 0);
                }

                search.addEventListener('input', applySearch);

                document.querySelectorAll('[data-org-toggle]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const detail = document.getElementById('org-detail-' + button.dataset.orgToggle);
                        if (detail) detail.classList.toggle('hidden');
                    });
                });
            });
        </script>
    @endif

    <!-- TAB: EVENTS -->
    <div class="{{ $activeTab === 'events' ? '' : 'hidden' }} space-y-5">
        @php
            $eventRank = ['review' => 0, 'upcoming' => 1, 'ongoing' => 2, 'completed' => 3, 'cancelled' => 4];
            $eventFilters = [
                'all' => 'All',
                'review' => 'Needs review',
                'upcoming' => 'Upcoming',
                'ongoing' => 'Ongoing',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ];
            $eventOverview = [
                ['key' => 'review', 'label' => 'Pending approval', 'icon' => 'fa-hourglass-half', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-500'],
                ['key' => 'upcoming', 'label' => 'Upcoming', 'icon' => 'fa-calendar-day', 'iconBg' => 'bg-sky-50', 'iconColor' => 'text-sky-600'],
                ['key' => 'ongoing', 'label' => 'Ongoing', 'icon' => 'fa-person-running', 'iconBg' => 'bg-indigo-50', 'iconColor' => 'text-indigo-500'],
                ['key' => 'completed', 'label' => 'Completed', 'icon' => 'fa-circle-check', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
            ];
            $selectedOrganization = $organizations->firstWhere('id', $selectedEventOrg);
            $visibleEvents = $postedEvents
                ->sortBy(fn ($event) => sprintf('%d-%s', $eventRank[$event->oversightStatus()] ?? 9, optional($event->start_time)->format('Y-m-d H:i:s')))
                ->when($eventStatusFilter !== 'all', fn ($events) => $events->filter(fn ($event) => $event->oversightStatus() === $eventStatusFilter))
                ->when($selectedOrganization, fn ($events) => $events->filter(fn ($event) => (int) $event->organization_id === (int) $selectedOrganization->id))
                ->values();
        @endphp

        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
            <div>
                <h4 class="font-extrabold text-slate-900">{{ number_format($postedEvents->count()) }} {{ \Illuminate\Support\Str::plural('event', $postedEvents->count()) }}</h4>
                <p class="text-xs text-slate-400 mt-0.5">Organizations create events. Approve one before volunteers can see it.</p>
            </div>
            <p class="text-[11px] text-slate-400 max-w-md">Contact details, application messages, and completion files stay with the organization. Assigned volunteer names are shown.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach($eventOverview as $card)
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'events', 'org' => $selectedEventOrg ?: null, 'event_status' => $card['key']])) }}" class="bg-white rounded-2xl border shadow-sm px-5 py-5 transition {{ $eventStatusFilter === $card['key'] ? 'border-jci-blue' : 'border-slate-100 hover:border-sky-200' }}">
                    <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                    <p class="mt-4 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($postedEvents->filter(fn ($event) => $event->oversightStatus() === $card['key'])->count()) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </a>
            @endforeach
        </div>

        @if($eventsAwaitingReview > 0)
            <a href="{{ route('admin.dashboard', array_filter(['tab' => 'events', 'org' => $selectedEventOrg ?: null, 'event_status' => 'review'])) }}" class="block rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                <p class="text-sm font-bold text-amber-800">{{ $eventsAwaitingReview }} {{ \Illuminate\Support\Str::plural('event', $eventsAwaitingReview) }} waiting for approval</p>
                <p class="text-xs text-amber-700 mt-1">These stay hidden from volunteers until you approve them.</p>
            </a>
        @endif

        <div class="flex flex-wrap gap-2">
            @foreach($eventFilters as $key => $label)
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'events', 'org' => $selectedEventOrg ?: null, 'event_status' => $key === 'all' ? null : $key])) }}"
                   class="px-3 py-1.5 rounded-full text-xs font-bold {{ $eventStatusFilter === $key ? 'bg-jci-blue text-white' : 'bg-white text-slate-500 border border-slate-200 hover:border-sky-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
            <div class="mb-4">
                <h4 class="font-extrabold text-slate-900">Organizations</h4>
                <p class="text-xs text-slate-400 mt-0.5">Choose an organization to see the events it posted.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @forelse($organizations as $org)
                    @php
                        $orgEvents = $postedEvents->where('organization_id', $org->id);
                        $orgAccepted = $orgEvents->flatMap->tasks->flatMap->assignments->pluck('user_id')->unique()->count();
                        $isSelected = (int) $selectedEventOrg === (int) $org->id;
                    @endphp
                    <a href="{{ route('admin.dashboard', array_filter(['tab' => 'events', 'org' => $org->id, 'event_status' => $eventStatusFilter === 'all' ? null : $eventStatusFilter])) }}"
                       class="min-h-[6.5rem] rounded-2xl border px-4 py-4 transition flex flex-col {{ $isSelected ? 'border-jci-blue bg-sky-50 shadow-sm' : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800 truncate">{{ $org->name }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $org->organizationTypeLabel() ?? 'Type not set' }}</p>
                                <p class="text-[11px] text-jci-blue mt-1 truncate">Event reward: {{ $org->eventRewardLabel() ?? 'Service certificate' }}</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[10px] {{ $isSelected ? 'text-jci-blue' : 'text-slate-300' }} mt-1"></i>
                        </div>
                        <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                            <span><span class="font-bold text-slate-800">{{ $orgEvents->count() }}</span> events</span>
                            <span><span class="font-bold text-slate-800">{{ $orgAccepted }}</span> accepted</span>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-400">No organizations are registered yet.</p>
                @endforelse
            </div>
        </div>

        @if(!$selectedOrganization)
        @elseif($visibleEvents->isEmpty())
            <div class="bg-white rounded-3xl border border-dashed border-slate-200 p-10 text-center">
                <i class="fa-solid fa-calendar-xmark text-slate-300 text-3xl mb-2"></i>
                <p class="text-sm text-slate-400">No events in this view.</p>
            </div>
        @else
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h4 class="font-extrabold text-slate-900">{{ $selectedOrganization->name }}</h4>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $selectedOrganization->organizationTypeLabel() ?? 'Type not set' }} · Event reward: {{ $selectedOrganization->eventRewardLabel() ?? 'Service certificate' }} · {{ $visibleEvents->count() }} {{ \Illuminate\Support\Str::plural('event', $visibleEvents->count()) }}</p>
                </div>
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'events', 'event_status' => $eventStatusFilter === 'all' ? null : $eventStatusFilter])) }}" class="text-xs font-bold text-jci-blue hover:underline">Show all organizations</a>
            </div>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                @foreach($visibleEvents as $event)
                    @php
                        $accepted = $event->tasks->flatMap->assignments;
                        $volunteerCount = $accepted->pluck('user_id')->unique()->count();
                        $openSlots = $event->capacity === null ? null : max((int) $event->capacity - $volunteerCount, 0);
                        $oversight = $event->oversightStatus();
                        $statusClass = match ($oversight) {
                            'upcoming' => 'bg-sky-50 text-sky-700',
                            'ongoing' => 'bg-amber-50 text-amber-700',
                            'completed' => 'bg-emerald-50 text-emerald-700',
                            'cancelled' => 'bg-rose-50 text-rose-600',
                            default => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <article class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 flex flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h5 class="font-bold text-slate-900 leading-snug">{{ $event->title }}</h5>
                                <p class="text-xs text-slate-500 mt-1">{{ $event->organization->name ?? 'Organization removed' }}</p>
                            </div>
                            <span class="{{ $statusClass }} text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wide shrink-0">{{ $event->oversightLabel() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-3">{{ $event->start_time?->format('M d, Y g:i A') }} – {{ $event->end_time?->format('M d, Y g:i A') }}</p>
                        <p class="text-xs text-slate-500 mt-1"><i class="fa-solid fa-location-dot text-slate-300"></i> {{ $event->location }}</p>
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-lg font-black text-slate-800">{{ $volunteerCount }}</p>
                                <p class="text-[10px] text-slate-400 font-semibold">Volunteers</p>
                            </div>
                            <div class="rounded-xl bg-sky-50 px-3 py-2">
                                <p class="text-lg font-black text-sky-700">{{ $openSlots === null ? 'Open' : $openSlots }}</p>
                                <p class="text-[10px] text-sky-600 font-semibold">{{ $openSlots === null ? 'No slot limit' : 'Slots left' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($oversight === 'review')
                                <form action="{{ route('admin.events.approve', $event) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-white bg-jci-blue hover:bg-jci-dark px-3 py-1.5 rounded-lg">Approve</button>
                                </form>
                            @endif
                            <button type="button" data-panel-toggle="event-detail-{{ $event->id }}" class="text-xs font-bold text-jci-blue hover:underline">Details</button>
                        </div>
                        <div id="event-detail-{{ $event->id }}" class="hidden mt-3 border-t border-slate-100 pt-3 space-y-2">
                            <p class="text-xs text-slate-500 leading-relaxed">{{ $event->description }}</p>
                            <p class="text-[11px] text-slate-500">Organization: {{ $event->organization->name ?? 'Organization removed' }}</p>
                            <p class="text-[11px] text-slate-500">Capacity: {{ $event->capacity === null ? 'No limit' : $event->capacity }} · Volunteers on tasks: {{ $volunteerCount }}</p>
                            <a href="{{ route('admin.dashboard', ['tab' => 'tasks', 'task_org' => $event->organization_id, 'task_event' => $event->id]) }}" class="inline-block text-xs font-bold text-jci-blue hover:underline">View this event's tasks</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    @if($activeTab === 'events')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-panel-toggle]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const panel = document.getElementById(button.dataset.panelToggle);
                        if (panel) panel.classList.toggle('hidden');
                    });
                });
            });
        </script>
    @endif

    <!-- TAB: TASKS -->
    <div class="{{ $activeTab === 'tasks' ? '' : 'hidden' }} space-y-5">
        @php
            $now = \Carbon\Carbon::now();
            $taskFilters = [
                'all' => 'All',
                'pending' => 'Pending',
                'assigned' => 'Assigned',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'overdue' => 'Overdue',
            ];
            $taskOverview = [
                ['key' => 'pending', 'label' => 'Pending', 'icon' => 'fa-clock', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-500'],
                ['key' => 'assigned', 'label' => 'Assigned', 'icon' => 'fa-user-check', 'iconBg' => 'bg-sky-50', 'iconColor' => 'text-sky-600'],
                ['key' => 'in_progress', 'label' => 'In Progress', 'icon' => 'fa-spinner', 'iconBg' => 'bg-indigo-50', 'iconColor' => 'text-indigo-500'],
                ['key' => 'completed', 'label' => 'Completed', 'icon' => 'fa-circle-check', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
            ];
            $taskEvent = $taskEventFilter ? $postedEvents->firstWhere('id', $taskEventFilter) : null;
            $activeTaskOrg = $selectedTaskOrg ?: (int) ($taskEvent->organization_id ?? 0);
            $selectedTaskOrganization = $organizations->firstWhere('id', $activeTaskOrg);
            $scopedTasks = $postedTasks
                ->when($activeTaskOrg, fn ($tasks) => $tasks->filter(fn ($task) => (int) optional($task->event)->organization_id === $activeTaskOrg))
                ->when($taskEventFilter, fn ($tasks) => $tasks->filter(fn ($task) => (int) $task->event_id === $taskEventFilter));
            $visibleTasks = $scopedTasks
                ->filter(function ($task) use ($taskStatusFilter) {
                    if ($taskStatusFilter === 'all') return true;
                    if ($taskStatusFilter === 'overdue') return $task->isOverdue();
                    return $task->oversightStatus() === $taskStatusFilter;
                })
                ->sortBy(fn ($task) => sprintf('%d-%s', $task->isOverdue() ? 0 : 1, optional($task->due_date)->format('Y-m-d H:i:s') ?? '9999'))
                ->values();
        @endphp

        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
            <div>
                <h4 class="font-extrabold text-slate-900">{{ number_format($postedTasks->count()) }} {{ \Illuminate\Support\Str::plural('task', $postedTasks->count()) }}</h4>
                <p class="text-xs text-slate-400 mt-0.5">Organizations create and assign tasks. This page checks deadlines, volunteers, and completion.</p>
            </div>
            @if($taskEvent)
                <a href="{{ route('admin.dashboard', ['tab' => 'tasks', 'task_org' => $activeTaskOrg]) }}" class="text-xs font-bold text-jci-blue hover:underline">Show all tasks for this organization</a>
            @endif
        </div>

        <div class="grid grid-cols-2 xl:grid-cols-5 gap-4">
            @foreach($taskOverview as $card)
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_org' => $activeTaskOrg ?: null, 'task_status' => $card['key'], 'task_event' => $taskEventFilter ?: null])) }}" class="bg-white rounded-2xl border shadow-sm px-5 py-5 transition {{ $taskStatusFilter === $card['key'] ? 'border-jci-blue' : 'border-slate-100 hover:border-sky-200' }}">
                    <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                    <p class="mt-4 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($scopedTasks->filter(fn ($task) => $task->oversightStatus() === $card['key'])->count()) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </a>
            @endforeach
            <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_org' => $activeTaskOrg ?: null, 'task_status' => 'overdue', 'task_event' => $taskEventFilter ?: null])) }}" class="bg-white rounded-2xl border shadow-sm px-5 py-5 transition {{ $taskStatusFilter === 'overdue' ? 'border-rose-400' : 'border-slate-100 hover:border-rose-200' }}">
                <span class="h-10 w-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </span>
                <p class="mt-4 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($scopedTasks->filter(fn ($task) => $task->isOverdue())->count()) }}</p>
                <p class="mt-1 text-sm text-slate-400 font-medium">Overdue</p>
            </a>
        </div>

        @if($taskEvent)
            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4">
                <p class="text-sm font-bold text-slate-800">Tasks for {{ $taskEvent->title }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $taskEvent->organization->name ?? 'Organization removed' }}</p>
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            @foreach($taskFilters as $key => $label)
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_org' => $activeTaskOrg ?: null, 'task_status' => $key === 'all' ? null : $key, 'task_event' => $taskEventFilter ?: null])) }}"
                   class="px-3 py-1.5 rounded-full text-xs font-bold {{ $taskStatusFilter === $key ? 'bg-jci-blue text-white' : 'bg-white text-slate-500 border border-slate-200 hover:border-sky-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
            <div class="mb-4">
                <h4 class="font-extrabold text-slate-900">Organizations</h4>
                <p class="text-xs text-slate-400 mt-0.5">Choose an organization to see the tasks inside its events.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @forelse($organizations as $org)
                    @php
                        $orgTasks = $postedTasks->filter(fn ($task) => (int) optional($task->event)->organization_id === (int) $org->id);
                        $isSelected = (int) $activeTaskOrg === (int) $org->id;
                    @endphp
                    <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_org' => $org->id, 'task_status' => $taskStatusFilter === 'all' ? null : $taskStatusFilter])) }}"
                       class="min-h-[6.5rem] rounded-2xl border px-4 py-4 transition flex flex-col {{ $isSelected ? 'border-jci-blue bg-sky-50 shadow-sm' : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800 truncate">{{ $org->name }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $org->organizationTypeLabel() ?? 'Type not set' }}</p>
                                <p class="text-[11px] text-jci-blue mt-1 truncate">Task reward: {{ $org->taskRewardLabel() ?? 'Logged service hours' }}</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[10px] {{ $isSelected ? 'text-jci-blue' : 'text-slate-300' }} mt-1"></i>
                        </div>
                        <div class="mt-3 text-xs text-slate-500">
                            <span class="font-bold text-slate-800">{{ $orgTasks->count() }}</span> tasks
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-400">No organizations are registered yet.</p>
                @endforelse
            </div>
        </div>

        @if(!$selectedTaskOrganization)
        @elseif($visibleTasks->isEmpty())
            <div class="bg-white rounded-3xl border border-dashed border-slate-200 p-10 text-center">
                <i class="fa-solid fa-list-check text-slate-300 text-3xl mb-2"></i>
                <p class="text-sm text-slate-400">No tasks in this view.</p>
            </div>
        @else
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h4 class="font-extrabold text-slate-900">{{ $selectedTaskOrganization->name }}</h4>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $selectedTaskOrganization->organizationTypeLabel() ?? 'Type not set' }} · Task reward: {{ $selectedTaskOrganization->taskRewardLabel() ?? 'Logged service hours' }} · {{ $visibleTasks->count() }} {{ \Illuminate\Support\Str::plural('task', $visibleTasks->count()) }}</p>
                </div>
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_status' => $taskStatusFilter === 'all' ? null : $taskStatusFilter])) }}" class="text-xs font-bold text-jci-blue hover:underline">Show all organizations</a>
            </div>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                @foreach($visibleTasks as $task)
                    @php
                        $oversight = $task->oversightStatus();
                        $statusClass = match ($oversight) {
                            'assigned' => 'bg-sky-50 text-sky-700',
                            'in_progress' => 'bg-indigo-50 text-indigo-600',
                            'completed' => 'bg-emerald-50 text-emerald-700',
                            'cancelled' => 'bg-rose-50 text-rose-600',
                            default => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <article class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 flex flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h5 class="font-bold text-slate-900 leading-snug">{{ $task->title }}</h5>
                                <p class="text-xs text-slate-500 mt-1">{{ $task->event->title ?? 'Event removed' }} · {{ $task->event->organization->name ?? 'Organization removed' }}</p>
                            </div>
                            <span class="{{ $statusClass }} text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wide shrink-0">{{ $task->oversightLabel() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-3">
                            {{ $task->due_date ? 'Due ' . $task->due_date->format('M d, Y g:i A') : 'No deadline' }}
                            @if($task->isOverdue())
                                <span class="text-rose-500 font-bold">· Overdue</span>
                            @endif
                        </p>
                        <p class="text-xs text-slate-600 mt-1">Volunteer: {{ $task->assignedVolunteerNames() }}</p>
                        <p class="text-[11px] text-slate-400 mt-1">{{ $oversight === 'completed' ? 'Completed' : 'Not completed yet' }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" data-panel-toggle="task-detail-{{ $task->id }}" class="text-xs font-bold text-jci-blue hover:underline">Details</button>
                            @if($task->isOverdue())
                                @php
                                    $notifyOrganization = $task->event->organization->name ?? 'the organization';
                                    $notifyVolunteer = $task->assignedVolunteerNames();
                                    $notifyCopy = $notifyVolunteer === 'Unassigned'
                                        ? 'This sends a notice to ' . $notifyOrganization . '. It tells them that "' . $task->title . '" is overdue and no volunteer is assigned, and asks them to assign someone who can finish it. The volunteer is not chosen from here.'
                                        : 'This sends a notice to ' . $notifyOrganization . '. It tells them that "' . $task->title . '" is overdue and is still with ' . $notifyVolunteer . ', and asks them to reassign it. The volunteer is not changed from here.';
                                @endphp
                                <button type="button"
                                        data-notify-open
                                        data-notify-action="{{ route('admin.tasks.notify', $task) }}"
                                        data-notify-copy="{{ $notifyCopy }}"
                                        class="text-xs font-bold text-amber-700 hover:underline">Notify</button>
                            @endif
                        </div>
                        <div id="task-detail-{{ $task->id }}" class="hidden mt-3 border-t border-slate-100 pt-3 space-y-2">
                            <p class="text-xs text-slate-500 leading-relaxed">{{ $task->description ?: 'No task description was added.' }}</p>
                            <p class="text-[11px] text-slate-500">Event: {{ $task->event->title ?? 'Event removed' }}</p>
                            <p class="text-[11px] text-slate-500">Priority: {{ ucfirst($task->priority) }} · {{ $task->applications->where('status', 'pending')->count() }} waiting to be assigned</p>
                            <p class="text-[11px] text-jci-blue">Finishing this task earns {{ $task->event->organization->taskRewardLabel() ?? 'Logged service hours' }}.</p>
                            @php
                                $taskRole = $task->skills->pluck('name')->filter()->join(', ');
                                if ($taskRole === '') {
                                    $taskRole = $task->title;
                                }
                            @endphp
                            <div class="space-y-2 pt-1">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Assigned volunteers</p>
                                @forelse($task->assignments as $assignment)
                                    <div class="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-xs font-bold text-slate-800">{{ $assignment->user->name ?? 'Former volunteer' }}</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Role: {{ $taskRole }}</p>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400">No volunteer is assigned to this task yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div id="notify-overdue-dialog" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-[999] hidden items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
                <h3 class="font-black text-slate-900">Notify the organization</h3>
                <p id="notify-overdue-copy" class="text-sm text-slate-600 leading-relaxed mt-3"></p>
                <form id="notify-overdue-form" method="POST" class="mt-5 flex justify-end gap-2">
                    @csrf
                    <button type="button" id="notify-overdue-cancel" class="text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl">Cancel</button>
                    <button type="submit" class="text-xs font-bold text-white bg-jci-blue hover:bg-jci-dark px-4 py-2 rounded-xl">Yes</button>
                </form>
            </div>
        </div>
    </div>

    @if($activeTab === 'tasks')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const dialog = document.getElementById('notify-overdue-dialog');
                const copy = document.getElementById('notify-overdue-copy');
                const form = document.getElementById('notify-overdue-form');
                const cancel = document.getElementById('notify-overdue-cancel');

                document.querySelectorAll('[data-panel-toggle]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const panel = document.getElementById(button.dataset.panelToggle);
                        if (panel) panel.classList.toggle('hidden');
                    });
                });

                document.querySelectorAll('[data-notify-open]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        if (!dialog || !copy || !form) return;
                        copy.textContent = button.dataset.notifyCopy || '';
                        form.action = button.dataset.notifyAction || '';
                        dialog.classList.remove('hidden');
                        dialog.classList.add('flex');
                    });
                });

                function closeNotifyDialog() {
                    if (!dialog) return;
                    dialog.classList.add('hidden');
                    dialog.classList.remove('flex');
                }

                if (cancel) cancel.addEventListener('click', closeNotifyDialog);
                if (dialog) {
                    dialog.addEventListener('click', function (event) {
                        if (event.target === dialog) closeNotifyDialog();
                    });
                }
            });
        </script>
    @endif

    <!-- TAB: VOLUNTEERS -->
    <div class="{{ $activeTab === 'volunteers' ? '' : 'hidden' }} space-y-5">
        @php
            $selectedVolunteerOrg = (int) request('volunteer_org', 0);
            $selectedVolunteerOrganization = $organizations->firstWhere('id', $selectedVolunteerOrg);
            $roster = $systemUsers->where('role', 'volunteer')->sortBy('name')->values();
            $joinedStatuses = ['approved', 'submitted', 'completed'];
            $rosterStats = $roster->map(function ($volunteer) use ($joinedStatuses) {
                $joined = $volunteer->assignments->whereIn('status', $joinedStatuses);
                $completed = $volunteer->assignments->where('status', 'completed');
                $member = $volunteer->status !== 'approved'
                    ? $volunteer->status
                    : ($volunteer->availability === 'inactive' ? 'inactive' : 'active');
                $orgIds = collect([$volunteer->organization_id])
                    ->merge($volunteer->assignments->map(fn ($assignment) => $assignment->event?->organization_id))
                    ->merge($volunteer->taskApplications->map(fn ($application) => $application->event?->organization_id))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                return [
                    'volunteer' => $volunteer,
                    'member' => $member,
                    'orgIds' => $orgIds,
                    'events' => $joined->pluck('event_id')->unique()->count(),
                    'tasks' => $completed->count(),
                    'hours' => $completed->sum('hours_logged'),
                    'rewards' => $volunteer->volunteerRewards->count(),
                    'certificates' => $volunteer->certificates->count(),
                ];
            });
            $volunteerOverview = [
                ['label' => 'Active', 'value' => $rosterStats->where('member', 'active')->count(), 'icon' => 'fa-user-check', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
                ['label' => 'Events Joined', 'value' => $rosterStats->sum('events'), 'icon' => 'fa-calendar-check', 'iconBg' => 'bg-sky-50', 'iconColor' => 'text-sky-600'],
                ['label' => 'Tasks Completed', 'value' => $rosterStats->sum('tasks'), 'icon' => 'fa-list-check', 'iconBg' => 'bg-indigo-50', 'iconColor' => 'text-indigo-500'],
                ['label' => 'Rewards Received', 'value' => $rosterStats->sum('rewards'), 'icon' => 'fa-award', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-500'],
            ];
            $orgRoster = $selectedVolunteerOrganization
                ? $rosterStats
                    ->filter(fn ($row) => $row['orgIds']->contains((int) $selectedVolunteerOrganization->id))
                    ->map(function ($row) use ($selectedVolunteerOrganization, $joinedStatuses) {
                        $volunteer = $row['volunteer'];
                        $orgId = (int) $selectedVolunteerOrganization->id;
                        $orgAssignments = $volunteer->assignments->filter(fn ($assignment) => (int) ($assignment->event?->organization_id ?? 0) === $orgId);
                        $row['joinedEvents'] = $orgAssignments->whereIn('status', $joinedStatuses)->unique('event_id')->values();
                        $row['completedTasks'] = $orgAssignments->where('status', 'completed')->values();
                        $row['orgRewards'] = $volunteer->volunteerRewards->filter(fn ($reward) => (int) $reward->organization_id === $orgId)->values();
                        $row['orgCertificates'] = $volunteer->certificates->filter(fn ($certificate) => (int) ($certificate->event?->organization_id ?? 0) === $orgId)->values();
                        $row['orgHours'] = $row['completedTasks']->sum('hours_logged');
                        $row['initials'] = collect(explode(' ', trim($volunteer->name)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->join('');
                        $row['memberLabel'] = match ($row['member']) {
                            'inactive' => 'Inactive',
                            'pending' => 'Pending',
                            'rejected' => 'Rejected',
                            default => 'Active',
                        };
                        $row['memberClass'] = match ($row['member']) {
                            'active' => 'text-emerald-600',
                            'inactive' => 'text-slate-400',
                            'rejected' => 'text-rose-500',
                            default => 'text-amber-600',
                        };
                        $row['searchText'] = strtolower(trim($volunteer->name . ' ' . $volunteer->email . ' ' . $volunteer->skills->pluck('name')->join(' ')));

                        return $row;
                    })
                    ->values()
                : collect();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach($volunteerOverview as $card)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-5">
                    <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                    <p class="mt-4 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                <div>
                    <h4 class="font-extrabold text-slate-900">Organizations</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Choose an organization to see the volunteers connected to it.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                    <div class="flex flex-wrap gap-2">
                        @foreach(['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $key => $label)
                            <button type="button" data-volunteer-filter="{{ $key }}" class="px-3 py-1.5 rounded-full text-xs font-bold {{ $key === 'all' ? 'bg-jci-blue text-white' : 'bg-slate-50 text-slate-500 border border-slate-200' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input id="volunteer-search" type="search" placeholder="Search volunteers..." class="w-full sm:w-56 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 items-stretch">
                @forelse($organizations as $org)
                    @php
                        $linked = $rosterStats->filter(fn ($row) => $row['orgIds']->contains((int) $org->id));
                        $isSelected = (int) $selectedVolunteerOrg === (int) $org->id;
                    @endphp
                    <a href="{{ route('admin.dashboard', ['tab' => 'volunteers', 'volunteer_org' => $org->id]) }}"
                       class="min-h-[6.5rem] rounded-2xl border px-4 py-4 transition flex flex-col {{ $isSelected ? 'border-jci-blue bg-sky-50 shadow-sm' : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800 truncate">{{ $org->name }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $org->organizationTypeLabel() ?? 'Type not set' }}</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[10px] {{ $isSelected ? 'text-jci-blue' : 'text-slate-300' }} mt-1"></i>
                        </div>
                        <div class="mt-auto pt-3 flex items-center gap-3 text-xs text-slate-500">
                            <span><span class="font-bold text-slate-800">{{ $linked->count() }}</span> volunteers</span>
                            <span><span class="font-bold text-slate-800">{{ $linked->where('member', 'active')->count() }}</span> active</span>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-400">No organizations are registered yet.</p>
                @endforelse
            </div>
        </div>

        @if($selectedVolunteerOrganization)
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h4 class="font-extrabold text-slate-900">{{ $selectedVolunteerOrganization->name }}</h4>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $orgRoster->count() }} {{ \Illuminate\Support\Str::plural('volunteer', $orgRoster->count()) }} linked through membership, events, or applications.</p>
                </div>
                <a href="{{ route('admin.dashboard', ['tab' => 'volunteers']) }}" class="text-xs font-bold text-jci-blue hover:underline">Show all organizations</a>
            </div>

            @if($orgRoster->isEmpty())
                <div class="bg-white rounded-3xl border border-dashed border-slate-200 p-10 text-center">
                    <i class="fa-solid fa-user-group text-slate-300 text-3xl mb-2"></i>
                    <p class="text-sm text-slate-400">No volunteers are connected to this organization yet.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-stretch" id="volunteer-roster">
                    @foreach($orgRoster as $row)
                        @php $volunteer = $row['volunteer']; @endphp
                        <article data-volunteer-card data-volunteer-id="{{ $volunteer->id }}" data-member="{{ $row['member'] }}" data-search="{{ $row['searchText'] }}" class="h-[19rem] w-full min-w-0 rounded-3xl border border-slate-100 bg-white shadow-sm p-4 flex flex-col box-border">
                            <div class="flex items-center gap-3 h-11 shrink-0">
                                @if($volunteer->profile_photo_path)
                                    <img src="{{ asset('storage/' . $volunteer->profile_photo_path) }}" alt="{{ $volunteer->name }}" class="h-11 w-11 rounded-full object-cover shrink-0">
                                @else
                                    <span class="h-11 w-11 rounded-full bg-jci-blue text-white text-xs font-black flex items-center justify-center shrink-0">{{ $row['initials'] }}</span>
                                @endif
                                <div class="min-w-0">
                                    <h5 class="font-bold text-slate-900 truncate">{{ $volunteer->name }}</h5>
                                    <p class="text-[11px] font-semibold {{ $row['memberClass'] }} mt-0.5 truncate"><i class="fa-solid fa-circle text-[6px] mr-1"></i>{{ $row['memberLabel'] }}</p>
                                </div>
                            </div>
                            <div class="mt-3 h-12 overflow-hidden flex flex-wrap content-start gap-1.5 shrink-0">
                                @forelse($volunteer->skills->take(3) as $skill)
                                    <span class="bg-slate-50 border border-slate-200 text-slate-500 text-[10px] font-semibold px-2 py-0.5 rounded-full max-w-full truncate">{{ $skill->name }}</span>
                                @empty
                                    <span class="text-[10px] text-slate-400">No skills listed</span>
                                @endforelse
                                @if($volunteer->skills->count() > 3)
                                    <span class="text-[10px] text-slate-400 font-semibold px-1">+{{ $volunteer->skills->count() - 3 }}</span>
                                @endif
                            </div>
                            <div class="grid grid-cols-3 gap-2 mt-auto pt-4 text-center">
                                <div class="h-16 rounded-xl bg-slate-50 px-2 flex flex-col items-center justify-center">
                                    <p class="text-lg font-black text-slate-900 leading-none">{{ $row['joinedEvents']->count() }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-1">Events</p>
                                </div>
                                <div class="h-16 rounded-xl bg-slate-50 px-2 flex flex-col items-center justify-center">
                                    <p class="text-lg font-black text-slate-900 leading-none">{{ $row['completedTasks']->count() }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-1">Tasks</p>
                                </div>
                                <div class="h-16 rounded-xl bg-slate-50 px-2 flex flex-col items-center justify-center">
                                    <p class="text-lg font-black text-slate-900 leading-none">{{ $row['orgRewards']->count() }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-1">Rewards</p>
                                </div>
                            </div>
                            <p class="h-5 shrink-0 text-[11px] text-slate-400 text-center mt-2 truncate">{{ rtrim(rtrim(number_format((float) $row['orgHours'], 1), '0'), '.') }}h logged · {{ $row['orgCertificates']->count() }} {{ \Illuminate\Support\Str::plural('certificate', $row['orgCertificates']->count()) }}</p>
                            <button type="button" data-panel-toggle="volunteer-detail-{{ $volunteer->id }}" class="mt-3 h-10 w-full shrink-0 box-border inline-flex items-center justify-center bg-jci-blue hover:bg-jci-dark text-white text-xs font-bold rounded-xl transition">View</button>
                        </article>
                    @endforeach
                </div>
                <div id="volunteer-details" class="space-y-4">
                    @foreach($orgRoster as $row)
                        @php $volunteer = $row['volunteer']; @endphp
                        <div id="volunteer-detail-{{ $volunteer->id }}" data-volunteer-detail="{{ $volunteer->id }}" class="hidden rounded-3xl border border-sky-100 bg-white shadow-sm p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h5 class="font-bold text-slate-900">{{ $volunteer->name }}</h5>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $selectedVolunteerOrganization->name }} · {{ $row['memberLabel'] }}</p>
                                </div>
                                <button type="button" data-panel-toggle="volunteer-detail-{{ $volunteer->id }}" class="text-xs font-bold text-slate-400 hover:text-slate-700 shrink-0">Close</button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mt-4 items-stretch">
                                <div class="min-h-56 rounded-2xl bg-slate-50 p-3 min-w-0 flex flex-col">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Profile</p>
                                    <p class="text-xs text-slate-700 mt-2 break-all">{{ $volunteer->email }}</p>
                                    <p class="text-xs text-slate-600 mt-1">{{ $volunteer->phone ?: 'No phone on record' }}</p>
                                    <p class="text-xs text-slate-600 mt-1">Joined {{ $volunteer->created_at?->format('M d, Y') }}</p>
                                    <p class="text-xs text-slate-600 mt-1">{{ rtrim(rtrim(number_format((float) $row['orgHours'], 1), '0'), '.') }} hours with this organization</p>
                                    @if($volunteer->skills->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach($volunteer->skills as $skill)
                                                <span class="bg-white border border-slate-200 text-slate-500 text-[10px] font-semibold px-2 py-0.5 rounded-full">{{ $skill->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($volunteer->bio)
                                        <p class="text-xs text-slate-600 leading-relaxed mt-2">{{ $volunteer->bio }}</p>
                                    @endif
                                </div>
                                <div class="min-h-56 rounded-2xl bg-slate-50 p-3 min-w-0 flex flex-col">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Events joined</p>
                                    <div class="mt-2 space-y-2 flex-1">
                                        @forelse($row['joinedEvents'] as $assignment)
                                            <div class="rounded-xl bg-white border border-slate-100 px-3 py-2">
                                                <p class="text-xs font-bold text-slate-800">{{ $assignment->event->title ?? 'Event removed' }}</p>
                                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $assignment->event?->start_time?->format('M d, Y') ?? 'Date unavailable' }}</p>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-400">No accepted event with this organization yet.</p>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="min-h-56 rounded-2xl bg-slate-50 p-3 min-w-0 flex flex-col">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Tasks completed</p>
                                    <div class="mt-2 space-y-2 flex-1">
                                        @forelse($row['completedTasks'] as $assignment)
                                            <div class="rounded-xl bg-white border border-slate-100 px-3 py-2">
                                                <p class="text-xs font-bold text-slate-800">{{ $assignment->task->title ?? 'Task removed' }}</p>
                                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $assignment->event->title ?? 'Event removed' }}</p>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-400">No completed task with this organization yet.</p>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="min-h-56 rounded-2xl bg-slate-50 p-3 min-w-0 flex flex-col">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Rewards and certificates</p>
                                    <div class="mt-2 space-y-2 flex-1">
                                        @forelse($row['orgRewards'] as $reward)
                                            <div class="rounded-xl bg-white border border-slate-100 px-3 py-2">
                                                <p class="text-xs font-bold text-slate-800">{{ $reward->label() ?? 'Reward' }}</p>
                                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $reward->reward_scope === 'event' ? ($reward->event->title ?? 'Event reward') : ($reward->task->title ?? 'Task reward') }}</p>
                                            </div>
                                        @empty
                                            @if($row['orgCertificates']->isEmpty())
                                                <p class="text-xs text-slate-400">No reward recorded yet.</p>
                                            @endif
                                        @endforelse
                                        @foreach($row['orgCertificates'] as $certificate)
                                            <div class="rounded-xl bg-white border border-slate-100 px-3 py-2">
                                                <p class="text-xs font-bold text-slate-800">Certificate {{ $certificate->certificate_code }}</p>
                                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $certificate->event->title ?? 'Event removed' }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p id="volunteer-empty" class="hidden text-sm text-slate-400 text-center py-8">No volunteers match this view.</p>
            @endif
        @endif
    </div>

    @if($activeTab === 'volunteers')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const search = document.getElementById('volunteer-search');
                const empty = document.getElementById('volunteer-empty');
                const cards = Array.from(document.querySelectorAll('[data-volunteer-card]'));
                let memberFilter = 'all';

                function applyRoster() {
                    const query = (search?.value || '').trim().toLowerCase();
                    let visible = 0;
                    cards.forEach(function (card) {
                        const matchesMember = memberFilter === 'all' || card.dataset.member === memberFilter;
                        const matchesQuery = query === '' || (card.dataset.search || '').includes(query);
                        const show = matchesMember && matchesQuery;
                        card.classList.toggle('hidden', !show);
                        const detail = document.getElementById('volunteer-detail-' + card.dataset.volunteerId);
                        if (detail && !show) detail.classList.add('hidden');
                        const cardButton = card.querySelector('[data-panel-toggle]');
                        if (!show && cardButton) cardButton.textContent = 'View';
                        if (show) visible += 1;
                    });
                    if (empty) empty.classList.toggle('hidden', visible !== 0 || cards.length === 0);
                }

                function closeDetails(exceptId) {
                    document.querySelectorAll('[data-volunteer-detail]').forEach(function (panel) {
                        if (panel.id !== exceptId) panel.classList.add('hidden');
                    });
                    document.querySelectorAll('#volunteer-roster [data-panel-toggle]').forEach(function (button) {
                        if (button.dataset.panelToggle !== exceptId && button.textContent.trim() === 'Hide') {
                            button.textContent = 'View';
                        }
                    });
                }

                search?.addEventListener('input', applyRoster);
                document.querySelectorAll('[data-volunteer-filter]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        memberFilter = button.dataset.volunteerFilter;
                        document.querySelectorAll('[data-volunteer-filter]').forEach(function (item) {
                            const selected = item === button;
                            item.classList.toggle('bg-jci-blue', selected);
                            item.classList.toggle('text-white', selected);
                            item.classList.toggle('bg-slate-50', !selected);
                            item.classList.toggle('text-slate-500', !selected);
                            item.classList.toggle('border', !selected);
                            item.classList.toggle('border-slate-200', !selected);
                        });
                        applyRoster();
                    });
                });
                document.querySelectorAll('[data-panel-toggle]').forEach(function (button) {
                    if (!button.dataset.panelToggle.startsWith('volunteer-detail-')) return;
                    button.addEventListener('click', function () {
                        const panel = document.getElementById(button.dataset.panelToggle);
                        if (!panel) return;
                        const willOpen = panel.classList.contains('hidden');
                        closeDetails(willOpen ? panel.id : '');
                        panel.classList.toggle('hidden', !willOpen);
                        const cardButton = document.querySelector('#volunteer-roster article [data-panel-toggle="' + panel.id + '"]');
                        if (cardButton) cardButton.textContent = willOpen ? 'Hide' : 'View';
                        if (willOpen) panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    });
                });
            });
        </script>
    @endif
    <!-- TAB 3: USER LISTS -->
    <div class="{{ $activeTab === 'users' ? '' : 'hidden' }} space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Total Users', 'value' => $systemUsers->count(), 'icon' => 'fa-users', 'iconBg' => 'bg-blue-50', 'iconColor' => 'text-jci-blue'],
                ['label' => 'Volunteers', 'value' => $systemUsers->where('role', 'volunteer')->count(), 'icon' => 'fa-user', 'iconBg' => 'bg-sky-50', 'iconColor' => 'text-sky-600'],
                ['label' => 'Organizations', 'value' => $systemUsers->where('role', 'organization')->count(), 'icon' => 'fa-building', 'iconBg' => 'bg-violet-50', 'iconColor' => 'text-violet-600'],
                ['label' => 'Approved Accounts', 'value' => $systemUsers->where('status', 'approved')->count(), 'icon' => 'fa-circle-check', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
            ] as $card)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-5">
                    <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                    <p class="mt-4 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3 p-4 border-b border-slate-100">
                <label class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input id="user-list-search" type="search" placeholder="Search by name or email..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-jci-blue/30 focus:border-jci-blue">
                </label>
                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl p-1" id="user-list-filters">
                    @foreach(['all' => 'All', 'volunteer' => 'Volunteer', 'organization' => 'Organization'] as $value => $label)
                        <button type="button" data-role-filter="{{ $value }}"
                                class="user-role-filter px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $value === 'all' ? 'bg-jci-blue text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-[11px] uppercase tracking-wider text-slate-400">
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-3 font-semibold">User</th>
                            <th class="px-4 py-3 font-semibold">Role</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Joined</th>
                            <th class="px-4 py-3 font-semibold">Organization Links</th>
                            <th class="px-4 py-3 font-semibold">Activity</th>
                            <th class="px-5 py-3 font-semibold">Skills</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($systemUsers as $user)
                            @php
                                $linkedOrganizations = collect();

                                if ($user->role === 'organization') {
                                    $linkedOrganizations = collect([$user->name]);
                                } else {
                                    if ($user->primaryOrganization) {
                                        $linkedOrganizations->push($user->primaryOrganization->name);
                                    }

                                    $assignmentOrgs = $user->assignments
                                        ->map(fn($assignment) => $assignment->event?->organization?->name)
                                        ->filter();
                                    $applicationOrgs = $user->taskApplications
                                        ->map(fn($application) => $application->event?->organization?->name)
                                        ->filter();
                                    $linkedOrganizations = $assignmentOrgs->merge($applicationOrgs)->unique()->values();
                                }

                                $statusClass = match ($user->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-600',
                                    'pending' => 'bg-amber-50 text-amber-600',
                                    'rejected' => 'bg-rose-50 text-rose-600',
                                    default => 'bg-slate-100 text-slate-500',
                                };
                                $initials = collect(explode(' ', $user->name))->map(fn($part) => substr($part, 0, 1))->take(2)->join('');
                                $searchText = strtolower(trim($user->name . ' ' . $user->email . ' ' . ($user->phone ?? '')));
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition" data-user-row data-role="{{ $user->role }}" data-search="{{ $searchText }}">
                                <td class="px-5 py-3.5 align-middle">
                                    <div class="flex items-center gap-3 min-w-52">
                                        @if($user->profile_photo_path)
                                            <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="{{ $user->name }}" class="h-9 w-9 rounded-full object-cover shrink-0">
                                        @else
                                            <span class="h-9 w-9 rounded-full bg-jci-blue text-white flex items-center justify-center text-[11px] font-bold shrink-0">
                                                {{ strtoupper($initials) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 truncate">{{ $user->name }}</div>
                                            <div class="text-xs text-slate-400 truncate">{{ $user->email }}</div>
                                            @if($user->phone)
                                                <div class="text-[11px] text-slate-400">{{ $user->phone }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 align-middle text-slate-600 capitalize whitespace-nowrap">{{ $user->role }}</td>
                                <td class="px-4 py-3.5 align-middle">
                                    <span class="{{ $statusClass }} text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wide">
                                        {{ $user->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 align-middle text-slate-500 whitespace-nowrap text-xs">
                                    {{ $user->created_at?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3.5 align-middle min-w-48">
                                    @if($linkedOrganizations->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($linkedOrganizations->take(4) as $orgName)
                                                <span class="bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-semibold px-2 py-1 rounded-lg">
                                                    {{ $orgName }}
                                                </span>
                                            @endforeach
                                            @if($linkedOrganizations->count() > 4)
                                                <span class="text-[10px] text-slate-400 font-bold px-1">+{{ $linkedOrganizations->count() - 4 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">No linked organization yet</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 align-middle">
                                    @if($user->role === 'volunteer')
                                        <div class="text-xs text-slate-500 space-y-0.5 whitespace-nowrap">
                                            <div><span class="font-bold text-slate-700">{{ $user->assignments->count() }}</span> assignment(s)</div>
                                            <div><span class="font-bold text-slate-700">{{ $user->certificates->count() }}</span> certificate(s)</div>
                                            <div><span class="font-bold text-slate-700">{{ $user->taskApplications->count() }}</span> application(s)</div>
                                        </div>
                                    @else
                                        <div class="text-xs text-slate-500 whitespace-nowrap">Partner organization account</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 align-middle min-w-40">
                                    @if($user->skills->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->skills->take(4) as $skill)
                                                <span class="bg-blue-50 text-jci-blue border border-blue-100 text-[10px] font-semibold px-2 py-0.5 rounded-lg">
                                                    {{ $skill->name }}
                                                </span>
                                            @endforeach
                                            @if($user->skills->count() > 4)
                                                <span class="text-[10px] text-slate-400 font-bold">+{{ $user->skills->count() - 4 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">None listed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="user-list-empty" class="hidden">
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">
                                No users match this search.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($activeTab === 'users')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const search = document.getElementById('user-list-search');
                const rows = Array.from(document.querySelectorAll('[data-user-row]'));
                const empty = document.getElementById('user-list-empty');
                const filters = Array.from(document.querySelectorAll('[data-role-filter]'));
                let role = 'all';

                function paintFilters() {
                    filters.forEach(function (button) {
                        const active = button.dataset.roleFilter === role;
                        button.classList.toggle('bg-jci-blue', active);
                        button.classList.toggle('text-white', active);
                        button.classList.toggle('shadow-sm', active);
                        button.classList.toggle('text-slate-500', !active);
                    });
                }

                function applyFilters() {
                    const query = search.value.trim().toLowerCase();
                    let visible = 0;
                    rows.forEach(function (row) {
                        const matchesRole = role === 'all' || row.dataset.role === role;
                        const matchesQuery = query === '' || (row.dataset.search || '').includes(query);
                        const show = matchesRole && matchesQuery;
                        row.classList.toggle('hidden', !show);
                        if (show) visible += 1;
                    });
                    if (empty) empty.classList.toggle('hidden', visible !== 0 || rows.length === 0);
                }

                const presetQuery = new URLSearchParams(window.location.search).get('q');
                if (presetQuery) {
                    search.value = presetQuery;
                }
                search.addEventListener('input', applyFilters);
                if (presetQuery) {
                    applyFilters();
                }
                filters.forEach(function (button) {
                    button.addEventListener('click', function () {
                        role = button.dataset.roleFilter;
                        paintFilters();
                        applyFilters();
                    });
                });
            });
        </script>
    @endif

    <!-- TAB 4: CHATBOT CONFIG -->
    <div class="{{ $activeTab === 'chatbot' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-robot text-jci-blue"></i> Chatbot Rule & Intent Configurator
            </h4>
            <span class="text-xs text-slate-400">Rule-Based Intent System</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Rule Input Form -->
            <form action="{{ route('admin.chatbot.rules.store') }}" method="POST" class="bg-slate-50/50 rounded-2xl p-4 border border-slate-200/80 space-y-4 h-fit premium-shadow">
                @csrf
                <h5 class="font-bold text-xs text-slate-700">Add New Direct Intent Rule</h5>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Trigger Keyword</label>
                    <input name="keyword" type="text" required placeholder="e.g., registration" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Response Match</label>
                    <textarea name="response" required placeholder="Enter predefined template response..." class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-24 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-jci-accent hover:bg-amber-600 text-jci-dark font-black text-xs py-2.5 rounded-xl transition-all flex items-center justify-center gap-1">
                    <i class="fa-solid fa-plus"></i> Save Pattern
                </button>
            </form>

            <!-- Active Rules List -->
            <div class="md:col-span-2 space-y-2">
                <h5 class="font-bold text-xs text-slate-700 mb-2">Active Rules Index</h5>
                
                @if($chatbotRules->count() > 0)
                    <div class="space-y-2 max-h-[350px] overflow-y-auto custom-scrollbar pr-1">
                        @foreach($chatbotRules as $rule)
                            <div class="p-3 bg-slate-50/30 rounded-2xl border border-slate-200/60 flex items-start justify-between gap-4 hover-lift premium-shadow">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="bg-blue-100 text-jci-blue text-[9px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                            Keyword: {{ $rule->keyword }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 italic">"{{ $rule->response }}"</p>
                                </div>
                                
                                <form action="{{ route('admin.chatbot.rules.destroy', $rule->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this rule?')" class="text-slate-400 hover:text-rose-600 p-1.5 transition">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400">No chatbot rules created yet. Add one using the form on the left.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="pt-5 border-t border-slate-100 space-y-3">
            <div class="flex justify-between items-center">
                <h5 class="font-bold text-xs text-slate-700 flex items-center gap-2">
                    <i class="fa-solid fa-comments text-jci-blue"></i> Recent Volunteer Chatbot Conversations
                </h5>
                <span class="text-[10px] text-slate-400 font-semibold">Latest 30 messages</span>
            </div>

            @if($chatbotLogs->count() > 0)
                <div class="space-y-2 max-h-[420px] overflow-y-auto custom-scrollbar pr-1">
                    @foreach($chatbotLogs as $log)
                        <div class="p-4 bg-slate-50/40 rounded-2xl border border-slate-200/70 premium-shadow">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                <div>
                                    <h6 class="text-xs font-black text-slate-800">
                                        {{ $log->user->name ?? 'Deleted volunteer' }}
                                    </h6>
                                    <p class="text-[10px] text-slate-400">
                                        {{ $log->user->email ?? 'No account attached' }} &middot; {{ $log->created_at->format('M d, Y g:i A') }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @if($log->intent)
                                        <span class="bg-blue-100 text-jci-blue text-[9px] font-extrabold px-2 py-0.5 rounded-full uppercase">
                                            {{ $log->intent }}
                                        </span>
                                    @endif
                                    @if(!is_null($log->confidence))
                                        <span class="bg-slate-200 text-slate-600 text-[9px] font-bold px-2 py-0.5 rounded-full">
                                            {{ round($log->confidence * 100) }}%
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="bg-white border border-slate-200 rounded-xl p-3">
                                    <span class="block text-[9px] font-black uppercase text-slate-400 mb-1">Volunteer asked</span>
                                    <p class="text-xs text-slate-700 leading-relaxed">{{ $log->message }}</p>
                                </div>
                                <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-3">
                                    <span class="block text-[9px] font-black uppercase text-jci-blue mb-1">Bot replied</span>
                                    <p class="text-xs text-slate-700 leading-relaxed">{{ $log->response }}</p>
                                </div>
                            </div>
                            <div class="mt-3 bg-white border border-slate-200 rounded-xl p-3">
                                @if($log->admin_reply)
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <span class="block text-[9px] font-black uppercase text-emerald-600 mb-1">Admin reply</span>
                                            <p class="text-xs text-slate-700 leading-relaxed">{{ $log->admin_reply }}</p>
                                            <p class="text-[9px] text-slate-400 mt-1">
                                                Sent {{ $log->admin_replied_at?->format('M d, Y g:i A') }}
                                            </p>
                                        </div>
                                        <span class="bg-emerald-100 text-emerald-700 text-[9px] font-black px-2 py-1 rounded uppercase shrink-0">Answered</span>
                                    </div>
                                @else
                                    <form action="{{ route('admin.chatbot.conversations.reply', $log->id) }}" method="POST" class="space-y-2">
                                        @csrf
                                        <label class="block text-[9px] font-black uppercase text-slate-400">Admin reply to volunteer</label>
                                        <textarea name="admin_reply" rows="2" maxlength="2000" required
                                                  placeholder="Write a clear answer or follow-up instruction..."
                                                  class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
                                        <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white text-[10px] font-black px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-reply"></i> Send Admin Reply
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <i class="fa-solid fa-comment-slash text-slate-300 text-3xl mb-2"></i>
                    <p class="text-xs text-slate-400">No volunteer chatbot conversations have been recorded yet.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- TAB: NOTIFICATIONS -->
    <div class="{{ $activeTab === 'notifications' ? '' : 'hidden' }} space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Unread', 'value' => $adminNotices->where('read', false)->count(), 'icon' => 'fa-bell', 'iconBg' => 'bg-rose-50', 'iconColor' => 'text-rose-500'],
                ['label' => 'Registrations waiting', 'value' => $pendingOrgs->count(), 'icon' => 'fa-building', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-500'],
                ['label' => 'Events to review', 'value' => $eventsAwaitingReview, 'icon' => 'fa-calendar-check', 'iconBg' => 'bg-sky-50', 'iconColor' => 'text-sky-600'],
                ['label' => 'Completions in review', 'value' => $pendingCompletionReviews, 'icon' => 'fa-clipboard-check', 'iconBg' => 'bg-indigo-50', 'iconColor' => 'text-indigo-500'],
            ] as $card)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-5">
                    <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                    <p class="mt-4 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h4 class="font-extrabold text-slate-900">Notice history</h4>
                        <p class="text-xs text-slate-400 mt-0.5">The full message for each notice sent to this admin account.</p>
                    </div>
                </div>
                <div class="space-y-3">
                    @forelse($adminNotices as $notice)
                        <article id="notice-{{ $notice['id'] }}" class="rounded-2xl border p-4 {{ $selectedNotice && $selectedNotice['id'] === $notice['id'] ? 'border-jci-blue bg-sky-50' : 'border-slate-100 bg-white' }}">
                            <div class="flex items-start gap-3">
                                <span class="h-9 w-9 rounded-full shrink-0 flex items-center justify-center text-xs {{ $notice['read'] ? 'bg-slate-100 text-slate-400' : 'bg-sky-100 text-jci-blue' }}">
                                    <i class="fa-solid {{ $notice['icon'] }}"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <h5 class="font-bold text-slate-900">{{ $notice['title'] }}</h5>
                                        <span class="text-[10px] font-bold uppercase tracking-wide {{ $notice['read'] ? 'text-slate-400' : 'text-jci-blue' }} shrink-0">{{ $notice['read'] ? 'Read' : 'New' }}</span>
                                    </div>
                                    <p class="text-sm text-slate-600 leading-relaxed mt-2">{{ $notice['message'] }}</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-3">
                                        <span class="text-[11px] text-slate-400">{{ $notice['stamp'] }} · {{ $notice['when'] }}</span>
                                        @if($notice['url'])
                                            <a href="{{ $notice['url'] }}" class="text-xs font-bold text-jci-blue hover:underline">Open the related page</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 p-10 text-center">
                            <i class="fa-solid fa-bell text-slate-300 text-3xl mb-2"></i>
                            <p class="text-sm text-slate-400">No notices yet. New organization applications, events waiting for approval, task submissions, and compliance uploads will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                    <h4 class="font-extrabold text-slate-900">What needs attention</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Live counts from the current records.</p>
                    <div class="mt-4 space-y-2">
                        <a href="{{ route('admin.dashboard', ['tab' => 'audits']) }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-3 hover:border-sky-200">
                            <span class="text-sm font-semibold text-slate-700">Organization applications</span>
                            <span class="text-sm font-black text-slate-900">{{ $pendingOrgs->count() }}</span>
                        </a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'events', 'event_status' => 'review']) }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-3 hover:border-sky-200">
                            <span class="text-sm font-semibold text-slate-700">Events awaiting approval</span>
                            <span class="text-sm font-black text-slate-900">{{ $eventsAwaitingReview }}</span>
                        </a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'tasks']) }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-3 hover:border-sky-200">
                            <span class="text-sm font-semibold text-slate-700">Completions in organization review</span>
                            <span class="text-sm font-black text-slate-900">{{ $pendingCompletionReviews }}</span>
                        </a>
                    </div>
                </div>
                @if($selectedNotice)
                    <div class="bg-white rounded-3xl border border-sky-100 shadow-sm p-5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Opened from the bell</p>
                        <h5 class="font-bold text-slate-900 mt-2">{{ $selectedNotice['title'] }}</h5>
                        <p class="text-sm text-slate-600 leading-relaxed mt-2">{{ $selectedNotice['message'] }}</p>
                        <p class="text-[11px] text-slate-400 mt-3">{{ $selectedNotice['stamp'] }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @if($activeTab === 'notifications' && $selectedNotice)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('notice-{{ $selectedNotice['id'] }}')?.scrollIntoView({ block: 'center' });
            });
        </script>
    @endif

    <!-- TAB 4: SYSTEM NOTICES -->
    <div class="{{ $activeTab === 'broadcast' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-tower-broadcast text-jci-blue"></i> System Notice Console
            </h4>
            <span class="text-xs text-slate-400 font-bold flex items-center gap-1">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Web Portal
            </span>
        </div>
        
        <form action="{{ route('admin.broadcast') }}" method="POST" class="space-y-4 max-w-2xl">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Notice Type</label>
                    <select name="notice_type" required class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-white">
                        <option value="technical_issue">Technical Issue</option>
                        <option value="maintenance">Scheduled Maintenance</option>
                        <option value="service_update">Service Update</option>
                        <option value="general_advisory">General Advisory</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Impact Level</label>
                    <select name="impact_level" required class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-white">
                        <option value="info">Informational</option>
                        <option value="minor">Minor Impact</option>
                        <option value="major">Major Impact</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Notice Title</label>
                <input name="title" type="text" required placeholder="e.g., Login Issue Under Investigation" 
                       class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Announcement Message</label>
                <textarea name="body" required placeholder="State the issue, affected area, current action, and expected next update..." 
                          class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-24 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
            </div>
            <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs py-3 px-6 rounded-xl transition duration-300 flex items-center gap-2">
                <i class="fa-solid fa-paper-plane"></i> Post System Announcement
            </button>
        </form>
    </div>

</x-layout.admin>
