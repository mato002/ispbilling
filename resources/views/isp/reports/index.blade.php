@extends('layouts.admin')

@section('title', $reportMeta['title'] ?? 'Reports')

@section('header')
    <x-isp-page-header
        :title="$reportMeta['title'] ?? 'Reports'"
        module="Analytics"
        :description="$reportMeta['description'] ?? 'Revenue, collections, and customer growth for the selected period.'"
    />
@endsection

@section('content')
@php
    $revenueChange = (float) ($stats['revenue_change_pct'] ?? 0);
    $changeLabel = ($revenueChange >= 0 ? '+' : '').number_format($revenueChange, 1).'% vs prior period';
@endphp
<div class="space-y-6" data-reports-page
     data-start="{{ $startDate }}"
     data-end="{{ $endDate }}"
     data-view="{{ $reportView }}">
    <div class="flex justify-end">
        <a href="{{ route('isp.reports.builder') }}" class="pb-btn pb-btn-primary">Build a custom report</a>
    </div>
    @if (! ($saasAdvancedAnalytics ?? true))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100">
            Advanced analytics widgets are not included in your current PrimeBilling plan. Upgrade in
            <a href="{{ route('isp.platform-subscription.index') }}" class="font-semibold underline">PrimeBilling subscription</a>
            to unlock the full analytics suite.
        </div>
    @endif

    @if ($reportView === 'overview')
        <section class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <x-dashboard.kpi-card title="Revenue (range)" :value="'KES '.number_format($stats['total_revenue'], 0)" :subtext="$changeLabel" iconBg="from-emerald-500 to-teal-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="New customers" :value="number_format($stats['new_customers'] ?? 0)" :subtext="number_format($stats['payment_count'] ?? 0).' payments in range'" iconBg="from-indigo-500 to-cyan-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Active subscriptions" :value="number_format($stats['active_subscriptions'])" :subtext="($stats['active_customers'] ?? 0).' active · '.($stats['suspended_customers'] ?? 0).' suspended'" iconBg="from-amber-500 to-orange-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Pending invoices" :value="number_format($stats['pending_invoices'])" :subtext="($stats['overdue_invoices'] ?? 0).' overdue'" iconBg="from-rose-500 to-red-600">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
        </section>

        <x-filter-bar :action="route('isp.reports.index')">
            <div class="min-w-[140px]">
                <label class="isp-form-label">Start date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="isp-form-input">
            </div>
            <div class="min-w-[140px]">
                <label class="isp-form-label">End date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="isp-form-input">
            </div>
            <div class="flex items-end">
                <button type="submit" class="pb-btn pb-btn-primary">Apply range</button>
            </div>
            <div class="flex items-end">
                <a href="{{ route('isp.reports.export.revenue.pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="pb-btn">Export revenue PDF</a>
            </div>
        </x-filter-bar>

        <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <a href="{{ route('isp.reports.index', ['focus' => 'revenue-per-plan']) }}" data-turbo-frame="pb-main" data-turbo-action="advance" class="pb-card pb-card-hover block p-5 no-underline">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Report</p>
                <h3 class="mt-2 text-lg font-bold text-slate-900 dark:text-white">Revenue per plan</h3>
                <p class="mt-1 text-sm text-slate-500">Break down collections by package and plan.</p>
            </a>
            <a href="{{ route('isp.reports.index', ['focus' => 'monthly-collections']) }}" data-turbo-frame="pb-main" data-turbo-action="advance" class="pb-card pb-card-hover block p-5 no-underline">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Report</p>
                <h3 class="mt-2 text-lg font-bold text-slate-900 dark:text-white">Monthly collections</h3>
                <p class="mt-1 text-sm text-slate-500">Track payment totals month over month.</p>
            </a>
            <a href="{{ route('isp.reports.index', ['focus' => 'customer-growth']) }}" data-turbo-frame="pb-main" data-turbo-action="advance" class="pb-card pb-card-hover block p-5 no-underline">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Report</p>
                <h3 class="mt-2 text-lg font-bold text-slate-900 dark:text-white">Customer growth</h3>
                <p class="mt-1 text-sm text-slate-500">New subscribers and status mix over time.</p>
            </a>
        </section>
    @endif

    @if ($reportView === 'growth')
    {{-- Customer growth + status --}}
    <section id="customer-growth" class="pb-card p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Customer growth</h3>
                <p class="text-sm text-slate-500">New customers per month and current status mix.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('isp.reports.export.growth', ['months' => 12]) }}" class="pb-btn text-sm">Export CSV</a>
                <button type="button" data-load="growth" class="pb-btn pb-btn-primary text-sm">Refresh</button>
            </div>
        </div>
        <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white" id="growth-total">—</p>
            </div>
            <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-900/20">
                <p class="text-xs uppercase tracking-wide text-emerald-600">Active</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300" id="growth-active">—</p>
            </div>
            <div class="rounded-xl bg-rose-50 p-4 dark:bg-rose-900/20">
                <p class="text-xs uppercase tracking-wide text-rose-600">Suspended</p>
                <p class="mt-1 text-2xl font-bold text-rose-700 dark:text-rose-300" id="growth-suspended">—</p>
            </div>
            <div class="rounded-xl bg-indigo-50 p-4 dark:bg-indigo-900/20">
                <p class="text-xs uppercase tracking-wide text-indigo-600">New (12 mo)</p>
                <p class="mt-1 text-2xl font-bold text-indigo-700 dark:text-indigo-300" id="growth-new">—</p>
            </div>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200">New customers</p>
                <div class="relative h-64">
                    <canvas id="chart-customer-growth"></canvas>
                </div>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200">Status mix</p>
                <div class="relative mx-auto h-64 max-w-sm">
                    <canvas id="chart-customer-status"></canvas>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if ($reportView === 'revenue')
    {{-- Revenue per plan --}}
    <section id="revenue-per-plan" class="pb-card p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Revenue per plan</h3>
                <p class="text-sm text-slate-500">Completed payments attributed through invoice → subscription → plan.</p>
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="isp-form-label text-xs">From</label>
                    <input type="date" id="revenue-start-date" value="{{ $startDate }}" class="isp-form-input text-sm">
                </div>
                <div>
                    <label class="isp-form-label text-xs">To</label>
                    <input type="date" id="revenue-end-date" value="{{ $endDate }}" class="isp-form-input text-sm">
                </div>
                <a id="revenue-export-link" href="{{ route('isp.reports.export.revenue', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="pb-btn text-sm">Export CSV</a>
                <a id="revenue-pdf-link" href="{{ route('isp.reports.export.revenue.pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="pb-btn text-sm">Statement PDF</a>
                <button type="button" data-load="revenue" class="pb-btn pb-btn-primary text-sm">Refresh</button>
            </div>
        </div>
        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total revenue</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white" id="revenue-total">—</p>
            </div>
            <div class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20">
                <p class="text-xs uppercase tracking-wide text-blue-600">Payments</p>
                <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300" id="revenue-payments">—</p>
            </div>
            <div class="rounded-xl bg-violet-50 p-4 dark:bg-violet-900/20">
                <p class="text-xs uppercase tracking-wide text-violet-600">Customers</p>
                <p class="mt-1 text-2xl font-bold text-violet-700 dark:text-violet-300" id="revenue-customers">—</p>
            </div>
        </div>
        <div class="relative mb-6 h-72">
            <canvas id="chart-revenue-plan"></canvas>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2">Plan</th>
                        <th class="px-3 py-2 text-right">Revenue</th>
                        <th class="px-3 py-2 text-right">Payments</th>
                        <th class="px-3 py-2 text-right">Customers</th>
                    </tr>
                </thead>
                <tbody id="revenue-tbody" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
            </table>
        </div>
    </section>
    @endif

    @if ($reportView === 'collections')
    {{-- Monthly collections --}}
    <section id="monthly-collections" class="pb-card p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Monthly collections</h3>
                <p class="text-sm text-slate-500">Completed payment totals by month.</p>
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="isp-form-label text-xs">Months</label>
                    <select id="collections-months" class="isp-form-select text-sm">
                        @foreach ([6, 12, 18, 24] as $m)
                            <option value="{{ $m }}" @selected($m === 12)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <a id="collections-export-link" href="{{ route('isp.reports.export.collections', ['months' => 12]) }}" class="pb-btn text-sm">Export CSV</a>
                <button type="button" data-load="collections" class="pb-btn pb-btn-primary text-sm">Refresh</button>
            </div>
        </div>
        <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white" id="collections-total">—</p>
            </div>
            <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-900/20">
                <p class="text-xs uppercase tracking-wide text-emerald-600">Avg / month</p>
                <p class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-300" id="collections-avg">—</p>
            </div>
            <div class="rounded-xl bg-amber-50 p-4 dark:bg-amber-900/20">
                <p class="text-xs uppercase tracking-wide text-amber-600">Peak month</p>
                <p class="mt-1 text-xl font-bold text-amber-700 dark:text-amber-300" id="collections-peak-month">—</p>
            </div>
            <div class="rounded-xl bg-cyan-50 p-4 dark:bg-cyan-900/20">
                <p class="text-xs uppercase tracking-wide text-cyan-600">Peak revenue</p>
                <p class="mt-1 text-xl font-bold text-cyan-700 dark:text-cyan-300" id="collections-peak-revenue">—</p>
            </div>
        </div>
        <div class="relative mb-6 h-72">
            <canvas id="chart-collections"></canvas>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2">Month</th>
                        <th class="px-3 py-2 text-right">Revenue</th>
                        <th class="px-3 py-2 text-right">Payments</th>
                        <th class="px-3 py-2 text-right">Customers</th>
                    </tr>
                </thead>
                <tbody id="collections-tbody" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
            </table>
        </div>
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const page = document.querySelector('[data-reports-page]');
    if (!page || typeof Chart === 'undefined') return;

    const view = page.getAttribute('data-view') || 'overview';
    const routes = {
        growth: @json(route('isp.reports.customer-status')),
        revenue: @json(route('isp.reports.revenue-per-plan')),
        collections: @json(route('isp.reports.monthly-collections')),
        exportRevenue: @json(route('isp.reports.export.revenue')),
        exportRevenuePdf: @json(route('isp.reports.export.revenue.pdf')),
        exportCollections: @json(route('isp.reports.export.collections')),
    };

    const charts = {};
    const money = (n) => 'KES ' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

    async function fetchJson(url) {
        const sep = url.includes('?') ? '&' : '?';
        const res = await fetch(url + sep + 'format=json', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    function upsertChart(id, type, labels, datasets, options = {}) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(canvas, {
            type,
            data: { labels, datasets },
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: type === 'doughnut' || datasets.length > 1 } },
                scales: type === 'doughnut' ? {} : {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } },
                },
            }, options),
        });
    }

    async function loadGrowth() {
        const data = await fetchJson(routes.growth + '?months=12');
        document.getElementById('growth-total').textContent = data.summary.total ?? 0;
        document.getElementById('growth-active').textContent = data.summary.active ?? 0;
        document.getElementById('growth-suspended').textContent = data.summary.suspended ?? 0;
        document.getElementById('growth-new').textContent = data.summary.new_in_period ?? 0;

        upsertChart('chart-customer-growth', 'line', data.growth.labels, data.growth.datasets);
        upsertChart('chart-customer-status', 'doughnut', data.labels, data.datasets, {
            plugins: { legend: { position: 'bottom' } },
        });
    }

    async function loadRevenue() {
        const start = document.getElementById('revenue-start-date').value;
        const end = document.getElementById('revenue-end-date').value;
        const exportLink = document.getElementById('revenue-export-link');
        if (exportLink) {
            exportLink.href = `${routes.exportRevenue}?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`;
        }
        const pdfLink = document.getElementById('revenue-pdf-link');
        if (pdfLink) pdfLink.href = `${routes.exportRevenuePdf}?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`;
        const data = await fetchJson(`${routes.revenue}?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`);
        document.getElementById('revenue-total').textContent = money(data.summary.total_revenue);
        document.getElementById('revenue-payments').textContent = data.summary.total_payments ?? 0;
        document.getElementById('revenue-customers').textContent = data.summary.total_customers ?? 0;

        upsertChart('chart-revenue-plan', 'bar', data.labels, data.datasets);

        const tbody = document.getElementById('revenue-tbody');
        tbody.innerHTML = (data.details || []).map((row) => `
            <tr>
                <td class="px-3 py-2 text-sm font-medium text-slate-900 dark:text-white">${row.plan_name}</td>
                <td class="px-3 py-2 text-right text-sm tabular-nums">${money(row.revenue)}</td>
                <td class="px-3 py-2 text-right text-sm tabular-nums text-slate-500">${row.payment_count}</td>
                <td class="px-3 py-2 text-right text-sm tabular-nums text-slate-500">${row.customer_count}</td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">No plan revenue in this range.</td></tr>';
    }

    async function loadCollections() {
        const months = document.getElementById('collections-months').value || 12;
        const exportLink = document.getElementById('collections-export-link');
        if (exportLink) {
            exportLink.href = `${routes.exportCollections}?months=${encodeURIComponent(months)}`;
        }
        const data = await fetchJson(`${routes.collections}?months=${encodeURIComponent(months)}`);
        document.getElementById('collections-total').textContent = money(data.summary.total_revenue);
        document.getElementById('collections-avg').textContent = money(data.summary.average_monthly);
        document.getElementById('collections-peak-month').textContent = data.summary.peak_month || '—';
        document.getElementById('collections-peak-revenue').textContent = money(data.summary.peak_revenue);

        upsertChart('chart-collections', 'line', data.labels, data.datasets);

        const tbody = document.getElementById('collections-tbody');
        tbody.innerHTML = (data.details || []).map((row) => `
            <tr>
                <td class="px-3 py-2 text-sm font-medium text-slate-900 dark:text-white">${row.month}</td>
                <td class="px-3 py-2 text-right text-sm tabular-nums">${money(row.revenue)}</td>
                <td class="px-3 py-2 text-right text-sm tabular-nums text-slate-500">${row.payment_count}</td>
                <td class="px-3 py-2 text-right text-sm tabular-nums text-slate-500">${row.customer_count}</td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">No collections in this range.</td></tr>';
    }

    page.querySelectorAll('[data-load]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const which = btn.getAttribute('data-load');
            try {
                if (which === 'growth') await loadGrowth();
                if (which === 'revenue') await loadRevenue();
                if (which === 'collections') await loadCollections();
            } catch (e) {
                console.error(e);
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Report failed', text: e.message || 'Could not load report data' });
                }
            }
        });
    });

    document.getElementById('collections-months')?.addEventListener('change', () => {
        const months = document.getElementById('collections-months').value;
        const link = document.getElementById('collections-export-link');
        if (link) link.href = `${routes.exportCollections}?months=${encodeURIComponent(months)}`;
    });

    const initialLoads = [];
    if (view === 'growth') initialLoads.push(loadGrowth());
    if (view === 'revenue') initialLoads.push(loadRevenue());
    if (view === 'collections') initialLoads.push(loadCollections());

    if (initialLoads.length) {
        Promise.all(initialLoads).catch((e) => console.error(e));
    }
})();
</script>
@endpush
