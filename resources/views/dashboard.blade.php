@extends('layouts.admin')

@section('title', 'Dashboard')

@section('header')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">ISP Operations Dashboard</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Monitor customers, billing, sessions, and network health in one view.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('isp.customers.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                + New Customer
            </a>
            <a href="{{ route('isp.payments.initiate') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                Record Payment
            </a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $stats = $stats ?? [];
        $alerts = $alerts ?? [];
    @endphp

    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Welcome, {{ Auth::user()->name }}</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            @if(Auth::user()->isIspAdmin())
                You are managing <span class="font-semibold">{{ Auth::user()->isp->name ?? 'your ISP' }}</span> as ISP Administrator.
            @else
                You are managing <span class="font-semibold">{{ Auth::user()->isp->name ?? 'your ISP' }}</span> as ISP Staff.
            @endif
        </p>
    </section>

    <section class="mb-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Critical Alerts</h3>
            <a href="{{ route('isp.notifications.index') }}" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">View all notifications</a>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            @if(($stats['overdue_invoices'] ?? 0) > 0)
                <x-dashboard.alert-card
                    title="{{ ($stats['overdue_invoices'] ?? 0) }} overdue invoice(s) require attention"
                    message="Follow up unpaid invoices to reduce service interruptions and outstanding balances."
                    :href="route('isp.invoices.index', ['status' => 'overdue'])"
                    action="Open invoices"
                    severity="danger"
                >
                    <x-slot:icon>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                    </x-slot:icon>
                </x-dashboard.alert-card>
            @endif

            @if(($stats['suspended_customers'] ?? 0) > 0)
                <x-dashboard.alert-card
                    title="{{ ($stats['suspended_customers'] ?? 0) }} customer(s) are suspended"
                    message="Review outstanding balances and account notes to restore eligible customers quickly."
                    :href="route('isp.customers.index', ['status' => 'suspended'])"
                    action="Review customers"
                    severity="warning"
                >
                    <x-slot:icon>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </x-slot:icon>
                </x-dashboard.alert-card>
            @endif

            @if(($stats['overdue_invoices'] ?? 0) === 0 && ($stats['suspended_customers'] ?? 0) === 0)
                <article class="rounded-xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-900/20 p-4">
                    <h3 class="text-sm font-semibold text-emerald-700 dark:text-emerald-200">No critical alerts</h3>
                    <p class="text-xs text-emerald-700/90 dark:text-emerald-200/90 mt-1">Operations are stable right now.</p>
                </article>
            @endif
        </div>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-dashboard.kpi-card title="Total Customers" :value="number_format($stats['total_customers'] ?? 0)" :subtext="number_format($stats['active_customers'] ?? 0).' active'" :comparison="isset($customerChange) ? (($customerChange >= 0 ? 'Up ' : 'Down ').number_format(abs($customerChange), 1).'%') : null" :trend="($customerChange ?? 0) >= 0 ? 'up' : 'down'" badge="Customer base" iconBg="bg-blue-500">
            <x-slot:icon><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Active Customers" :value="number_format($stats['active_customers'] ?? 0)" :subtext="number_format($stats['suspended_customers'] ?? 0).' suspended'" badge="Service quality" iconBg="bg-emerald-500">
            <x-slot:icon><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Total Revenue" :value="'KES '.number_format($stats['total_revenue'] ?? 0, 2)" :subtext="'Monthly: KES '.number_format($stats['monthly_revenue'] ?? 0, 2)" :comparison="isset($revenueChange) ? (($revenueChange >= 0 ? 'Up ' : 'Down ').number_format(abs($revenueChange), 1).'%') : null" :trend="($revenueChange ?? 0) >= 0 ? 'up' : 'down'" badge="Revenue" iconBg="bg-green-500">
            <x-slot:icon><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2m0 0c1.657 0 3 .895 3 2s-1.343 2-3 2m0-8V7m0 1v8m9-4a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Monthly Revenue" :value="'KES '.number_format($stats['monthly_revenue'] ?? 0, 2)" :subtext="'Last month: KES '.number_format($lastMonthRevenue ?? 0, 2)" badge="Cash flow" iconBg="bg-cyan-500">
            <x-slot:icon><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Active Subscriptions" :value="number_format($stats['active_subscriptions'] ?? 0)" :subtext="number_format($stats['expiring_soon'] ?? 0).' expiring soon'" badge="Subscriber health" iconBg="bg-amber-500">
            <x-slot:icon><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Active Sessions" :value="number_format($stats['active_sessions'] ?? 0)" subtext="Currently online" badge="Network load" iconBg="bg-violet-500">
            <x-slot:icon><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Overdue Invoices" :value="number_format($stats['overdue_invoices'] ?? 0)" :subtext="number_format($stats['pending_invoices'] ?? 0).' pending'" badge="Collections" iconBg="bg-rose-500">
            <x-slot:icon><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Suspended Customers" :value="number_format($stats['suspended_customers'] ?? 0)" subtext="Requires action" badge="Risk indicator" iconBg="bg-orange-500">
            <x-slot:icon><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        <article class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Revenue Summary</h3>
            <div class="mt-4 space-y-2 text-sm">
                <p class="flex justify-between"><span class="text-gray-500">Total Revenue</span><span class="font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($stats['total_revenue'] ?? 0, 2) }}</span></p>
                <p class="flex justify-between"><span class="text-gray-500">This Month</span><span class="font-semibold text-emerald-600 dark:text-emerald-400">KES {{ number_format($stats['monthly_revenue'] ?? 0, 2) }}</span></p>
                <p class="flex justify-between"><span class="text-gray-500">Last Month</span><span class="font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($lastMonthRevenue ?? 0, 2) }}</span></p>
            </div>
        </article>
        <article class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Network / Router Status</h3>
            <div class="mt-4 space-y-2 text-sm">
                <p class="flex justify-between"><span class="text-gray-500">Total Routers</span><span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_routers'] ?? 0) }}</span></p>
                <p class="flex justify-between"><span class="text-gray-500">Active Routers</span><span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['active_routers'] ?? 0) }}</span></p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Router uptime and signal quality trend can be added from monitoring integrations.</p>
            </div>
        </article>
        <article class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Quick Actions</h3>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <a href="{{ route('isp.customers.create') }}" class="text-center text-xs font-medium px-3 py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100">Add Customer</a>
                <a href="{{ route('isp.subscriptions.create') }}" class="text-center text-xs font-medium px-3 py-2 rounded-lg bg-violet-50 text-violet-700 hover:bg-violet-100">New Subscription</a>
                <a href="{{ route('isp.invoices.index', ['status' => 'overdue']) }}" class="text-center text-xs font-medium px-3 py-2 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100">Overdue Invoices</a>
                <a href="{{ route('isp.routers.index') }}" class="text-center text-xs font-medium px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Router Status</a>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <article class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Recent Payments</h3>
                <a href="{{ route('isp.payments.index') }}" class="text-xs font-medium text-blue-600 hover:underline">View all</a>
            </div>
            <div class="space-y-2">
                @forelse(($recentPayments ?? collect())->take(5) as $payment)
                    <div class="dashboard-list-item">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $payment->customer->full_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $payment->processed_at?->diffForHumans() ?? 'N/A' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">KES {{ number_format($payment->amount ?? 0, 2) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ strtoupper($payment->payment_method ?? 'N/A') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No recent payments available.</p>
                @endforelse
            </div>
        </article>

        <article class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Recent Customers</h3>
                <a href="{{ route('isp.customers.index') }}" class="text-xs font-medium text-blue-600 hover:underline">View all</a>
            </div>
            <div class="space-y-2">
                @forelse(($recentCustomers ?? collect())->take(5) as $customer)
                    <div class="dashboard-list-item">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $customer->full_name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $customer->created_at?->diffForHumans() ?? 'N/A' }}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ ($customer->status ?? '') === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                            {{ ucfirst($customer->status ?? 'unknown') }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No recent customers available.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
