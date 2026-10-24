@extends('layouts.admin')

@section('title', 'Subscriber operations')
@section('compact_header', true)

@php
    $stats = $stats ?? [];
@endphp

@section('header')
@endsection

@section('content')
<div
    class="space-y-3"
    x-data="{
        drawerOpen: false,
        selected: null,
        quickOpen: false,
        viewCompact: localStorage.getItem('pb.customers.compact') === '1',
        autoRefresh: localStorage.getItem('pb.customers.autorefresh') === '1',
        toggleCompact() {
            this.viewCompact = !this.viewCompact;
            localStorage.setItem('pb.customers.compact', this.viewCompact ? '1' : '0');
        },
        toggleAuto() {
            this.autoRefresh = !this.autoRefresh;
            localStorage.setItem('pb.customers.autorefresh', this.autoRefresh ? '1' : '0');
        },
        openDrawer(payload) { this.selected = payload; this.drawerOpen = true; document.body.classList.add('overflow-hidden'); },
        closeDrawer() { this.drawerOpen = false; this.selected = null; document.body.classList.remove('overflow-hidden'); },
    }"
    x-init="if (autoRefresh) { setInterval(() => { if (!document.hidden) window.location.reload(); }, 120000); }"
    @keydown.escape.window="closeDrawer(); quickOpen = false"
>
    {{-- ========== OPERATIONAL TOOLBAR ========== --}}
    <div class="pb-card relative overflow-visible p-3 sm:p-4">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-[inherit]">
            <div class="absolute -right-20 -top-20 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-500/15 to-cyan-400/10 blur-2xl"></div>
        </div>
        <form
            method="GET"
            action="{{ route('isp.customers.index') }}"
            id="customer-search-form"
            class="relative flex flex-col gap-2 lg:flex-row lg:items-center"
            data-turbo-frame="pb-main"
            x-data="pbFilterForm({ autoSearch: true })"
        >
            @foreach(request()->except(['search','status','page','export']) as $k => $v)
                @if(is_array($v))
                    @foreach($v as $vv)
                        <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                    @endforeach
                @elseif($v !== null && $v !== '')
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach

            <div class="customer-toolbar-primary flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                <h1 class="shrink-0 text-lg font-bold tracking-tight text-slate-900 dark:text-white">Customers</h1>
                <div class="customer-search-box relative min-w-0 flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search name, phone, email, username, router, invoice #, IP, MAC…"
                        class="w-full rounded-xl border border-slate-200 bg-white/90 py-2 pl-10 pr-3 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100"
                    >
                </div>
                <select name="status" aria-label="Quick status filter" class="rounded-xl border border-slate-200 bg-white px-2 py-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">All status</option>
                    @foreach(['active','suspended','expired','inactive','pending_installation'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st === 'pending_installation' ? 'Pending' : ucfirst($st) }}</option>
                    @endforeach
                </select>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="toggleCompact()" class="pb-btn py-2 text-xs" :title="viewCompact ? 'Comfortable density' : 'Compact density'">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <span x-text="viewCompact ? 'Comfortable' : 'Compact'"></span>
                    </button>
                    <button type="button" @click="toggleAuto()" class="pb-btn py-2 text-xs" :class="autoRefresh ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10' : ''">
                        <span class="flex h-2 w-2 rounded-full" :class="autoRefresh ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"></span>
                        Auto refresh
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-2 dark:border-slate-800 lg:border-0 lg:pt-0">
                <div class="relative">
                    <button type="button" @click="quickOpen = !quickOpen" class="pb-btn py-2 text-xs">
                        Quick actions
                        <svg class="h-3.5 w-3.5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="quickOpen" x-cloak @click.outside="quickOpen = false" x-transition
                         class="absolute right-0 z-40 mt-2 w-56 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                        <a href="{{ route('isp.customers.create') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">New subscriber</a>
                        <a href="{{ route('isp.payments.initiate') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Record payment</a>
                        <a href="{{ route('isp.subscriptions.create') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">New subscription</a>
                        <a href="{{ route('isp.sessions.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Live sessions</a>
                    </div>
                </div>
                <a href="{{ route('isp.customers.create') }}" class="pb-btn-primary py-2 text-xs">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                    Add customer
                </a>
                <x-export-dropdown :formats="$exportFormats ?? []" label="Export" />
            </div>
        </form>
        @if (count($filterChips ?? []) > 0)
            <div class="relative mt-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                <x-filter-chips :chips="$filterChips" />
            </div>
        @endif
    </div>

    {{-- ========== VISIBLE FILTERS ========== --}}
    @php $advancedFilterCount = collect(request()->only(['connection_type','router_id','plan_id','location','payment_status','online_only','expiring_only','created_from','created_to']))->filter(fn ($value) => filled($value))->count(); @endphp
    <details class="pb-card p-3" @if($advancedFilterCount > 0) open @endif>
        <summary class="cursor-pointer text-sm font-semibold text-slate-800 dark:text-slate-100">Filters <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $advancedFilterCount }}</span></summary>
            <form
                method="GET"
                action="{{ route('isp.customers.index') }}"
                class="customer-advanced-filters mt-3"
                data-turbo-frame="pb-main"
                x-data="pbFilterForm({ autoSearch: true })"
            >
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <div class="w-32 shrink-0">
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Connection</label>
                    <select name="connection_type" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">All</option>
                        <option value="hotspot" {{ request('connection_type') === 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                        <option value="pppoe" {{ request('connection_type') === 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                    </select>
                </div>
                <div class="w-36 shrink-0">
                    <x-searchable-select name="router_id" label="Router" :options="$routers->pluck('name', 'id')->all()" placeholder="Any router" :value="request('router_id')" />
                </div>
                <div class="w-36 shrink-0">
                    <x-searchable-select name="plan_id" label="Package" :options="$plans->pluck('name', 'id')->all()" placeholder="Any active plan" :value="request('plan_id')" />
                </div>
                <div class="w-36 shrink-0">
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Location contains</label>
                    <input type="text" name="location" value="{{ request('location') }}" placeholder="Town, estate, site…" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div class="w-36 shrink-0">
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Billing</label>
                    <select name="payment_status" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">Any</option>
                        <option value="overdue" {{ request('payment_status') === 'overdue' ? 'selected' : '' }}>Has overdue invoice</option>
                        <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Has pending invoice</option>
                        <option value="clear" {{ request('payment_status') === 'clear' ? 'selected' : '' }}>No pending/overdue</option>
                    </select>
                </div>
                <div class="customer-filter-toggles flex shrink-0 flex-col justify-center gap-1 rounded-xl border border-slate-100 bg-slate-50/80 px-2.5 py-2 dark:border-slate-800 dark:bg-slate-800/40">
                    <label class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-medium text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="online_only" value="1" {{ request('online_only') ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800">
                        Online now (active session)
                    </label>
                    <label class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-medium text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="expiring_only" value="1" {{ request('expiring_only') ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800">
                        Subscription expiring ≤ 7 days
                    </label>
                </div>
                <div class="customer-filter-dates grid w-[264px] shrink-0 grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Created from</label>
                        <input type="date" name="created_from" value="{{ request('created_from') }}" class="w-full rounded-xl border border-slate-200 bg-white py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Created to</label>
                        <input type="date" name="created_to" value="{{ request('created_to') }}" class="w-full rounded-xl border border-slate-200 bg-white py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                </div>
                <div class="w-24 shrink-0">
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Per page</label>
                    <select name="per_page" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @foreach ([10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="customer-filter-reset shrink-0">
                    <a href="{{ route('isp.customers.index') }}" data-turbo-frame="pb-main" class="inline-flex h-10 items-center rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Reset filters</a>
                </div>
            </form>
    </details>


    {{-- ========== KPI STRIP ========== --}}
    <details class="pb-card px-3 py-2">
        <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">Show metrics</summary>
        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-8">
        @foreach([
            ['label' => 'Total', 'val' => $stats['total'] ?? 0],
            ['label' => 'Active', 'val' => $stats['active'] ?? 0],
            ['label' => 'Suspended', 'val' => $stats['suspended'] ?? 0],
            ['label' => 'Hotspot', 'val' => $stats['hotspot'] ?? 0],
            ['label' => 'PPPoE', 'val' => $stats['pppoe'] ?? 0],
            ['label' => 'Expiring', 'val' => $stats['expiring_soon'] ?? 0],
            ['label' => 'Unpaid', 'val' => $stats['unpaid'] ?? 0],
            ['label' => 'Online', 'val' => $stats['online_sessions'] ?? 0],
        ] as $kpi)
            <div class="rounded-lg border border-slate-200 px-2 py-1.5 dark:border-slate-700">
                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</span>
                <span class="ml-1 font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($kpi['val']) }}</span>
            </div>
        @endforeach
        </div>
    </details>

    {{-- ========== LIVE ACTIVITY RAIL ========== --}}
    @if(isset($recentActivity) && $recentActivity->isNotEmpty())
        <details class="pb-card px-3 py-2">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">Recent payments</summary>
            <div class="flex flex-wrap gap-2">
                @foreach($recentActivity as $pay)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        {{ $pay->customer->full_name ?? 'Customer' }}
                        <span class="font-mono text-emerald-600 dark:text-emerald-400">+KSh {{ number_format($pay->amount ?? 0, 0) }}</span>
                        <span class="text-slate-400">{{ $pay->processed_at?->diffForHumans() }}</span>
                    </span>
                @endforeach
            </div>
        </details>
    @endif

    {{-- ========== RESULTS ========== --}}
    @if($customers->isEmpty())
        <div class="pb-card py-16 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500/15 to-cyan-500/10">
                <svg class="h-8 w-8 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">No subscribers match</h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">
                @if(request()->anyFilled(['search','status','connection_type','router_id','plan_id','location','payment_status','online_only','expiring_only','created_from','created_to']))
                    No customers match your search or filters.
                @else
                    No customers yet.
                @endif
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('isp.customers.create') }}" class="pb-btn-primary px-5 py-2.5 text-sm">Add customer</a>
                @if(request()->anyFilled(['search','status','connection_type','router_id','plan_id','location','payment_status','online_only','expiring_only','created_from','created_to']))
                    <a href="{{ route('isp.customers.index') }}" class="pb-btn px-5 py-2.5 text-sm">Clear filters</a>
                @endif
            </div>
        </div>
    @else
        <div :class="viewCompact ? 'pb-customers-table--compact' : ''">
            <x-data-table>
                <thead>
                    <tr>
                        <th class="min-w-[200px]">Subscriber</th>
                        <th class="min-w-[140px]">Contact</th>
                        <th class="whitespace-nowrap">Access</th>
                        <th class="min-w-[160px]">Subscription</th>
                        <th class="min-w-[150px]">Billing</th>
                        <th class="min-w-[180px]">Network</th>
                        <th class="whitespace-nowrap">Activity</th>
                        <th class="whitespace-nowrap">Status</th>
                        <th class="text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                        @php
                            $sub = $customer->subscriptions->first();
                            $plan = $sub?->plan;
                            $pay = $customer->payments->first();
                            $sess = $customer->hotspotSessions->first();
                            $device = $customer->devices->first();
                            $ip = $sess?->ip_address ?? '—';
                            $mac = $sess?->mac_address ?? $device?->mac_address ?? '—';
                            $online = (bool) $sess;
                            $overdueAmt = (float) ($customer->overdue_amount ?? 0);
                            $pendingAmt = (float) ($customer->pending_amount ?? 0);
                            $balanceDue = $overdueAmt + $pendingAmt;
                            $bw = $plan?->speed_profile
                                ?? (($plan && ($plan->download_speed_kbps || $plan->upload_speed_kbps))
                                    ? (($plan->download_speed_kbps ?? 0) / 1000).'↓ / '.(($plan->upload_speed_kbps ?? 0) / 1000).'↑ Mbps'
                                    : '—');
                            $risk = $overdueAmt > 0 ? 'collection' : ($customer->status === 'suspended' ? 'churn' : null);
                            $payload = [
                                'id' => $customer->id,
                                'full_name' => $customer->full_name,
                                'username' => $customer->username,
                                'email' => $customer->email,
                                'phone' => $customer->phone_number,
                                'status' => $customer->status,
                                'connection_type' => $customer->connection_type,
                                'location' => $customer->location,
                                'plan' => $plan?->name,
                                'expiry' => $sub?->end_date?->format('M j, Y'),
                                'bandwidth' => $bw,
                                'router' => $customer->router?->name,
                                'router_ip' => $customer->router?->ip_address,
                                'ip' => $ip,
                                'mac' => $mac,
                                'online' => $online,
                                'last_seen' => $device?->last_seen_at?->diffForHumans() ?? ($sess?->started_at?->diffForHumans()),
                                'balance_due' => $balanceDue,
                                'overdue' => $overdueAmt,
                                'pending' => $pendingAmt,
                                'last_payment' => $pay ? ('KSh '.number_format($pay->amount, 0).' · '.$pay->processed_at?->diffForHumans()) : '—',
                                'notes' => \Illuminate\Support\Str::limit((string) $customer->notes, 160),
                                'invoices_count' => $customer->invoices_count ?? 0,
                                'payments_count' => $customer->payments_count ?? 0,
                                'urls' => [
                                    'show' => route('isp.customers.show', $customer),
                                    'edit' => route('isp.customers.edit', $customer),
                                    'invoice' => route('isp.invoices.create-for-customer', $customer),
                                    'payments' => route('isp.payments.customer', $customer),
                                    'sessions' => route('isp.sessions.customer-history', $customer),
                                    'settings_sms' => route('isp.settings.notifications'),
                                ],
                            ];
                            $util = $online ? 25 + ($customer->id % 50) : 90;
                        @endphp
                        <x-listing-table.row class="align-middle" :href="route('isp.customers.show', $customer)">
                            <td>
                                <button type="button" @click.stop="openDrawer(@js($payload))" class="group flex max-w-[220px] items-start gap-2 text-left sm:max-w-none">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-cyan-500 text-xs font-bold text-white shadow-md shadow-indigo-500/20">
                                        {{ strtoupper(\Illuminate\Support\Str::substr($customer->full_name, 0, 1)) }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-300">{{ $customer->full_name }}</span>
                                        <span class="block truncate font-mono text-slate-500 dark:text-slate-400">{{ $customer->username }}</span>
                                        <span class="mt-0.5 inline-block rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-600 dark:bg-slate-800 dark:text-slate-300">#{{ $customer->id }}</span>
                                        @if($risk === 'collection')
                                            <span class="ml-1 inline-flex rounded bg-rose-500/10 px-1.5 py-0.5 text-[10px] font-bold text-rose-600 dark:text-rose-400">Risk</span>
                                        @elseif($risk === 'churn')
                                            <span class="ml-1 inline-flex rounded bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 dark:text-amber-300">Churn</span>
                                        @endif
                                    </span>
                                </button>
                            </td>
                            <td class="max-w-[200px]">
                                <div class="truncate text-slate-800 dark:text-slate-100" title="{{ $customer->phone_number }}">{{ $customer->phone_number }}</div>
                                <div class="truncate text-slate-500 dark:text-slate-400" title="{{ $customer->email ?? '' }}">{{ $customer->email ?: '—' }}</div>
                            </td>
                            <td>
                                <div class="flex flex-col gap-1">
                                    @if($customer->connection_type === 'hotspot')
                                        <span class="inline-flex w-fit items-center gap-1 rounded-md border border-cyan-200 bg-cyan-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-cyan-800 dark:border-cyan-500/30 dark:bg-cyan-500/10 dark:text-cyan-200">Hotspot</span>
                                    @else
                                        <span class="inline-flex w-fit items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-800 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">PPPoE</span>
                                    @endif
                                    @if($online)
                                        <span class="inline-flex w-fit items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                                            Live
                                        </span>
                                    @else
                                        <span class="text-[10px] font-medium text-slate-400">Offline</span>
                                    @endif
                                </div>
                            </td>
                            <td class="max-w-[220px]">
                                <div class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $plan?->name ?? 'No active plan' }}</div>
                                <div class="truncate text-slate-500">{{ $bw }}</div>
                                <div class="text-slate-500">
                                    @if($sub)
                                        <span class="whitespace-nowrap">{{ $sub->end_date->format('M j, Y') }}</span>
                                        @if($sub->end_date->diffInDays(now(), false) <= 7 && $sub->end_date->isFuture())
                                            <span class="ml-1 rounded bg-amber-500/15 px-1 py-0.5 text-[10px] font-bold text-amber-700 dark:text-amber-300">Soon</span>
                                        @endif
                                    @else
                                        <span class="text-amber-600 dark:text-amber-400">Needs subscription</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="font-medium text-slate-800 dark:text-slate-100">
                                    Due <span class="font-mono {{ $balanceDue > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">KSh {{ number_format($balanceDue, 0) }}</span>
                                </div>
                                <div class="text-slate-500">Ovd {{ number_format($overdueAmt, 0) }} · Pend {{ number_format($pendingAmt, 0) }}</div>
                                <div class="max-w-[180px] truncate text-slate-500" title="{{ $pay ? 'KSh '.number_format($pay->amount, 0).' · '.$pay->processed_at?->diffForHumans() : '' }}">Last: {{ $pay ? 'KSh '.number_format($pay->amount, 0).' · '.$pay->processed_at?->diffForHumans() : '—' }}</div>
                            </td>
                            <td class="max-w-[220px]">
                                <div class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $customer->router?->name ?? 'No router' }}</div>
                                <div class="truncate font-mono text-slate-500">{{ $customer->router?->ip_address ?? '—' }}</div>
                                <div class="truncate font-mono text-slate-500" title="{{ $ip }} · {{ $mac }}">{{ $ip }} · {{ $mac }}</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="pb-meter h-1.5 w-16 shrink-0 sm:w-[4.5rem]"><div class="pb-meter-fill" style="right: {{ 100 - $util }}%"></div></div>
                                    <span class="text-[10px] text-slate-400">{{ $online ? 'Active' : 'Idle' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($customer->status === 'active')
                                    <span class="inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">Active</span>
                                @elseif($customer->status === 'suspended')
                                    <span class="inline-flex rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-bold text-amber-800 dark:text-amber-200">Suspended</span>
                                @elseif($customer->status === 'expired')
                                    <span class="inline-flex rounded-full bg-rose-500/15 px-2 py-0.5 text-[11px] font-bold text-rose-700 dark:text-rose-300">Expired</span>
                                @elseif($customer->status === 'inactive')
                                    <span class="inline-flex rounded-full bg-slate-500/15 px-2 py-0.5 text-[11px] font-bold text-slate-600 dark:text-slate-300">Inactive</span>
                                @elseif($customer->status === 'pending_installation')
                                    <span class="inline-flex rounded-full bg-sky-500/15 px-2 py-0.5 text-[11px] font-bold text-sky-700 dark:text-sky-300">Pending install</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-500/15 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ ucfirst($customer->status) }}</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="relative inline-flex items-center gap-1" data-no-row-nav x-data="{ open: false }" @click.outside="open = false">
                                    <button type="button" @click="open = !open" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40" title="Actions">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak x-transition class="absolute right-0 z-30 mt-1 w-52 origin-top-right rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                        <a href="{{ route('isp.customers.show', $customer) }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">View profile</a>
                                        <a href="{{ route('isp.customers.edit', $customer) }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Edit</a>
                                        <a href="{{ route('isp.invoices.create-for-customer', $customer) }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Generate invoice</a>
                                        <a href="{{ route('isp.sessions.customer-history', $customer) }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">View sessions</a>
                                        <a href="{{ route('isp.payments.customer', $customer) }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Payment history</a>
                                        <a href="{{ route('isp.settings.notifications') }}" class="block px-3 py-2 text-sm text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800">SMS settings…</a>
                                        <a href="{{ route('isp.customers.edit', $customer) }}#password" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Reset password</a>
                                        <form method="POST" action="{{ route('isp.user-connections.disconnect', $customer) }}" class="border-t border-slate-100 dark:border-slate-800" onsubmit="confirmFormSubmit(event, {title: 'Disconnect?', text: 'Terminate active sessions for this subscriber?', icon: 'warning'}); return false;">
                                            @csrf
                                            <button type="submit" class="block w-full px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">Disconnect user</button>
                                        </form>
                                        @if($customer->status === 'active')
                                            <form method="POST" action="{{ route('isp.customers.suspend', $customer) }}" onsubmit="confirmFormSubmit(event, {title: 'Suspend subscriber', text: 'Suspend {{ $customer->full_name }}?', icon: 'warning'}); return false;">
                                                @csrf
                                                <button type="submit" class="block w-full px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-500/10">Suspend</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('isp.customers.activate', $customer) }}" onsubmit="confirmFormSubmit(event, {title: 'Activate subscriber', text: 'Activate {{ $customer->full_name }}?', icon: 'question'}); return false;">
                                                @csrf
                                                <button type="submit" class="block w-full px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-500/10">Activate</button>
                                            </form>
                                        @endif
                                    </div>
                                    <a href="{{ route('isp.customers.show', $customer) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" title="Open profile">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </div>

        <div class="pb-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Showing
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $customers->firstItem() ?? 0 }}</span>
                –
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $customers->lastItem() ?? 0 }}</span>
                of
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($customers->total()) }}</span>
            </p>
            @if ($customers->hasPages())
                <div class="pb-pagination">{{ $customers->withQueryString()->links() }}</div>
            @endif
        </div>
    @endif

    {{-- ========== PROFILE DRAWER ========== --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-[60] flex justify-end">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeDrawer()"></div>
        <div x-show="drawerOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             class="relative flex h-full w-full max-w-lg flex-col border-l border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-950">
            <div class="flex h-full flex-col" x-show="selected" x-cloak>
                    <div class="border-b border-slate-100 bg-gradient-to-br from-indigo-600/90 to-slate-900 px-6 py-5 text-white dark:border-slate-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">Subscriber</p>
                                <h3 class="mt-1 text-xl font-bold" x-text="selected.full_name"></h3>
                                <p class="font-mono text-sm text-white/80"><span x-text="'@' + (selected && selected.username ? selected.username : '')"></span></p>
                                <p class="mt-2 text-xs text-white/70">ID #<span x-text="selected.id"></span> · <span x-text="selected.connection_type ? selected.connection_type.toUpperCase() : ''"></span></p>
                            </div>
                            <button type="button" @click="closeDrawer()" class="rounded-lg p-2 text-white/80 hover:bg-white/10">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-semibold">
                                <span class="h-2 w-2 rounded-full" :class="selected.online ? 'bg-emerald-400 animate-pulse' : 'bg-slate-400'"></span>
                                <span x-text="selected.online ? 'Online' : 'Offline'"></span>
                            </span>
                            <span class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-semibold" x-text="selected.status"></span>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                        <section>
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Contact</h4>
                            <dl class="mt-2 space-y-1 text-sm text-slate-700 dark:text-slate-300">
                                <div class="flex justify-between gap-2"><dt class="text-slate-500">Phone</dt><dd class="font-mono text-right" x-text="selected.phone"></dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-500">Email</dt><dd class="text-right truncate" x-text="selected.email || '—'"></dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-500">Site</dt><dd class="text-right text-xs" x-text="selected.location"></dd></div>
                            </dl>
                        </section>
                        <section>
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Subscription</h4>
                            <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white" x-text="selected.plan || 'No active plan'"></p>
                            <p class="text-xs text-slate-500"><span x-text="selected.bandwidth"></span> · Due <span x-text="selected.expiry || '—'"></span></p>
                        </section>
                        <section>
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Billing</h4>
                            <div class="mt-2 rounded-xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/60">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Balance due: <span class="font-mono text-rose-600 dark:text-rose-400">KSh <span x-text="Math.round(selected.balance_due || 0).toLocaleString()"></span></span></p>
                                <p class="text-xs text-slate-500 mt-1">Last payment: <span x-text="selected.last_payment"></span></p>
                                <p class="text-xs text-slate-500">Invoices: <span x-text="selected.invoices_count"></span> · Payments recorded: <span x-text="selected.payments_count"></span></p>
                            </div>
                        </section>
                        <section>
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Network</h4>
                            <dl class="mt-2 space-y-1 text-sm">
                                <div class="flex justify-between gap-2"><dt class="text-slate-500">Router</dt><dd class="text-right font-medium text-slate-800 dark:text-slate-100" x-text="selected.router || '—'"></dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-500">Router IP</dt><dd class="text-right font-mono text-xs" x-text="selected.router_ip || '—'"></dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-500">Customer IP</dt><dd class="text-right font-mono text-xs" x-text="selected.ip"></dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-500">MAC</dt><dd class="text-right font-mono text-xs break-all" x-text="selected.mac"></dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-slate-500">Last seen</dt><dd class="text-right text-xs text-slate-500" x-text="selected.last_seen || '—'"></dd></div>
                            </dl>
                        </section>
                        <section x-show="selected && selected.notes">
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Notes</h4>
                            <p class="mt-2 rounded-lg border border-slate-100 bg-amber-50/60 p-3 text-xs text-slate-700 dark:border-slate-800 dark:bg-amber-500/10 dark:text-slate-300" x-text="selected && selected.notes ? selected.notes : ''"></p>
                        </section>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/80 px-4 py-4 dark:border-slate-800 dark:bg-slate-900">
                        <div class="grid grid-cols-2 gap-2">
                            <a :href="selected ? selected.urls.show : '#'" class="pb-btn justify-center py-2.5 text-xs">Full profile</a>
                            <a :href="selected ? selected.urls.edit : '#'" class="pb-btn-primary justify-center py-2.5 text-xs">Edit</a>
                            <a :href="selected ? selected.urls.invoice : '#'" class="pb-btn justify-center py-2.5 text-xs">Invoice</a>
                            <a :href="selected ? selected.urls.sessions : '#'" class="pb-btn justify-center py-2.5 text-xs">Sessions</a>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>
@endsection
