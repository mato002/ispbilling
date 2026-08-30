<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400">Platform command</p>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">
                    {{ __('Super Admin NOC') }}
                </h2>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <span class="pb-noc-dot pb-noc-dot--healthy" aria-hidden="true"></span>
                    Fleet poll ~1m
                </span>
                <a href="{{ route('admin.audit.activity') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Global audit →</a>
            </div>
        </div>
    </x-slot>

    {{-- Primary KPI strip --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Total ISPs</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ number_format($stats['total_isps']) }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ number_format($stats['active_isps']) }} active tenants</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Subscribers</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ number_format($stats['total_customers']) }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ number_format($stats['active_customers']) }} active</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Gross collections</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white">KES {{ number_format($stats['total_revenue'], 0) }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">All completed payments</p>
        </div>
        <div class="rounded-2xl border border-rose-200/70 bg-gradient-to-br from-rose-50/80 to-white p-5 shadow-sm dark:border-rose-500/20 dark:from-rose-950/30 dark:to-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-rose-600 dark:text-rose-400">Invoice pressure</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums text-rose-700 dark:text-rose-300">{{ number_format($stats['pending_invoices']) }}</p>
            <p class="mt-1 text-xs text-rose-600/80 dark:text-rose-300/80">{{ number_format($stats['overdue_invoices']) }} customer invoices overdue</p>
        </div>
    </div>

    {{-- NOC grid: fleet + tenants (2/3) | audit feed + alerts (1/3) --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.dashboard.fleet-status :totals="$fleetTotals" :tenants="$fleetTenants" />
            <x-admin.dashboard.tenant-list :tenants="$recentIsps" />
        </div>

        <div class="space-y-6 lg:sticky lg:top-20 lg:self-start">
            <x-admin.dashboard.activity-feed :events="$activityFeed" />
            <x-admin.dashboard.critical-alerts :alerts="$criticalAlerts" />
        </div>
    </div>

    {{-- Quick navigation --}}
    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('admin.isps.index') }}" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/30">
            <p class="text-sm font-bold text-slate-900 dark:text-white">Manage ISPs</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Tenant roster, SaaS plans, lock controls</p>
        </a>
        <a href="{{ route('admin.finance.index') }}" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/30">
            <p class="text-sm font-bold text-slate-900 dark:text-white">Platform finance</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">SaaS billing calendar & commission ledger</p>
        </a>
        <a href="{{ route('admin.network.index') }}" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/30">
            <p class="text-sm font-bold text-slate-900 dark:text-white">Network operations</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cross-tenant router telemetry & maintenance</p>
        </a>
    </div>
</x-admin-layout>
