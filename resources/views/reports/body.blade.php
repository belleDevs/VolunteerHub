@php
    $eventTotal = array_sum(array_column($report->eventStatus, 'value'));
    $recordedTaskTotal = collect($report->taskStatus)->where('key', '!=', 'overdue')->sum('value');
    $showEventChart = $eventTotal > 0 && in_array($report->category, ['overview', 'events'], true);
    $showTaskChart = $recordedTaskTotal > 0 && in_array($report->category, ['overview', 'tasks'], true);
    $showParticipation = array_sum($report->monthlyEvents) + array_sum($report->monthlyAttendance) > 0
        && in_array($report->category, ['overview', 'events'], true);
    $showSignups = $report->showSignupChart && array_sum($report->monthlySignups) > 0 && $report->category === 'overview';
@endphp

<style>
    @media print {
        aside, header, .no-print { display: none !important; }
        main, .md\:h-screen, .md\:overflow-hidden { height: auto !important; overflow: visible !important; }
        body { background: white !important; }
    }
</style>

<div class="space-y-6" id="report-sheet">
    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-jci-blue">
                {{ $report->purpose !== 'platform' ? $report->purposeLabel : ($report->audience === 'admin' ? 'Platform reports' : ($report->audience === 'organization' ? 'Organization reports' : 'Personal report')) }}
            </p>
            <h2 class="text-2xl font-black text-slate-900 mt-1">
                {{ $report->subjectTitle !== '' ? $report->subjectTitle : ($report->audience === 'volunteer' ? 'My participation' : 'VolunteerHub') }}
            </h2>
            <p class="text-sm text-slate-500 mt-1">{{ $report->purpose !== 'platform' ? $report->purposeLabel.' · ' : '' }}{{ $report->rangeLabel }}</p>
            <p class="text-xs text-slate-400 mt-2 max-w-2xl">Completed events and tasks use the status saved in VolunteerHub. A past date does not complete an event, and assigning a task does not complete it.</p>
        </div>
        <div class="flex flex-wrap gap-2 no-print {{ $report->awaitingSubject ? 'hidden' : '' }}">
            <a href="{{ route($report->exportRoute, ['format' => 'pdf']) }}?{{ $report->query() }}" class="inline-flex items-center gap-2 rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-black text-white hover:bg-sky-700">
                <i class="fa-solid fa-file-pdf"></i> Export PDF
            </a>
            <a href="{{ route($report->exportRoute, ['format' => 'xls']) }}?{{ $report->query() }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white hover:bg-emerald-700">
                <i class="fa-solid fa-file-excel"></i> Export Excel
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:border-jci-blue hover:text-jci-blue">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>

    @if($report->purpose === 'platform' && count($report->categories) > 1)
        <div class="flex flex-wrap gap-2 no-print">
            @foreach($report->categories as $key => $label)
                <a href="{{ route($report->pageRoute) }}?{{ $report->query(['category' => $key, 'status' => null, 'detail' => null]) }}"
                   class="rounded-full px-4 py-2 text-xs font-black {{ $report->category === $key ? 'bg-jci-blue text-white' : 'bg-white text-slate-600 border border-slate-200 hover:border-jci-blue hover:text-jci-blue' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route($report->pageRoute) }}" class="bg-white border border-slate-200 rounded-3xl p-4 no-print">
        <input type="hidden" name="category" value="{{ $report->category }}">
        @if($report->audience === 'admin')
            <label class="text-xs font-bold text-slate-500 block mb-3">
                Report purpose
                <select name="purpose" id="report-purpose" class="mt-1 w-full md:w-80 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                    @foreach(['platform' => 'Whole platform', 'organization' => 'One organization', 'event' => 'One event', 'task' => 'One task', 'volunteer' => 'One volunteer'] as $key => $label)
                        <option value="{{ $key }}" {{ $report->purpose === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <input type="hidden" name="purpose" value="platform">
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">
            <label class="text-xs font-bold text-slate-500 xl:col-span-1">
                Period
                <select name="range" id="report-range" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                    @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year', 'custom' => 'Custom Range'] as $key => $label)
                        <option value="{{ $key }}" {{ $report->rangeKey === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label id="report-from-wrap" class="text-xs font-bold text-slate-500 {{ $report->rangeKey === 'custom' ? '' : 'hidden' }}">
                From
                <input type="date" name="from" value="{{ $report->startDate }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-800">
            </label>
            <label id="report-to-wrap" class="text-xs font-bold text-slate-500 {{ $report->rangeKey === 'custom' ? '' : 'hidden' }}">
                To
                <input type="date" name="to" value="{{ $report->endDate }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-800">
            </label>
            @if($report->statuses !== [] && $report->purpose === 'platform')
                <label class="text-xs font-bold text-slate-500">
                    Status
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                        @foreach($report->statuses as $key => $label)
                            <option value="{{ $key }}" {{ $report->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if($report->audience === 'admin')
                <label class="text-xs font-bold text-slate-500" data-purpose="platform organization event task volunteer">
                    Organization
                    <select name="organization" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                        <option value="">{{ $report->purpose === 'organization' ? 'Choose an organization' : 'All organizations' }}</option>
                        @foreach($report->organizationOptions as $option)
                            <option value="{{ $option['id'] }}" {{ (int) $report->organizationId === (int) $option['id'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label class="text-xs font-bold text-slate-500 {{ in_array($report->purpose, ['platform', 'event', 'task'], true) ? '' : 'hidden' }}" data-purpose="platform event task">
                Event
                <select name="event" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                    <option value="">{{ $report->purpose === 'event' ? 'Choose an event' : 'All events' }}</option>
                    @foreach($report->eventOptions as $option)
                        <option value="{{ $option['id'] }}" {{ (int) $report->eventId === (int) $option['id'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </label>
            @if($report->audience === 'admin')
                <label class="text-xs font-bold text-slate-500 {{ $report->purpose === 'task' ? '' : 'hidden' }}" data-purpose="task">
                    Task
                    <select name="task" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                        <option value="">Choose a task</option>
                        @foreach($report->taskOptions as $option)
                            <option value="{{ $option['id'] }}" {{ (int) $report->taskId === (int) $option['id'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-bold text-slate-500 {{ $report->purpose === 'volunteer' ? '' : 'hidden' }}" data-purpose="volunteer">
                    Volunteer
                    <select name="volunteer" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                        <option value="">Choose a volunteer</option>
                        @foreach($report->volunteerOptions as $option)
                            <option value="{{ $option['id'] }}" {{ (int) $report->volunteerId === (int) $option['id'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label class="text-xs font-bold text-slate-500 xl:col-span-2 {{ $report->purpose === 'platform' ? '' : 'hidden' }}" data-purpose="platform">
                Search
                <input type="search" name="q" value="{{ $report->search }}" placeholder="Organization, event, task, or volunteer" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-800">
            </label>
        </div>
        <div class="mt-3 flex justify-end">
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white">Generate report</button>
        </div>
    </form>

    @if($report->awaitingSubject)
        <div class="bg-white border border-dashed border-slate-200 rounded-3xl p-8 text-sm text-slate-500">
            Choose the {{ strtolower(str_replace(' report', '', $report->purposeLabel)) }} above, then generate the report. The page, PDF, and Excel file will include only that record.
        </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
        @foreach($report->cards as $card)
            <a href="{{ route($report->pageRoute) }}?{{ $report->query(['detail' => $card['detail']]) }}#report-details"
               class="bg-white border rounded-3xl p-5 hover:border-jci-blue transition {{ $report->detail === $card['detail'] ? 'border-jci-blue ring-2 ring-sky-100' : 'border-slate-200' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-black text-slate-900">{{ is_int($card['value']) ? number_format($card['value']) : $card['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $card['hint'] }}</p>
            </a>
        @endforeach
    </div>

    @if($report->category === 'events')
        <p class="text-xs text-slate-400">Approved events are the ones stored as published. VolunteerHub does not keep a separate rejected event status. An organization can cancel an event, or leave it as a draft until an admin approves it.</p>
    @endif

    @if($showSignups || $showEventChart || $showTaskChart || $showParticipation)
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            @if($showSignups)
                <div class="bg-white border border-slate-200 rounded-3xl p-5">
                    <h3 class="font-black text-slate-900">Volunteer registrations</h3>
                    <p class="text-xs text-slate-400 mt-1">Accounts created during the selected period</p>
                    <canvas id="signupChart" class="mt-4 max-h-72"></canvas>
                </div>
            @endif
            @if($showParticipation)
                <div class="bg-white border border-slate-200 rounded-3xl p-5">
                    <h3 class="font-black text-slate-900">Participation trend</h3>
                    <p class="text-xs text-slate-400 mt-1">Events that started in each month, and volunteers on those events</p>
                    <canvas id="participationChart" class="mt-4 max-h-72"></canvas>
                </div>
            @endif
            @if($showEventChart)
                <div class="bg-white border border-slate-200 rounded-3xl p-5">
                    <h3 class="font-black text-slate-900">Event status distribution</h3>
                    <p class="text-xs text-slate-400 mt-1">Counted from each event's saved status</p>
                    <canvas id="eventStatusChart" class="mt-4 max-h-72"></canvas>
                </div>
            @endif
            @if($showTaskChart)
                <div class="bg-white border border-slate-200 rounded-3xl p-5">
                    <h3 class="font-black text-slate-900">Task status distribution</h3>
                    <p class="text-xs text-slate-400 mt-1">Overdue is a deadline flag. Those tasks also stay in their saved status.</p>
                    <canvas id="taskStatusChart" class="mt-4 max-h-72"></canvas>
                </div>
            @endif
        </div>
    @elseif(in_array($report->category, ['overview', 'events', 'tasks'], true))
        <div class="bg-white border border-dashed border-slate-200 rounded-3xl p-8 text-center text-sm text-slate-400">
            No chart for this period yet. The figures above are the records that match the filters.
        </div>
    @endif

    <div id="report-details" class="space-y-4">
        @if($report->detailTitle)
            <div class="flex items-center justify-between gap-3">
                <h3 class="font-black text-slate-900">{{ $report->detailTitle }}</h3>
                <a href="{{ route($report->pageRoute) }}?{{ $report->query(['detail' => null]) }}" class="no-print text-xs font-black text-slate-500 hover:text-jci-blue">Close details</a>
            </div>
        @endif

        @foreach($report->sections as $section)
            <section class="bg-white border border-slate-200 rounded-3xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="font-black text-slate-900">{{ $section['title'] }}</h3>
                </div>
                @if($section['rows'] === [])
                    <p class="px-5 py-8 text-sm text-slate-400">{{ $section['empty'] }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400">
                                <tr>
                                    @foreach($section['headers'] as $header)
                                        <th class="px-4 py-3 font-bold">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($section['rows'] as $row)
                                    <tr class="border-t border-slate-100">
                                        @foreach($row as $cell)
                                            <td class="px-4 py-3 font-semibold text-slate-700 whitespace-nowrap">{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
    @endif
</div>

@if($showSignups || $showEventChart || $showTaskChart || $showParticipation)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const blue = '#0097d6';
            const common = {
                responsive: true,
                plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } } },
            };

            const signups = document.getElementById('signupChart');
            if (signups) {
                const gradient = signups.getContext('2d').createLinearGradient(0, 0, 0, 260);
                gradient.addColorStop(0, 'rgba(0, 151, 214, 0.28)');
                gradient.addColorStop(1, 'rgba(0, 151, 214, 0)');
                new Chart(signups, {
                    type: 'line',
                    data: {
                        labels: @json($report->monthlyLabels),
                        datasets: [{
                            label: 'Registrations',
                            data: @json($report->monthlySignups),
                            borderColor: blue,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointBackgroundColor: blue,
                        }],
                    },
                    options: common,
                });
            }

            const participation = document.getElementById('participationChart');
            if (participation) {
                new Chart(participation, {
                    type: 'bar',
                    data: {
                        labels: @json($report->monthlyLabels),
                        datasets: [
                            { label: 'Events', data: @json($report->monthlyEvents), backgroundColor: blue, borderRadius: 6 },
                            { label: 'Volunteers', data: @json($report->monthlyAttendance), backgroundColor: '#7dd3fc', borderRadius: 6 },
                        ],
                    },
                    options: { ...common, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
                });
            }

            const eventStatus = document.getElementById('eventStatusChart');
            if (eventStatus) {
                const slices = @json($report->eventStatus);
                new Chart(eventStatus, {
                    type: 'doughnut',
                    data: {
                        labels: slices.map(slice => slice.label),
                        datasets: [{ data: slices.map(slice => slice.value), backgroundColor: slices.map(slice => slice.color), borderWidth: 0 }],
                    },
                    options: common,
                });
            }

            const taskStatus = document.getElementById('taskStatusChart');
            if (taskStatus) {
                const bars = @json($report->taskStatus);
                new Chart(taskStatus, {
                    type: 'bar',
                    data: {
                        labels: bars.map(bar => bar.label),
                        datasets: [{ label: 'Tasks', data: bars.map(bar => bar.value), backgroundColor: bars.map(bar => bar.color), borderRadius: 6 }],
                    },
                    options: { ...common, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
                });
            }
        });
    </script>
@endif

<script>
    document.getElementById('report-range')?.addEventListener('change', function () {
        const custom = this.value === 'custom';
        document.getElementById('report-from-wrap')?.classList.toggle('hidden', !custom);
        document.getElementById('report-to-wrap')?.classList.toggle('hidden', !custom);
    });

    const purposeSelect = document.getElementById('report-purpose');
    const syncPurpose = function () {
        if (!purposeSelect) {
            return;
        }
        document.querySelectorAll('[data-purpose]').forEach(function (field) {
            const allowed = field.dataset.purpose.split(' ');
            field.classList.toggle('hidden', !allowed.includes(purposeSelect.value));
        });
        const placeholders = {
            event: ['Choose an event', 'All events'],
            organization: ['Choose an organization', 'All organizations'],
        };
        const eventSelect = document.querySelector('select[name="event"]');
        if (eventSelect?.options[0]) {
            eventSelect.options[0].text = purposeSelect.value === 'event' ? placeholders.event[0] : placeholders.event[1];
        }
        const organizationSelect = document.querySelector('select[name="organization"]');
        if (organizationSelect?.options[0]) {
            organizationSelect.options[0].text = purposeSelect.value === 'organization' ? placeholders.organization[0] : placeholders.organization[1];
        }
    };
    purposeSelect?.addEventListener('change', syncPurpose);
    syncPurpose();

    document.querySelector('#report-sheet form')?.addEventListener('submit', function () {
        this.querySelectorAll('label.hidden input, label.hidden select, label.hidden textarea').forEach(function (field) {
            field.disabled = true;
        });
        if (document.getElementById('report-range')?.value !== 'custom') {
            this.querySelectorAll('#report-from-wrap input, #report-to-wrap input').forEach(function (field) {
                field.disabled = true;
            });
        }
    });
</script>
