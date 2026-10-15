<x-layout.admin activeTab="audit">
    @php
        $filtersOpen = request()->hasAny(['from', 'to', 'role', 'status', 'module', 'action_type']);
        $formatValues = function ($values) {
            if (!is_array($values) || $values === []) {
                return 'None recorded';
            }

            return collect($values)->map(function ($value, $key) {
                $shown = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;

                return str_replace('_', ' ', (string) $key).': '.$shown;
            })->implode("\n");
        };
    @endphp

    <div class="flex flex-wrap items-center gap-2">
        @foreach([
            ['key' => null, 'label' => 'All', 'value' => $summary['total']],
            ['key' => 'admin', 'label' => 'Admin', 'value' => $summary['admin']],
            ['key' => 'organization', 'label' => 'Organization', 'value' => $summary['organization']],
            ['key' => 'volunteer', 'label' => 'Volunteer', 'value' => $summary['volunteer']],
            ['key' => 'failed', 'label' => 'Failed', 'value' => $summary['failed']],
        ] as $card)
            @php
                $active = $card['key'] === 'failed'
                    ? request('status') === 'failed' && !request('role')
                    : ($card['key'] === null ? !request('role') && !request('status') : request('role') === $card['key'] && !request('status'));
                $params = request()->except(['page', 'role', 'status']);
                if ($card['key'] === 'failed') {
                    $params['status'] = 'failed';
                } elseif ($card['key']) {
                    $params['role'] = $card['key'];
                }
            @endphp
            <a href="{{ route('admin.audit', $params) }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold {{ $active ? 'border-jci-blue bg-sky-50 text-jci-blue' : 'border-slate-200 bg-white text-slate-600 hover:border-jci-blue' }}">
                {{ $card['label'] }}
                <span class="rounded-full bg-white/80 px-1.5 text-[11px] font-black">{{ number_format($card['value']) }}</span>
            </a>
        @endforeach
        <span class="ml-auto text-[11px] text-slate-400">Kept {{ (int) config('audit.retention_months', 24) }} months. Entries cannot be edited or deleted here.</span>
    </div>

    <form method="GET" action="{{ route('admin.audit') }}" class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <span class="h-8 w-8 rounded-full bg-sky-50 text-jci-blue flex items-center justify-center">
                    <i class="fa-solid fa-wave-square text-sm"></i>
                </span>
                <h2 class="font-black text-slate-900">Activity Trail</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search logs..." class="w-56 rounded-full border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-sm text-slate-700">
                </label>
                <button type="button" id="audit-filter-toggle" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-jci-blue hover:text-jci-blue">Filters</button>
                <a href="{{ route('admin.audit.export', ['format' => 'csv']) }}?{{ http_build_query(request()->except('page')) }}" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-emerald-500 hover:text-emerald-600">CSV</a>
                <a href="{{ route('admin.audit.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except('page')) }}" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-jci-blue hover:text-jci-blue">PDF</a>
            </div>
        </div>

        <div id="audit-filters" class="{{ $filtersOpen ? '' : 'hidden' }} px-5 py-4 border-b border-slate-100 bg-slate-50/70 grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <label class="text-[11px] font-bold text-slate-500">
                From
                <input type="date" name="from" value="{{ request('from') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
            </label>
            <label class="text-[11px] font-bold text-slate-500">
                To
                <input type="date" name="to" value="{{ request('to') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
            </label>
            <label class="text-[11px] font-bold text-slate-500">
                User role
                <select name="role" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                    <option value="">All roles</option>
                    @foreach(\App\Support\AuditTrail::ROLES as $value => $label)
                        <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-[11px] font-bold text-slate-500">
                Module
                <select name="module" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                    <option value="">All modules</option>
                    @foreach(\App\Support\AuditTrail::MODULES as $module)
                        <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>{{ $module }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-[11px] font-bold text-slate-500">
                Action type
                <select name="action_type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                    <option value="">All action types</option>
                    @foreach(\App\Support\AuditTrail::ACTION_TYPES as $value => $label)
                        <option value="{{ $value }}" {{ request('action_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-[11px] font-bold text-slate-500">
                Status
                <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800">
                    <option value="">All results</option>
                    @foreach(\App\Support\AuditTrail::STATUSES as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="md:col-span-3 xl:col-span-6 flex justify-end gap-2">
                <a href="{{ route('admin.audit') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-600">Clear</a>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white">Apply</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-left">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3 font-bold">Action</th>
                        <th class="px-4 py-3 font-bold">Actor</th>
                        <th class="px-4 py-3 font-bold">Target</th>
                        <th class="px-4 py-3 font-bold">IP</th>
                        <th class="px-5 py-3 font-bold text-right">When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php [$icon, $tone] = \App\Support\AuditTrail::icon($log); @endphp
                        <tr data-audit-row="{{ $log->id }}" class="border-t border-slate-100 cursor-pointer hover:bg-slate-50/80">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="h-8 w-8 rounded-full {{ $tone }} flex items-center justify-center shrink-0">
                                        <i class="fa-solid {{ $icon }} text-xs"></i>
                                    </span>
                                    <span class="font-semibold text-slate-800 text-sm">{{ $log->action }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-slate-600">{{ $log->actor_name }}</td>
                            <td class="px-4 py-3.5 text-sm text-slate-600 max-w-[220px] truncate">{{ $log->subject_label ?: '—' }}</td>
                            <td class="px-4 py-3.5 text-sm text-slate-500">{{ $log->ip_address ?: '—' }}</td>
                            <td class="px-5 py-3.5 text-sm text-slate-500 text-right whitespace-nowrap">
                                {{ \App\Support\AuditTrail::trailWhen($log->created_at) }}
                                <i data-audit-chevron="{{ $log->id }}" class="fa-solid fa-chevron-down ml-2 text-[10px] text-slate-300"></i>
                            </td>
                        </tr>
                        <tr id="audit-detail-{{ $log->id }}" data-audit-detail="{{ $log->id }}" class="hidden">
                            <td colspan="5" class="px-5 pb-4 bg-slate-50/60">
                                <div class="rounded-2xl border border-slate-200 bg-white p-4 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Log ID</p>
                                        <p class="mt-1 font-semibold text-slate-800">{{ $log->id }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Date and time</p>
                                        <p class="mt-1 font-semibold text-slate-800">{{ \App\Support\AuditTrail::localTime($log->created_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Performed by</p>
                                        <p class="mt-1 font-semibold text-slate-800">{{ $log->actor_name }} · {{ \App\Support\AuditTrail::roleLabel($log->actor_role) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Module</p>
                                        <p class="mt-1 font-semibold text-slate-800">{{ $log->module }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Affected record</p>
                                        <p class="mt-1 font-semibold text-slate-800">{{ $log->subject_label ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Result</p>
                                        <p class="mt-1 font-semibold {{ $log->status === 'failed' ? 'text-rose-600' : 'text-emerald-600' }}">{{ $log->status === 'failed' ? 'Failed' : 'Success' }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Description</p>
                                        <p class="mt-1 font-semibold text-slate-800">{{ $log->description }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Previous values</p>
                                        <p class="mt-1 font-semibold text-slate-800 whitespace-pre-wrap">{{ $formatValues($log->previous_values) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">New values</p>
                                        <p class="mt-1 font-semibold text-slate-800 whitespace-pre-wrap">{{ $formatValues($log->new_values) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">IP address</p>
                                        <p class="mt-1 font-semibold text-slate-800">{{ $log->ip_address ?: '—' }}</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No activities have been recorded for this filter yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">{{ $logs->links() }}</div>
        @endif
    </form>

    <script>
        document.getElementById('audit-filter-toggle')?.addEventListener('click', function () {
            document.getElementById('audit-filters')?.classList.toggle('hidden');
        });

        document.querySelectorAll('[data-audit-row]').forEach(function (row) {
            row.addEventListener('click', function () {
                const detail = document.getElementById('audit-detail-' + row.dataset.auditRow);
                const willOpen = detail?.classList.contains('hidden');
                document.querySelectorAll('[data-audit-detail]').forEach(function (panel) {
                    panel.classList.add('hidden');
                });
                document.querySelectorAll('[data-audit-chevron]').forEach(function (chevron) {
                    chevron.classList.remove('rotate-180');
                });
                if (willOpen && detail) {
                    detail.classList.remove('hidden');
                    row.querySelector('[data-audit-chevron]')?.classList.add('rotate-180');
                }
            });
        });
    </script>
</x-layout.admin>
