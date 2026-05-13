@extends('layouts.admin')

@section('title', 'Reports')

@section('header')
    <x-isp-page-header
        title="Reports"
        module="Analytics"
        description="Summary KPIs and report widgets for finance and operations."
    />
@endsection

@section('content')
<div class="space-y-6">
    @if (! ($saasAdvancedAnalytics ?? true))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100">
            Advanced analytics widgets are not included in your current PrimeBilling plan. Upgrade in <a href="{{ route('isp.platform-subscription.index') }}" class="font-semibold underline">PrimeBilling subscription</a> to unlock the full analytics suite.
        </div>
    @endif
    <section class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <x-dashboard.kpi-card title="Total customers" :value="number_format($stats['total_customers'])" :subtext="($stats['active_customers'] ?? 0).' active · '.($stats['suspended_customers'] ?? 0).' suspended'" iconBg="from-indigo-500 to-cyan-500">
            <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Total revenue" :value="'KES '.number_format($stats['total_revenue'], 0)" subtext="All time" iconBg="from-emerald-500 to-teal-500">
            <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Active subscriptions" :value="number_format($stats['active_subscriptions'])" subtext="Currently active" iconBg="from-amber-500 to-orange-500">
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
    </x-filter-bar>

    <!-- Report Sections -->
    <div class="space-y-6">
        <!-- Active vs Suspended Customers Report -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Active vs Suspended Customers</h3>
                    <button onclick="loadCustomerStatusReport()" class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
                        Refresh
                    </button>
                </div>
                <div id="customer-status-summary" class="mb-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="total-customers">-</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <p class="text-sm text-green-600 dark:text-green-400">Active</p>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300" id="active-customers">-</p>
                        </div>
                        <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <p class="text-sm text-red-600 dark:text-red-400">Suspended</p>
                            <p class="text-2xl font-bold text-red-700 dark:text-red-300" id="suspended-customers">-</p>
                        </div>
                    </div>
                </div>
                <div id="customer-status-chart" class="h-64 flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                    <p class="text-gray-500 dark:text-gray-400">Chart-ready data available via API endpoint</p>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">API Endpoint:</p>
                    <code class="block p-2 bg-gray-100 dark:bg-gray-900 rounded text-xs">{{ route('isp.reports.customer-status') }}</code>
                </div>
            </div>
        </div>

        <!-- Revenue per Plan Report -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Revenue per Plan</h3>
                    <div class="flex gap-2">
                        <input type="date" id="revenue-start-date" value="{{ now()->startOfYear()->format('Y-m-d') }}" 
                               class="text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <input type="date" id="revenue-end-date" value="{{ now()->format('Y-m-d') }}" 
                               class="text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <button onclick="loadRevenuePerPlanReport()" class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
                            Load
                        </button>
                    </div>
                </div>
                <div id="revenue-per-plan-summary" class="mb-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="total-revenue">-</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-sm text-blue-600 dark:text-blue-400">Total Payments</p>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300" id="total-payments">-</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <p class="text-sm text-purple-600 dark:text-purple-400">Customers</p>
                            <p class="text-2xl font-bold text-purple-700 dark:text-purple-300" id="total-customers-revenue">-</p>
                        </div>
                    </div>
                </div>
                <div id="revenue-per-plan-table" class="mb-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Plan</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Revenue</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Payments</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Customers</th>
                                </tr>
                            </thead>
                            <tbody id="revenue-per-plan-tbody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Click "Load" to fetch data</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="revenue-per-plan-chart" class="h-64 flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                    <p class="text-gray-500 dark:text-gray-400">Chart-ready data available via API endpoint</p>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">API Endpoint:</p>
                    <code class="block p-2 bg-gray-100 dark:bg-gray-900 rounded text-xs">{{ route('isp.reports.revenue-per-plan') }}?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD</code>
                </div>
            </div>
        </div>

        <!-- Monthly Collections Report -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Monthly Collections</h3>
                    <div class="flex gap-2">
                        <select id="collections-months" class="text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="6">Last 6 months</option>
                            <option value="12" selected>Last 12 months</option>
                            <option value="24">Last 24 months</option>
                        </select>
                        <button onclick="loadMonthlyCollectionsReport()" class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
                            Load
                        </button>
                    </div>
                </div>
                <div id="monthly-collections-summary" class="mb-4">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="monthly-total-revenue">-</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-sm text-blue-600 dark:text-blue-400">Avg Monthly</p>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300" id="monthly-avg-revenue">-</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <p class="text-sm text-green-600 dark:text-green-400">Peak Month</p>
                            <p class="text-lg font-bold text-green-700 dark:text-green-300" id="peak-month">-</p>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <p class="text-sm text-yellow-600 dark:text-yellow-400">Peak Revenue</p>
                            <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300" id="peak-revenue">-</p>
                        </div>
                    </div>
                </div>
                <div id="monthly-collections-table" class="mb-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Month</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Revenue</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Payments</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Customers</th>
                                </tr>
                            </thead>
                            <tbody id="monthly-collections-tbody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Click "Load" to fetch data</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="monthly-collections-chart" class="h-64 flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                    <p class="text-gray-500 dark:text-gray-400">Chart-ready data available via API endpoint</p>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">API Endpoint:</p>
                    <code class="block p-2 bg-gray-100 dark:bg-gray-900 rounded text-xs">{{ route('isp.reports.monthly-collections') }}?months=12</code>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        // Load Customer Status Report
        function loadCustomerStatusReport() {
            fetch('{{ route("isp.reports.customer-status") }}')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-customers').textContent = data.summary.total;
                    document.getElementById('active-customers').textContent = data.summary.active;
                    document.getElementById('suspended-customers').textContent = data.summary.suspended;
                })
                .catch(error => console.error('Error:', error));
        }

        // Load Revenue per Plan Report
        function loadRevenuePerPlanReport() {
            const startDate = document.getElementById('revenue-start-date').value;
            const endDate = document.getElementById('revenue-end-date').value;
            const url = `{{ route("isp.reports.revenue-per-plan") }}?start_date=${startDate}&end_date=${endDate}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-revenue').textContent = 'KES ' + parseFloat(data.summary.total_revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('total-payments').textContent = data.summary.total_payments;
                    document.getElementById('total-customers-revenue').textContent = data.summary.total_customers;
                    
                    const tbody = document.getElementById('revenue-per-plan-tbody');
                    tbody.innerHTML = '';
                    data.details.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">${item.plan_name}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">KES ${parseFloat(item.revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-500 dark:text-gray-400">${item.payment_count}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-500 dark:text-gray-400">${item.customer_count}</td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Load Monthly Collections Report
        function loadMonthlyCollectionsReport() {
            const months = document.getElementById('collections-months').value;
            const url = `{{ route("isp.reports.monthly-collections") }}?months=${months}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('monthly-total-revenue').textContent = 'KES ' + parseFloat(data.summary.total_revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('monthly-avg-revenue').textContent = 'KES ' + parseFloat(data.summary.average_monthly).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('peak-month').textContent = data.summary.peak_month || '-';
                    document.getElementById('peak-revenue').textContent = 'KES ' + parseFloat(data.summary.peak_revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    
                    const tbody = document.getElementById('monthly-collections-tbody');
                    tbody.innerHTML = '';
                    data.details.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">${item.month}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">KES ${parseFloat(item.revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-500 dark:text-gray-400">${item.payment_count}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-500 dark:text-gray-400">${item.customer_count}</td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Auto-load reports on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadCustomerStatusReport();
        });
    </script>
@endsection
