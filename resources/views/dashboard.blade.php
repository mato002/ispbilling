@extends('layouts.admin')

@section('title', 'Dashboard')


@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
        <div class="flex gap-2">
            <a href="{{ route('isp.customers.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                + New Customer
            </a>
            <a href="{{ route('isp.payments.initiate') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                Record Payment
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if(isset($stats))
        <!-- Alerts -->
        @if(isset($alerts) && count($alerts) > 0)
            <div class="mb-6 space-y-2">
                @foreach($alerts as $alert)
                    <div class="bg-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-50 dark:bg-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-900/20 border border-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-200 dark:border-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-800 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-800 dark:text-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-200">
                                {{ $alert['message'] }}
                            </p>
                            @if(isset($alert['link']))
                                <a href="{{ $alert['link'] }}" class="text-sm font-medium text-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-600 dark:text-{{ $alert['type'] === 'danger' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-400 hover:underline">
                                    View →
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Welcome Section -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-2">Welcome, {{ Auth::user()->name }}!</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    @if(Auth::user()->isIspAdmin())
                        You are logged in as an ISP Administrator for <strong>{{ Auth::user()->isp->name ?? 'your ISP' }}</strong>.
                    @else
                        You are logged in as ISP Staff for <strong>{{ Auth::user()->isp->name ?? 'your ISP' }}</strong>.
                    @endif
                </p>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Customers -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Customers</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_customers']) }}</p>
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $stats['active_customers'] }} active</p>
                            </div>
                        </div>
                        @if(isset($customerChange))
                            <div class="text-right">
                                <p class="text-xs {{ $customerChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $customerChange >= 0 ? '↑' : '↓' }} {{ number_format(abs($customerChange), 1) }}%
                                </p>
                                <p class="text-xs text-gray-500">vs last month</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($stats['total_revenue'], 2) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">KES {{ number_format($stats['monthly_revenue'], 2) }} this month</p>
                            </div>
                        </div>
                        @if(isset($revenueChange))
                            <div class="text-right">
                                <p class="text-xs {{ $revenueChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $revenueChange >= 0 ? '↑' : '↓' }} {{ number_format(abs($revenueChange), 1) }}%
                                </p>
                                <p class="text-xs text-gray-500">vs last month</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Active Subscriptions -->
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
                            <p class="text-xs {{ $stats['expiring_soon'] > 0 ? 'text-orange-600' : 'text-gray-500' }} mt-1">
                                {{ $stats['expiring_soon'] }} expiring soon
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Sessions -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Sessions</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($stats['active_sessions']) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Currently online</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Pending Invoices -->
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
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $stats['overdue_invoices'] }} overdue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Routers -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Routers</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_routers']) }}</p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $stats['active_routers'] }} active</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vouchers -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-teal-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Vouchers</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_vouchers']) }}</p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $stats['active_vouchers'] }} active</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suspended Customers -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-orange-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Suspended</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($stats['suspended_customers']) }}</p>
                            <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">Requires attention</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Revenue Trend Chart -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Revenue Trend (Last 30 Days)</h3>
                    @if(isset($revenueTrends) && count($revenueTrends) > 0)
                        <canvas id="revenueChart" height="100"></canvas>
                    @else
                        <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <p>No revenue data available for the last 30 days</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Customer Growth Chart -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Customer Growth (Last 12 Months)</h3>
                    @if(isset($customerGrowth) && count($customerGrowth) > 0)
                        <canvas id="customerGrowthChart" height="100"></canvas>
                    @else
                        <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <p>No customer growth data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Breakdown Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Connection Type Breakdown -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Connection Type Breakdown</h3>
                    @if(isset($connectionBreakdown) && count($connectionBreakdown) > 0)
                        <canvas id="connectionChart" height="150"></canvas>
                    @else
                        <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <p>No connection type data available</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payment Method Breakdown -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Payment Methods (Last 30 Days)</h3>
                    @if(isset($paymentMethodBreakdown) && count($paymentMethodBreakdown) > 0)
                        <canvas id="paymentMethodChart" height="150"></canvas>
                    @else
                        <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <p>No payment method data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Top Customers -->
            @if(isset($topCustomers) && $topCustomers->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Top Customers (Last 30 Days)</h3>
                            <a href="{{ route('isp.customers.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Customer</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($topCustomers->take(5) as $customer)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $customer->full_name }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">
                                                KES {{ number_format($customer->payments_sum_amount ?? 0, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Top Plans -->
            @if(isset($topPlans) && $topPlans->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Top Plans</h3>
                            <a href="{{ route('isp.plans.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Plan</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Subscriptions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($topPlans->take(5) as $plan)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $plan->name }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">
                                                {{ number_format($plan->subscriptions_count) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Recent Activity Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Recent Payments -->
            @if(isset($recentPayments) && $recentPayments->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Payments</h3>
                            <a href="{{ route('isp.payments.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                        </div>
                        <div class="space-y-3">
                            @foreach($recentPayments->take(5) as $payment)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $payment->customer->full_name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $payment->processed_at?->diffForHumans() ?? 'N/A' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">KES {{ number_format($payment->amount, 2) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ strtoupper($payment->payment_method) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recent Customers -->
            @if(isset($recentCustomers) && $recentCustomers->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Customers</h3>
                            <a href="{{ route('isp.customers.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                        </div>
                        <div class="space-y-3">
                            @foreach($recentCustomers->take(5) as $customer)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $customer->full_name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $customer->created_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $customer->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ ucfirst($customer->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recent Invoices -->
            @if(isset($recentInvoices) && $recentInvoices->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Invoices</h3>
                            <a href="{{ route('isp.invoices.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                        </div>
                        <div class="space-y-3">
                            @foreach($recentInvoices->take(5) as $invoice)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invoice->customer->full_name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice->created_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($invoice->total_amount, 2) }}</p>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : ($invoice->status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <p>Loading dashboard data...</p>
            </div>
        </div>
    @endif

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Revenue Trend Chart
        @if(isset($revenueTrends) && count($revenueTrends) > 0)
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(collect($revenueTrends)->pluck('date')) !!},
                    datasets: [{
                        label: 'Revenue (KES)',
                        data: {!! json_encode(collect($revenueTrends)->pluck('revenue')) !!},
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'KES ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        @endif

        // Customer Growth Chart
        @if(isset($customerGrowth) && count($customerGrowth) > 0)
        const customerGrowthCtx = document.getElementById('customerGrowthChart');
        if (customerGrowthCtx) {
            new Chart(customerGrowthCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode(collect($customerGrowth)->pluck('month')) !!},
                    datasets: [{
                        label: 'New Customers',
                        data: {!! json_encode(collect($customerGrowth)->pluck('count')) !!},
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
        @endif

        // Connection Type Breakdown
        @if(isset($connectionBreakdown) && count($connectionBreakdown) > 0)
        const connectionCtx = document.getElementById('connectionChart');
        if (connectionCtx) {
            new Chart(connectionCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode(array_keys($connectionBreakdown->toArray())) !!},
                    datasets: [{
                        data: {!! json_encode(array_values($connectionBreakdown->toArray())) !!},
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(251, 191, 36, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true
                }
            });
        }
        @endif

        // Payment Method Breakdown
        @if(isset($paymentMethodBreakdown) && count($paymentMethodBreakdown) > 0)
        const paymentMethodCtx = document.getElementById('paymentMethodChart');
        if (paymentMethodCtx) {
            new Chart(paymentMethodCtx, {
                type: 'pie',
                data: {
                    labels: {!! json_encode($paymentMethodBreakdown->pluck('payment_method')) !!},
                    datasets: [{
                        label: 'Count',
                        data: {!! json_encode($paymentMethodBreakdown->pluck('count')) !!},
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(251, 191, 36, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true
                }
            });
        }
        @endif
    </script>
    @endpush
@endsection
