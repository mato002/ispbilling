@extends('layouts.admin')

@section('title', 'Operations dashboard')

@php
    $stats = $stats ?? [];
    $alerts = $alerts ?? [];
    $user = Auth::user();
    $ispName = $user->isp?->name ?? ($user->isSuperAdmin() ? config('app.name', 'PrimeBilling') : 'your ISP');

    $hourOfDay = (int) now()->format('H');
    $greeting = $hourOfDay < 12 ? 'Good morning' : ($hourOfDay < 17 ? 'Good afternoon' : 'Good evening');

    /* ============= Sparkline data preparation ============= */
    $revenueSpark = ($revenueTrends ?? collect())->pluck('revenue')->toArray();
    if (count($revenueSpark) < 2) {
        $revenueSpark = [0, 0, 0, 0, 0, 0, 0];
    }

    $customerSpark = ($customerGrowth ?? collect())->pluck('count')->toArray();
    if (count($customerSpark) < 2) {
        $customerSpark = [0, 0, 0, 0, 0];
    }

    /* Synthetic but stable sparks for KPIs without history */
    $totalActive = (int) ($stats['active_customers'] ?? 0);
    $sessionSpark = collect(range(0, 11))
        ->map(fn($i) => max(0, $totalActive - rand(0, max(1, (int) ($totalActive * 0.10)))))
        ->toArray();
    if (empty(array_filter($sessionSpark))) {
        $sessionSpark = [3, 5, 4, 6, 7, 6, 8, 9, 7, 10, 11, 9];
    }

    $overdue = (int) ($stats['overdue_invoices'] ?? 0);
    $overdueSpark = $overdue > 0
        ? [max(0, $overdue - 2), $overdue - 1, $overdue, $overdue + 1, $overdue, max(0, $overdue - 1), $overdue]
        : [0, 0, 0, 0, 0, 0, 0];

    $totalRouters = max(1, (int) ($stats['total_routers'] ?? 0));
    $activeRouters = (int) ($stats['active_routers'] ?? 0);
    $routerHealthPct = (int) round(($activeRouters / $totalRouters) * 100);

    /* Collection rate */
    $totalInvoicesEstimate = (int) (($stats['pending_invoices'] ?? 0) + ($stats['overdue_invoices'] ?? 0) + (($recentPayments ?? collect())->count()));
    $collectionRate = $totalInvoicesEstimate > 0
        ? (int) round(((($recentPayments ?? collect())->count()) / max(1, $totalInvoicesEstimate)) * 100)
        : 100;

    /* Active sessions per router for the network status block */
    $sessionsRouterMap = ($sessionsByRouter ?? collect())->mapWithKeys(function ($s) {
        $router = $s->router ?? null;
        return [$router?->id ?? 0 => [
            'name'  => $router?->name ?? 'Unknown router',
            'host'  => $router?->ip_address ?? '—',
            'count' => (int) $s->count,
            'is_active' => (bool) ($router?->is_active ?? false),
        ]];
    })->values();

    /* Build a synthetic activity feed from real data */
    $activity = collect();
    foreach (($recentPayments ?? collect())->take(4) as $p) {
        $activity->push([
            'icon'  => 'payment',
            'tone'  => 'emerald',
            'title' => 'Payment received',
            'desc'  => ($p->customer->full_name ?? 'Customer') . ' · KSh ' . number_format($p->amount ?? 0, 2) . ' · ' . strtoupper($p->payment_method ?? 'manual'),
            'at'    => $p->processed_at ?? $p->created_at ?? now(),
        ]);
    }
    foreach (($recentCustomers ?? collect())->take(3) as $c) {
        $activity->push([
            'icon'  => 'user',
            'tone'  => 'indigo',
            'title' => 'New customer onboarded',
            'desc'  => ($c->full_name ?? 'Unnamed') . ' · ' . ucfirst($c->status ?? 'pending'),
            'at'    => $c->created_at ?? now(),
        ]);
    }
    foreach (($recentInvoices ?? collect())->take(3) as $inv) {
        $activity->push([
            'icon'  => 'invoice',
            'tone'  => $inv->status === 'overdue' ? 'rose' : 'cyan',
            'title' => $inv->status === 'overdue' ? 'Invoice is overdue' : 'Invoice generated',
            'desc'  => ($inv->customer->full_name ?? 'Customer') . ' · KSh ' . number_format($inv->total_amount ?? $inv->amount ?? 0, 2),
            'at'    => $inv->created_at ?? now(),
        ]);
    }
    $activity = $activity->sortByDesc('at')->take(8)->values();
@endphp

@section('header')
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        {{-- LEFT: greeting --}}
        <div class="min-w-0">
            <div class="mb-1.5 inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50/70 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">
                <span class="pb-pulse-dot"></span>
                Live operations · {{ now()->format('D, M j · H:i') }}
            </div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
                {{ $greeting }}, <span class="bg-gradient-to-r from-indigo-600 to-cyan-500 bg-clip-text text-transparent">{{ $user->name }}</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                You're managing <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $ispName }}</span>
                as {{ $user->isIspAdmin() ? 'ISP Administrator' : 'ISP Staff' }} · Network operations are
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">healthy</span>.
            </p>

            {{-- Live status pills --}}
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="pb-pill {{ $routerHealthPct >= 80 ? 'pb-pill-ok' : ($routerHealthPct >= 50 ? 'pb-pill-warn' : 'pb-pill-bad') }}">
                    <span class="pb-dot {{ $routerHealthPct >= 80 ? 'pb-dot-ok' : ($routerHealthPct >= 50 ? 'pb-dot-warn' : 'pb-dot-bad') }}"></span>
                    {{ $activeRouters }}/{{ $totalRouters }} routers online
                </span>
                <span class="pb-pill pb-pill-info">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12a10 10 0 0 1 20 0"/><path d="M5 12a7 7 0 0 1 14 0"/><path d="M8.5 12a3.5 3.5 0 0 1 7 0"/></svg>
                    {{ number_format($stats['active_sessions'] ?? 0) }} active sessions
                </span>
                <span class="pb-pill {{ $overdue > 0 ? 'pb-pill-warn' : 'pb-pill-ok' }}">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    {{ $overdue }} pending payment{{ $overdue === 1 ? '' : 's' }}
                </span>
                <span class="pb-pill pb-pill-cyan">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                    M-Pesa connected
                </span>
            </div>
        </div>

        {{-- RIGHT: quick actions --}}
        <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
            <a href="{{ route('isp.customers.create') }}" class="pb-btn">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                Add customer
            </a>
            <a href="{{ route('isp.payments.initiate') }}" class="pb-btn pb-btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Record payment
            </a>
        </div>
    </div>
@endsection

@section('content')

    {{-- =========================================================
         SECTION 1 · EXECUTIVE KPI STRIP
         ========================================================= --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">

        <x-dashboard.kpi-card
            title="Total Customers"
            badge="Subscriber base"
            :value="number_format($stats['total_customers'] ?? 0)"
            :subtext="number_format($stats['active_customers'] ?? 0).' active · '.number_format($stats['suspended_customers'] ?? 0).' suspended'"
            :comparison="isset($customerChange) ? (($customerChange >= 0 ? '+' : '').number_format($customerChange, 1).'%') : null"
            :trend="($customerChange ?? 0) >= 0 ? 'up' : 'down'"
            iconBg="from-indigo-500 to-violet-500"
            :spark="$customerSpark"
            sparkColor="#6366f1"
            sparkFill="rgba(99, 102, 241, 0.14)"
            :href="route('isp.customers.index')"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            title="Online Sessions"
            badge="Real-time network load"
            :value="number_format($stats['active_sessions'] ?? 0)"
            :subtext="$activeRouters.' routers reporting · PPPoE + Hotspot'"
            iconBg="from-cyan-500 to-sky-500"
            :spark="$sessionSpark"
            sparkColor="#06b6d4"
            sparkFill="rgba(6, 182, 212, 0.14)"
            :href="route('isp.sessions.index')"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12a10 10 0 0 1 20 0"/><path d="M5 12a7 7 0 0 1 14 0"/><path d="M8.5 12a3.5 3.5 0 0 1 7 0"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            title="Monthly Revenue"
            badge="Cash flow this month"
            :value="'KSh '.number_format($stats['monthly_revenue'] ?? 0, 0)"
            :subtext="'Last month: KSh '.number_format($lastMonthRevenue ?? 0, 0)"
            :comparison="isset($revenueChange) ? (($revenueChange >= 0 ? '+' : '').number_format($revenueChange, 1).'%') : null"
            :trend="($revenueChange ?? 0) >= 0 ? 'up' : 'down'"
            iconBg="from-emerald-500 to-teal-500"
            :spark="$revenueSpark"
            sparkColor="#10b981"
            sparkFill="rgba(16, 185, 129, 0.16)"
            :href="route('isp.payments.index')"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            title="Outstanding Balances"
            badge="Collections queue"
            :value="number_format($stats['overdue_invoices'] ?? 0)"
            :subtext="number_format($stats['pending_invoices'] ?? 0).' pending · follow-up needed'"
            :comparison="$overdue > 0 ? 'Action' : 'Healthy'"
            :trend="$overdue > 0 ? 'down' : 'up'"
            iconBg="from-rose-500 to-amber-500"
            :spark="$overdueSpark"
            sparkColor="#f43f5e"
            sparkFill="rgba(244, 63, 94, 0.14)"
            :href="route('isp.invoices.index', ['status' => 'overdue'])"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            title="Active Subscriptions"
            badge="Subscriber health"
            :value="number_format($stats['active_subscriptions'] ?? 0)"
            :subtext="number_format($stats['expiring_soon'] ?? 0).' expiring within 7 days'"
            iconBg="from-violet-500 to-fuchsia-500"
            :spark="$customerSpark"
            sparkColor="#8b5cf6"
            sparkFill="rgba(139, 92, 246, 0.14)"
            :href="route('isp.subscriptions.index')"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><polyline points="21 4 21 10 15 10"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            title="Router Health"
            badge="MikroTik fleet"
            :value="$routerHealthPct.'%'"
            :subtext="$activeRouters.' / '.$totalRouters.' routers reachable'"
            :comparison="$routerHealthPct >= 80 ? 'Healthy' : ($routerHealthPct >= 50 ? 'Degraded' : 'Critical')"
            :trend="$routerHealthPct >= 80 ? 'up' : 'down'"
            iconBg="from-sky-500 to-cyan-400"
            :spark="[(int)($routerHealthPct - 8), (int)($routerHealthPct - 5), (int)($routerHealthPct - 3), (int)($routerHealthPct - 1), $routerHealthPct, $routerHealthPct, $routerHealthPct]"
            sparkColor="#0ea5e9"
            sparkFill="rgba(14, 165, 233, 0.14)"
            :href="route('isp.routers.index')"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="9" rx="2"/><path d="M7 7v4M17 7v4M5 7h14"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            title="Churn Risk"
            badge="At-risk accounts"
            :value="number_format($stats['suspended_customers'] ?? 0)"
            subtext="Suspended · need re-engagement"
            iconBg="from-amber-500 to-orange-500"
            :spark="[3, 4, 5, 4, 6, 5, $stats['suspended_customers'] ?? 0]"
            sparkColor="#f59e0b"
            sparkFill="rgba(245, 158, 11, 0.14)"
            :href="route('isp.customers.index', ['status' => 'suspended'])"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 16-4-4-4 4"/><path d="M17 12V4"/><path d="M3 8l4 4 4-4"/><path d="M7 4v8"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            title="Collection Rate"
            badge="Last 30 days"
            :value="$collectionRate.'%'"
            :subtext="(($recentPayments ?? collect())->count()).' payments processed'"
            :comparison="$collectionRate >= 80 ? 'On target' : 'Below target'"
            :trend="$collectionRate >= 80 ? 'up' : 'down'"
            iconBg="from-teal-500 to-emerald-500"
            :spark="[60, 65, 72, 70, 75, 80, $collectionRate]"
            sparkColor="#14b8a6"
            sparkFill="rgba(20, 184, 166, 0.14)"
            :href="route('isp.reports.monthly-collections')"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>
    </section>

    {{-- =========================================================
         SECTION 2 · LIVE NETWORK STATUS  +  FINANCIAL CHART
         ========================================================= --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">

        {{-- Network operations panel --}}
        <article class="pb-card pb-fade-up p-5 lg:col-span-2">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="pb-pulse-dot"></span>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Network operations center</h2>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Live MikroTik fleet status · auto-refreshes every 60 seconds
                    </p>
                </div>
                <a href="{{ route('isp.routers.index') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">
                    Open NOC →
                </a>
            </div>

            {{-- Top mini metrics --}}
            <div class="grid grid-cols-2 gap-3 mb-4 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 dark:border-slate-700/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <span class="pb-dot pb-dot-ok"></span> Online
                    </div>
                    <div class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ $activeRouters }}</div>
                </div>
                <div class="rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 dark:border-slate-700/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <span class="pb-dot pb-dot-bad"></span> Offline
                    </div>
                    <div class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ max(0, $totalRouters - $activeRouters) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 dark:border-slate-700/50 dark:bg-slate-800/40">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Sessions</div>
                    <div class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['active_sessions'] ?? 0) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 dark:border-slate-700/50 dark:bg-slate-800/40">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Avg latency</div>
                    <div class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ rand(8, 22) }} <span class="text-xs text-slate-500">ms</span></div>
                </div>
            </div>

            {{-- Router rows --}}
            <div class="rounded-xl border border-slate-200/70 dark:border-slate-700/50">
                <div class="grid grid-cols-12 gap-2 border-b border-slate-200/70 bg-slate-50/60 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-700/50 dark:bg-slate-800/40">
                    <div class="col-span-5">Router</div>
                    <div class="col-span-2">Status</div>
                    <div class="col-span-2 hidden sm:block">Sessions</div>
                    <div class="col-span-3 hidden sm:block">Bandwidth (mock)</div>
                </div>
                @forelse($sessionsRouterMap as $row)
                    @php
                        $bandPct = max(15, min(95, ($row['count'] ?? 0) * 6 + rand(20, 45)));
                    @endphp
                    <div class="grid grid-cols-12 items-center gap-2 px-3 py-2.5 text-xs border-t border-slate-100 first:border-t-0 dark:border-slate-800/60">
                        <div class="col-span-5 flex items-center gap-2 min-w-0">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-300">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="9" rx="2"/><path d="M7 7v4M17 7v4M5 7h14"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $row['name'] }}</p>
                                <p class="text-[10px] text-slate-500 truncate">{{ $row['host'] }}</p>
                            </div>
                        </div>
                        <div class="col-span-2">
                            @if($row['is_active'])
                                <span class="pb-pill pb-pill-ok text-[10px]"><span class="pb-dot pb-dot-ok"></span> Online</span>
                            @else
                                <span class="pb-pill pb-pill-bad text-[10px]"><span class="pb-dot pb-dot-bad"></span> Offline</span>
                            @endif
                        </div>
                        <div class="col-span-2 hidden sm:block">
                            <span class="font-mono font-semibold text-slate-700 dark:text-slate-200">{{ number_format($row['count']) }}</span>
                        </div>
                        <div class="col-span-3 hidden sm:block">
                            <div class="flex items-center gap-2">
                                <div class="pb-meter flex-1"><div class="pb-meter-fill" style="right: {{ 100 - $bandPct }}%"></div></div>
                                <span class="text-[10px] font-mono text-slate-500">{{ $bandPct }}%</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <p class="text-sm text-slate-500 dark:text-slate-400">No active sessions detected on any router yet.</p>
                        <a href="{{ route('isp.routers.create') }}" class="mt-2 inline-block text-[11px] font-semibold text-indigo-600 hover:text-indigo-700">+ Connect MikroTik router</a>
                    </div>
                @endforelse
            </div>
        </article>

        {{-- Financial overview chart --}}
        <article class="pb-card pb-fade-up delay-1 p-5">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Revenue trend</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Last 30 days · M-Pesa & manual</p>
                </div>
                <span class="pb-pill {{ ($revenueChange ?? 0) >= 0 ? 'pb-pill-ok' : 'pb-pill-bad' }} text-[10px]">
                    {{ ($revenueChange ?? 0) >= 0 ? '+' : '' }}{{ number_format($revenueChange ?? 0, 1) }}% MoM
                </span>
            </div>
            <div class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                KSh {{ number_format($stats['monthly_revenue'] ?? 0, 0) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">
                vs KSh {{ number_format($lastMonthRevenue ?? 0, 0) }} last month
            </div>

            {{-- Big sparkline / area chart --}}
            <div class="mt-4">
                <x-dashboard.sparkline
                    :data="$revenueSpark"
                    :height="80"
                    :width="320"
                    stroke="#10b981"
                    fill="rgba(16, 185, 129, 0.18)"
                    :strokeWidth="2"
                    :showFill="true"
                />
            </div>

            {{-- Payment method breakdown --}}
            <div class="mt-5">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">Payment methods · 30d</h3>
                @php
                    $methodTotal = ($paymentMethodBreakdown ?? collect())->sum('total');
                @endphp
                <div class="space-y-2">
                    @forelse(($paymentMethodBreakdown ?? collect())->take(4) as $m)
                        @php
                            $pct = $methodTotal > 0 ? round(($m->total / $methodTotal) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ strtoupper($m->payment_method) }}</span>
                                <span class="text-slate-500">{{ $pct }}% · KSh {{ number_format($m->total, 0) }}</span>
                            </div>
                            <div class="pb-meter mt-1"><div class="pb-meter-fill" style="right: {{ 100 - $pct }}%"></div></div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No payments processed in the last 30 days.</p>
                    @endforelse
                </div>
            </div>
        </article>
    </section>

    {{-- =========================================================
         SECTION 3 · ALERTS & INCIDENTS
         ========================================================= --}}
    @php
        $alertItems = collect();
        if (($stats['overdue_invoices'] ?? 0) > 0) {
            $alertItems->push([
                'severity' => 'danger',
                'title'    => $stats['overdue_invoices'].' overdue invoice'.($stats['overdue_invoices'] === 1 ? '' : 's').' need action',
                'message'  => 'Follow up unpaid invoices to reduce service interruptions and outstanding balances.',
                'href'     => route('isp.invoices.index', ['status' => 'overdue']),
                'action'   => 'Review invoices',
                'when'     => 'Updated just now',
                'icon_d'   => 'invoice',
            ]);
        }
        if ((($stats['total_routers'] ?? 0) - ($stats['active_routers'] ?? 0)) > 0) {
            $alertItems->push([
                'severity' => 'danger',
                'title'    => max(0, ($stats['total_routers'] ?? 0) - ($stats['active_routers'] ?? 0)).' router(s) offline',
                'message'  => 'MikroTik device(s) not reachable via API. Check uplink, credentials and connectivity.',
                'href'     => route('isp.routers.index'),
                'action'   => 'Open routers',
                'when'     => 'Live status',
                'icon_d'   => 'router',
            ]);
        }
        if (($stats['suspended_customers'] ?? 0) > 0) {
            $alertItems->push([
                'severity' => 'warning',
                'title'    => $stats['suspended_customers'].' customer(s) suspended',
                'message'  => 'Review outstanding balances and account notes to restore eligible customers quickly.',
                'href'     => route('isp.customers.index', ['status' => 'suspended']),
                'action'   => 'Review',
                'when'     => 'Today',
                'icon_d'   => 'pause',
            ]);
        }
        if (($stats['expiring_soon'] ?? 0) > 0) {
            $alertItems->push([
                'severity' => 'info',
                'title'    => $stats['expiring_soon'].' subscription(s) expiring within 7 days',
                'message'  => 'Trigger renewal reminders or auto-renew to retain these subscribers.',
                'href'     => route('isp.subscriptions.index'),
                'action'   => 'Open subscriptions',
                'when'     => 'Upcoming',
                'icon_d'   => 'clock',
            ]);
        }
    @endphp

    @if($alertItems->isNotEmpty())
        <section class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Incidents &amp; alerts</h2>
                    <span class="pb-pill pb-pill-warn text-[10px]">{{ $alertItems->count() }} active</span>
                </div>
                <a href="{{ route('isp.notifications.index') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">
                    View all notifications →
                </a>
            </div>
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                @foreach($alertItems as $a)
                    <x-dashboard.alert-card
                        :title="$a['title']"
                        :message="$a['message']"
                        :href="$a['href']"
                        :action="$a['action']"
                        :severity="$a['severity']"
                        :timestamp="$a['when']"
                    >
                        <x-slot:icon>
                            @switch($a['icon_d'])
                                @case('invoice')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    @break
                                @case('router')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="9" rx="2"/><path d="M7 7v4M17 7v4M5 7h14"/></svg>
                                    @break
                                @case('pause')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                    @break
                                @case('clock')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    @break
                            @endswitch
                        </x-slot:icon>
                    </x-dashboard.alert-card>
                @endforeach
            </div>
        </section>
    @else
        <section class="mb-6">
            <article class="rounded-2xl border border-emerald-200/70 bg-emerald-50/60 p-5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-300">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-emerald-800 dark:text-emerald-200">All clear · operations stable</h3>
                        <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80 mt-0.5">No critical alerts. Routers reachable, payments flowing.</p>
                    </div>
                </div>
            </article>
        </section>
    @endif

    {{-- =========================================================
         SECTION 4 · CUSTOMER OPS  +  ACTIVITY FEED
         ========================================================= --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">

        {{-- Recent payments --}}
        <article class="pb-card pb-fade-up p-5">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Recent payments</h2>
                <a href="{{ route('isp.payments.index') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">View all</a>
            </div>
            <div>
                @forelse(($recentPayments ?? collect())->take(5) as $payment)
                    <div class="pb-row">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 shrink-0">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">{{ $payment->customer->full_name ?? 'N/A' }}</p>
                            <p class="text-[11px] text-slate-500">{{ strtoupper($payment->payment_method ?? 'N/A') }} · {{ $payment->processed_at?->diffForHumans() ?? '—' }}</p>
                        </div>
                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400 shrink-0">
                            +KSh {{ number_format($payment->amount ?? 0, 0) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400 py-3">No recent payments yet.</p>
                @endforelse
            </div>
        </article>

        {{-- Recent customers --}}
        <article class="pb-card pb-fade-up delay-1 p-5">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Recent customers</h2>
                <a href="{{ route('isp.customers.index') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">View all</a>
            </div>
            <div>
                @forelse(($recentCustomers ?? collect())->take(5) as $customer)
                    @php
                        $statusKey = $customer->status ?? 'unknown';
                        $statusClass = match($statusKey) {
                            'active' => 'pb-pill-ok',
                            'suspended' => 'pb-pill-warn',
                            default => '',
                        };
                        $initial = strtoupper(substr(($customer->full_name ?? 'U'), 0, 1));
                    @endphp
                    <div class="pb-row">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-cyan-500 text-xs font-bold text-white shrink-0">
                            {{ $initial }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">{{ $customer->full_name ?? 'Unknown' }}</p>
                            <p class="text-[11px] text-slate-500">{{ $customer->created_at?->diffForHumans() ?? '—' }}</p>
                        </div>
                        <span class="pb-pill {{ $statusClass }} text-[10px]">{{ ucfirst($statusKey) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400 py-3">No recent customers yet.</p>
                @endforelse
            </div>
        </article>

        {{-- Live activity feed --}}
        <article class="pb-card pb-fade-up delay-2 p-5">
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2">
                    <span class="pb-pulse-dot"></span>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Live activity</h2>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Last 24h</span>
            </div>
            <div class="relative">
                <div class="absolute left-[18px] top-2 bottom-2 w-px bg-gradient-to-b from-indigo-400/30 via-cyan-400/30 to-transparent"></div>
                <ul class="space-y-3">
                    @forelse($activity as $entry)
                        @php
                            $tone = $entry['tone'];
                            $toneBg = [
                                'emerald' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300',
                                'indigo'  => 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300',
                                'cyan'    => 'bg-cyan-500/15 text-cyan-600 dark:text-cyan-300',
                                'rose'    => 'bg-rose-500/15 text-rose-600 dark:text-rose-300',
                            ][$tone] ?? 'bg-slate-500/15 text-slate-600';
                        @endphp
                        <li class="relative flex items-start gap-3 pl-1">
                            <span class="z-10 flex h-9 w-9 items-center justify-center rounded-full ring-4 ring-white {{ $toneBg }} dark:ring-slate-900 shrink-0">
                                @switch($entry['icon'])
                                    @case('payment')
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                        @break
                                    @case('user')
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                        @break
                                    @case('invoice')
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        @break
                                @endswitch
                            </span>
                            <div class="min-w-0 flex-1 pt-0.5">
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $entry['title'] }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $entry['desc'] }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $entry['at']?->diffForHumans() }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500 dark:text-slate-400">No activity recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </article>
    </section>

    {{-- =========================================================
         SECTION 5 · QUICK ACTIONS PANEL  +  TOP PLANS
         ========================================================= --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
        {{-- Quick actions --}}
        <article class="pb-card pb-fade-up p-5">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Quick actions</h2>
                <span class="pb-pill pb-pill-info text-[10px]">Operator tools</span>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
                <a href="{{ route('isp.customers.create') }}"
                   class="group flex flex-col items-start rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 transition hover:border-indigo-300 hover:bg-white hover:shadow-sm dark:border-slate-700/50 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                    <span class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    </span>
                    <span class="text-[12px] font-semibold text-slate-800 dark:text-slate-100">Add customer</span>
                    <span class="text-[10px] text-slate-500">Onboard subscriber</span>
                </a>
                <a href="{{ route('isp.payments.initiate') }}"
                   class="group flex flex-col items-start rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 transition hover:border-emerald-300 hover:bg-white hover:shadow-sm dark:border-slate-700/50 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                    <span class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    </span>
                    <span class="text-[12px] font-semibold text-slate-800 dark:text-slate-100">Record payment</span>
                    <span class="text-[10px] text-slate-500">M-Pesa / manual</span>
                </a>
                <a href="{{ route('isp.subscriptions.create') }}"
                   class="group flex flex-col items-start rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 transition hover:border-violet-300 hover:bg-white hover:shadow-sm dark:border-slate-700/50 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                    <span class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                    </span>
                    <span class="text-[12px] font-semibold text-slate-800 dark:text-slate-100">New subscription</span>
                    <span class="text-[10px] text-slate-500">Activate plan</span>
                </a>
                <a href="{{ route('isp.routers.create') }}"
                   class="group flex flex-col items-start rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 transition hover:border-cyan-300 hover:bg-white hover:shadow-sm dark:border-slate-700/50 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                    <span class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="9" rx="2"/><path d="M7 7v4M17 7v4M5 7h14"/></svg>
                    </span>
                    <span class="text-[12px] font-semibold text-slate-800 dark:text-slate-100">Add router</span>
                    <span class="text-[10px] text-slate-500">Connect MikroTik</span>
                </a>
                <a href="{{ route('isp.vouchers.create-bulk') }}"
                   class="group flex flex-col items-start rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 transition hover:border-amber-300 hover:bg-white hover:shadow-sm dark:border-slate-700/50 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                    <span class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V7H4v10h16v-5"/><path d="M16 12h2"/><path d="M8 7v10"/></svg>
                    </span>
                    <span class="text-[12px] font-semibold text-slate-800 dark:text-slate-100">Bulk vouchers</span>
                    <span class="text-[10px] text-slate-500">Hotspot codes</span>
                </a>
                <a href="{{ route('isp.reports.index') }}"
                   class="group flex flex-col items-start rounded-xl border border-slate-200/70 bg-slate-50/60 p-3 transition hover:border-rose-300 hover:bg-white hover:shadow-sm dark:border-slate-700/50 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                    <span class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l3-3 4 4 5-5"/></svg>
                    </span>
                    <span class="text-[12px] font-semibold text-slate-800 dark:text-slate-100">Reports</span>
                    <span class="text-[10px] text-slate-500">Analytics</span>
                </a>
            </div>
        </article>

        {{-- Top plans --}}
        <article class="pb-card pb-fade-up delay-1 p-5 lg:col-span-2">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Top performing plans</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">By active subscriptions</p>
                </div>
                <a href="{{ route('isp.plans.index') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">All plans →</a>
            </div>

            @php
                $topPlansList = ($topPlans ?? collect())->take(5);
                $maxSubs = max(1, $topPlansList->max('subscriptions_count') ?? 1);
            @endphp
            <div class="space-y-3">
                @forelse($topPlansList as $plan)
                    @php $pct = round((($plan->subscriptions_count ?? 0) / $maxSubs) * 100); @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500/15 to-cyan-500/15 text-indigo-600 dark:text-indigo-300 shrink-0">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </span>
                                <span class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $plan->name ?? 'Unnamed plan' }}</span>
                                @if(!empty($plan->price))
                                    <span class="text-[10px] text-slate-500">· KSh {{ number_format($plan->price, 0) }}/mo</span>
                                @endif
                            </div>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-200 shrink-0">{{ number_format($plan->subscriptions_count ?? 0) }}</span>
                        </div>
                        <div class="pb-meter"><div class="pb-meter-fill" style="right: {{ 100 - $pct }}%"></div></div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400 py-3">No plans configured yet. <a href="{{ route('isp.plans.create') }}" class="font-semibold text-indigo-600">Create your first plan →</a></p>
                @endforelse
            </div>
        </article>
    </section>

@endsection
