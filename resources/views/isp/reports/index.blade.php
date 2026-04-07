@extends('layouts.admin')

@section('title', 'Reports')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Reports') }}
    </h2>
@endsection

@section('content')
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Customers</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_customers']) }}</p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $stats['active_customers'] }} active, {{ $stats['suspended_customers'] ?? 0 }} suspended</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($stats['total_revenue'], 2) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">All time</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Subscriptions</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($stats['active_subscriptions']) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Currently active</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Invoices</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($stats['pending_invoices']) }}</p>
                        <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $stats['overdue_invoices'] }} overdue</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('isp.reports.index') }}" class="flex gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" 
                           class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" 
                           class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

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
