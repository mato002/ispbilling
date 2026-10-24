@extends('layouts.admin')

@section('title', 'Network Routers')

@section('header')
    <x-isp-page-header
        title="Network Routers"
        module="Network Operations"
        description="Manage MikroTik infrastructure, monitor connectivity, and control subscriber routing."
        primaryLabel="Provision router"
        :primaryHref="route('isp.routers.create')"
    >
        <x-slot name="actions">
            <x-export-dropdown :formats="$exportFormats ?? []" label="Export" />
            <span class="isp-noc-live-pill" title="Live telemetry polled every minute via PollRouterHealthJob">
                <span class="isp-noc-live-pill__dot" aria-hidden="true"></span>
                Live NOC
            </span>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
@php
    $snapFor = fn ($r) => $r->noc ?? [];
    $detailPayload = fn ($router) => array_merge(
        $router->only(['id', 'name', 'ip_address', 'port', 'location', 'use_ssl', 'is_active']),
        ['customers_count' => $router->customers_count, 'noc' => $snapFor($router)]
    );
    $latencyTone = function (array $noc): string {
        $reach = $noc['reachability'] ?? 'unknown';
        if ($reach === 'offline') {
            return 'danger';
        }
        if (in_array($reach, ['degraded', 'rebooting'], true)) {
            return 'warn';
        }
        if (! ($noc['has_live'] ?? false)) {
            return 'muted';
        }
        $ms = $noc['latency_ms'] ?? 0;
        if ($ms > 35) {
            return 'warn';
        }
        return 'ok';
    };
@endphp

<div
    class="isp-noc-root space-y-3"
    x-data="{
        detailOpen: false,
        detail: null,
        refreshing: false,
        filtersOpen: false,
        selected: [],
        lastSyncLabel: @js(now()->format('H:i:s')),
        openDetail(payload) { this.detail = payload; this.detailOpen = true; document.body.classList.add('overflow-hidden'); },
        closeDetail() { this.detailOpen = false; this.detail = null; document.body.classList.remove('overflow-hidden'); },
        toggleAll(checked, ids) { this.selected = checked ? [...ids] : []; },
        syncFleet() {
            this.refreshing = true;
            window.setTimeout(() => window.location.reload(), 200);
        },
        init() {
            window.setInterval(() => {
                const d = new Date();
                this.lastSyncLabel = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }, 1000);
        }
    }"
    @keydown.escape.window="closeDetail(); filtersOpen = false"
>
    {{-- Fleet KPI strip --}}
    <details class="pb-card px-3 py-2">
        <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">Fleet metrics</summary>
        <div class="isp-noc-kpi-grid mt-2">
        <div class="isp-noc-kpi pb-card p-4">
            <p class="isp-noc-kpi__label">Total routers</p>
            <p class="isp-noc-kpi__value">{{ number_format($fleetStats['total']) }}</p>
            <p class="isp-noc-kpi__hint">Fleet inventory</p>
        </div>
        <div class="isp-noc-kpi pb-card p-4 isp-noc-kpi--ok">
            <p class="isp-noc-kpi__label">Online</p>
            <p class="isp-noc-kpi__value text-emerald-600 dark:text-emerald-400">{{ number_format($fleetStats['online']) }}</p>
            <p class="isp-noc-kpi__hint">Operational</p>
        </div>
        <div class="isp-noc-kpi pb-card p-4 isp-noc-kpi--danger">
            <p class="isp-noc-kpi__label">Offline</p>
            <p class="isp-noc-kpi__value text-rose-600 dark:text-rose-400">{{ number_format($fleetStats['offline']) }}</p>
            <p class="isp-noc-kpi__hint">Standby / unreachable</p>
        </div>
        <div class="isp-noc-kpi pb-card p-4">
            <p class="isp-noc-kpi__label">Subscribers on routers</p>
            <p class="isp-noc-kpi__value">{{ number_format($fleetStats['connected_customers']) }}</p>
            <p class="isp-noc-kpi__hint">Assigned customers</p>
        </div>
        <div class="isp-noc-kpi pb-card p-4">
            <p class="isp-noc-kpi__label">Avg latency</p>
            <p class="isp-noc-kpi__value">
                @if($fleetStats['avg_latency_ms'] !== null)
                    {{ $fleetStats['avg_latency_ms'] }}<span class="text-base font-bold text-slate-400">ms</span>
                @else
                    —
                @endif
            </p>
            <p class="isp-noc-kpi__hint">{{ $fleetStats['with_telemetry'] ?? 0 }} / {{ $fleetStats['total'] }} reporting</p>
        </div>
        <div class="isp-noc-kpi pb-card p-4 isp-noc-kpi--health">
            <p class="isp-noc-kpi__label">Network health</p>
            <p class="isp-noc-kpi__value">
                @if($fleetStats['network_health'] !== null)
                    {{ $fleetStats['network_health'] }}<span class="text-base font-bold text-slate-400">%</span>
                @else
                    —
                @endif
            </p>
            <div class="isp-noc-health-bar mt-2" role="progressbar" aria-valuenow="{{ $fleetStats['network_health'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100">
                <span style="width: {{ $fleetStats['network_health'] !== null ? min(100, $fleetStats['network_health']) : 0 }}%"></span>
            </div>
        </div>
        </div>
    </details>

    {{-- Live status strip --}}
    <div class="pb-card isp-noc-live-strip overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-200/80 px-4 py-3 dark:border-slate-700/80 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-600 dark:text-slate-300">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-emerald-700 ring-1 ring-emerald-500/25 dark:text-emerald-300">
                    <span class="isp-noc-pulse h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    Live polling — every minute
                </span>
                <span class="hidden sm:inline text-slate-400">|</span>
                <span class="tabular-nums text-slate-500 dark:text-slate-400">Clock <span x-text="lastSyncLabel"></span></span>
                <span class="hidden md:inline text-slate-400">·</span>
                <span class="text-violet-600 dark:text-violet-300">{{ $fleetStats['degraded'] ?? 0 }} degraded</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('isp.routers.bulk') }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="poll">
                    @foreach($routers as $r)
                        <input type="hidden" name="ids[]" value="{{ $r->id }}">
                    @endforeach
                    <button type="submit" class="isp-noc-toolbar-btn isp-noc-toolbar-btn--primary" title="Queue an immediate poll for every router on this page">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
                        Sync fleet now
                    </button>
                </form>
                <button type="button" class="isp-noc-toolbar-btn" :class="refreshing && 'isp-noc-toolbar-btn--loading'" :disabled="refreshing" @click="syncFleet()" title="Reload this page">
                    Reload view
                </button>
            </div>
        </div>
        <div class="grid gap-px bg-slate-200/80 dark:bg-slate-700/80 sm:grid-cols-3">
            <div class="isp-noc-live-cell bg-[var(--pb-panel)] px-4 py-3 sm:px-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Reachability source</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">RouterOS API ping</p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Stored in router_metrics every minute</p>
            </div>
            <div class="isp-noc-live-cell bg-[var(--pb-panel)] px-4 py-3 sm:px-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Telemetry</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $fleetStats['with_telemetry'] ?? 0 }} / {{ $fleetStats['total'] }} reporting</p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Sparklines pulled from real polls</p>
            </div>
            <div class="isp-noc-live-cell bg-[var(--pb-panel)] px-4 py-3 sm:px-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Scope</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">MikroTik fleet</p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Hotspot · PPP · interfaces · CPU/RAM</p>
            </div>
        </div>
    </div>

    {{-- Topology + insights --}}
    @if($fleetStats['total'] > 0)
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="isp-noc-topo pb-card p-5 lg:col-span-1">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h2 class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Topology</h2>
                    <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">Branch map (preview)</p>
                </div>
                <span class="rounded-md bg-indigo-500/10 px-2 py-0.5 text-[10px] font-bold uppercase text-indigo-600 dark:text-indigo-300">Beta</span>
            </div>
            <div class="isp-noc-topo__canvas mt-4" aria-hidden="true">
                <div class="isp-noc-topo__node isp-noc-topo__node--core">CORE</div>
                <div class="isp-noc-topo__edge"></div>
                <div class="isp-noc-topo__node isp-noc-topo__node--edge">EDGE</div>
                <div class="isp-noc-topo__edge isp-noc-topo__edge--2"></div>
                <div class="isp-noc-topo__node isp-noc-topo__node--cpe">CPE</div>
            </div>
            <p class="mt-4 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                Geographic / branch overlays and L2 path tracing will render here. For now this panel anchors the NOC layout.
            </p>
        </div>
        <div class="space-y-3 lg:col-span-2">
            <h2 class="px-0.5 text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Quick insights</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="isp-noc-insight pb-card p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Highest traffic (preview)</p>
                    @if($networkInsights['busiest'])
                        <p class="mt-2 font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $networkInsights['busiest']->name }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Relative load index {{ $snapFor($networkInsights['busiest'])['traffic_score'] ?? '—' }}</p>
                    @else
                        <p class="mt-2 text-sm text-slate-500">—</p>
                    @endif
                </div>
                <div class="isp-noc-insight pb-card p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Most subscribers</p>
                    @if($networkInsights['most_subscribers'])
                        <p class="mt-2 font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $networkInsights['most_subscribers']->name }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ number_format($networkInsights['most_subscribers']->customers_count) }} customers</p>
                    @else
                        <p class="mt-2 text-sm text-slate-500">—</p>
                    @endif
                </div>
                <div class="isp-noc-insight pb-card p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Last standby router</p>
                    @if($networkInsights['last_offline'])
                        <p class="mt-2 font-mono text-sm font-bold text-rose-600 dark:text-rose-400">{{ $networkInsights['last_offline']->name }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Inactive in billing · verify WAN</p>
                    @else
                        <p class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">All routers marked active</p>
                    @endif
                </div>
                <div class="isp-noc-insight pb-card p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Congestion</p>
                    <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $networkInsights['congestion_note'] }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    @if($fleetStats['total'] > 0)
    <div class="pb-card overflow-hidden p-0">
        <div class="border-b border-slate-200/80 px-4 py-3 dark:border-slate-700/80 sm:px-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Fleet filters</h2>
                <span class="hidden text-[10px] font-medium text-slate-400 md:inline">Search · status · branch · health · transport</span>
            </div>
        </div>
        <div class="px-4 py-3 md:hidden">
            <button type="button" class="pb-btn w-full justify-center" @click="filtersOpen = true">⚙ Filter</button>
        </div>
        <div class="isp-filter-modal" role="dialog" aria-modal="true" :class="{ 'is-open': filtersOpen }" @click.self="filtersOpen = false">
        <form
            action="{{ route('isp.routers.index') }}"
            method="GET"
            class="isp-noc-filter-form px-4 py-4 sm:px-5"
            @submit="filtersOpen = false"
            data-turbo-frame="pb-main"
            x-data="pbFilterForm({ autoSearch: true })"
        >
            <div class="isp-filter-modal-heading md:hidden">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Fleet filters</h2>
                <button type="button" class="pb-btn px-2 py-1" aria-label="Close filters" @click="filtersOpen = false">✕</button>
            </div>
            <div class="isp-noc-filter-grid">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, IP, site…" class="isp-noc-filter-input pl-10" autocomplete="off" />
                </div>
                <select name="is_active" class="isp-noc-filter-select" aria-label="Operational status">
                    <option value="">All statuses</option>
                    <option value="1" @selected(request('is_active') === '1')>Active / in service</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactive / standby</option>
                </select>
                <input type="text" name="branch" value="{{ request('branch') }}" placeholder="Branch / site filter" class="isp-noc-filter-input" title="Matches location field" />
                <select name="health" class="isp-noc-filter-select" aria-label="Reachability">
                    <option value="">All reachability</option>
                    <option value="ok" @selected(request('health') === 'ok')>Online · healthy latency</option>
                    <option value="online" @selected(request('health') === 'online')>Any online</option>
                    <option value="warn" @selected(request('health') === 'warn')>Degraded / rebooting</option>
                    <option value="offline" @selected(request('health') === 'offline')>Offline</option>
                    <option value="unknown" @selected(request('health') === 'unknown')>Awaiting first poll</option>
                </select>
                <select name="conn_type" class="isp-noc-filter-select" aria-label="Transport">
                    <option value="">All transports</option>
                    <option value="ssl" @selected(request('conn_type') === 'ssl')>HTTPS API</option>
                    <option value="plain" @selected(request('conn_type') === 'plain')>HTTP API</option>
                </select>
                <select name="lifecycle" class="isp-noc-filter-select" aria-label="Lifecycle">
                    <option value="">All lifecycle</option>
                    @foreach (['live', 'stock', 'maintenance', 'retired'] as $opt)
                        <option value="{{ $opt }}" @selected(request('lifecycle') === $opt)>{{ ucfirst($opt) }}</option>
                    @endforeach
                </select>
                <select name="sort" class="isp-noc-filter-select" aria-label="Sort">
                    <option value="latest" @selected(request('sort','latest') === 'latest')>Newest first</option>
                    <option value="name" @selected(request('sort') === 'name')>Name</option>
                    <option value="latency" @selected(request('sort') === 'latency')>Latency (low → high)</option>
                    <option value="health" @selected(request('sort') === 'health')>Health (high → low)</option>
                    <option value="customers" @selected(request('sort') === 'customers')>Most subscribers</option>
                </select>
                <select name="per_page" class="isp-noc-filter-select" aria-label="Per page">
                    @foreach ([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }} / page</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                <a href="{{ route('isp.routers.index') }}" data-turbo-frame="pb-main" class="isp-noc-toolbar-btn">Reset</a>
                @if (count($filterChips ?? []) > 0)
                    <x-filter-chips :chips="$filterChips" class="flex-1" />
                @endif
            </div>
            <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3 dark:border-slate-800 md:hidden">
                <button type="submit" class="pb-btn pb-btn-primary flex-1">Apply filters</button>
                <button type="button" class="pb-btn" @click="filtersOpen = false">Close</button>
            </div>
        </form>
        </div>
    </div>
    @endif

    @if($fleetStats['total'] === 0)
        <div class="isp-noc-empty pb-card overflow-hidden">
            <div class="isp-noc-empty__hero px-6 py-10 text-center sm:px-10 sm:py-12">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500/20 to-cyan-500/15 ring-1 ring-indigo-500/25">
                    <svg class="h-8 w-8 text-indigo-600 dark:text-indigo-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                </div>
                <h3 class="mt-6 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Provision your first MikroTik edge</h3>
                <p class="mx-auto mt-2 max-w-lg text-sm text-slate-600 dark:text-slate-400">
                    This console is built for live RouterOS fleets. Add a router to sync hotspot servers, IP pools, sessions, and subscriber attachment points.
                </p>
                <a href="{{ route('isp.routers.create') }}" class="pb-btn pb-btn-primary mt-8 inline-flex">Start connection wizard</a>
            </div>
            <div class="grid gap-px border-t border-slate-200/80 bg-slate-200/80 dark:border-slate-700/80 dark:bg-slate-700/80 sm:grid-cols-3">
                <div class="bg-[var(--pb-panel)] px-5 py-4 text-left">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-violet-600 dark:text-violet-300">1 · Reachability</p>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">Validate API port, SSL, and firewall allow-list.</p>
                </div>
                <div class="bg-[var(--pb-panel)] px-5 py-4 text-left">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-cyan-600 dark:text-cyan-300">2 · Services</p>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">Import hotspot profiles and pools into billing.</p>
                </div>
                <div class="bg-[var(--pb-panel)] px-5 py-4 text-left">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-300">3 · Subscribers</p>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">Attach customers to this edge for automation.</p>
                </div>
            </div>
        </div>
    @elseif($routers->isEmpty())
        <div class="pb-card p-8 text-center">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">No routers match these filters</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Adjust search or status, or reset the fleet filters.</p>
            <a href="{{ route('isp.routers.index') }}" class="pb-btn pb-btn-primary mt-4 inline-flex text-sm">Reset filters</a>
        </div>
    @else
        {{-- Bulk action bar --}}
        <form method="POST" action="{{ route('isp.routers.bulk') }}" class="pb-card p-3 sm:p-4" x-show="selected.length > 0" x-cloak>
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500"><span x-text="selected.length"></span> selected</span>
                <button type="submit" name="action" value="poll" class="pb-btn text-xs">Poll telemetry</button>
                <button type="submit" name="action" value="test" class="pb-btn text-xs">Test API</button>
                <button type="submit" name="action" value="backup" class="pb-btn text-xs">Backup config</button>
                <button type="submit" name="action" value="activate" class="pb-btn text-xs">Activate</button>
                <button type="submit" name="action" value="deactivate" class="pb-btn text-xs border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">Deactivate</button>
                <button type="button" class="pb-btn text-xs" @click="selected = []">Clear selection</button>
            </div>
        </form>

        {{-- Desktop table --}}
        <div class="hidden md:block pb-card p-0 overflow-hidden">
            <div class="isp-noc-table-scroll">
                <table class="isp-noc-table min-w-[1180px] w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="isp-noc-th w-8">
                                <input type="checkbox" @change="toggleAll($event.target.checked, [{{ $routers->pluck('id')->implode(',') }}])"
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" aria-label="Select all on page" />
                            </th>
                            <th class="isp-noc-th">Router</th>
                            <th class="isp-noc-th">Endpoints</th>
                            <th class="isp-noc-th">Site</th>
                            <th class="isp-noc-th">Health</th>
                            <th class="isp-noc-th">Telemetry</th>
                            <th class="isp-noc-th">Subscribers</th>
                            <th class="isp-noc-th isp-noc-th--sticky text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($routers as $router)
                            @php $noc = $snapFor($router); $tone = $latencyTone($noc); @endphp
                            <tr class="isp-noc-tr group">
                                <td class="isp-noc-td">
                                    <input type="checkbox" :value="{{ $router->id }}" x-model.number="selected"
                                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" aria-label="Select router {{ $router->name }}" />
                                </td>
                                <td class="isp-noc-td">
                                    <div class="flex items-start gap-3">
                                        <span class="isp-noc-router-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="4" width="16" height="10" rx="1"/><path d="M9 20h6"/><path d="M12 14v6"/></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 dark:text-white">{{ $router->name }}</p>
                                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ $noc['model'] }}</p>
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @php $reach = $noc['reachability'] ?? 'unknown'; @endphp
                                                @if($reach === 'online')
                                                    <span class="isp-noc-chip isp-noc-chip--ok"><span class="isp-noc-pulse h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Online</span>
                                                @elseif(in_array($reach, ['degraded', 'rebooting'], true))
                                                    <span class="isp-noc-chip isp-noc-chip--warn"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> {{ ucfirst($reach) }}</span>
                                                @elseif($reach === 'offline')
                                                    <span class="isp-noc-chip isp-noc-chip--danger"><span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span> Offline</span>
                                                @else
                                                    <span class="isp-noc-chip isp-noc-chip--muted">Awaiting poll</span>
                                                @endif
                                                @if($noc['api_status'] === 'connected')
                                                    <span class="isp-noc-chip isp-noc-chip--info">API</span>
                                                @elseif($noc['api_status'] === 'degraded')
                                                    <span class="isp-noc-chip isp-noc-chip--warn">API</span>
                                                @else
                                                    <span class="isp-noc-chip isp-noc-chip--muted">API</span>
                                                @endif
                                                @if($router->branch)
                                                    <span class="isp-noc-chip isp-noc-chip--muted">{{ $router->branch }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="isp-noc-td font-mono text-xs">
                                    <div class="text-slate-900 dark:text-slate-100">{{ $router->ip_address }}</div>
                                    <div class="mt-1 text-slate-500 dark:text-slate-400">:{{ $router->port }}@if($router->use_ssl)<span class="ml-1 text-emerald-600 dark:text-emerald-400">TLS</span>@else<span class="ml-1 text-slate-400">plain</span>@endif</div>
                                </td>
                                <td class="isp-noc-td text-xs text-slate-600 dark:text-slate-300">{{ $router->location ?? '—' }}</td>
                                <td class="isp-noc-td">
                                    <div class="flex items-center gap-3">
                                        <div class="isp-noc-ring" data-tone="{{ $router->is_active ? $tone : 'danger' }}" style="--p: {{ $router->is_active ? $noc['health_pct'] : 0 }}">
                                            <span>{{ $router->is_active ? $noc['health_pct'] : 0 }}%</span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="isp-noc-uptime-track">
                                                <span style="width: {{ $router->is_active ? $noc['uptime_pct'] : 0 }}%"></span>
                                            </div>
                                            <p class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Uptime est. {{ $router->is_active ? $noc['uptime_pct'] : 0 }}%</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="isp-noc-td">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($router->is_active && $noc['latency_ms'] !== null)
                                            <span class="isp-noc-lat isp-noc-lat--{{ $tone }}">{{ $noc['latency_ms'] }}ms</span>
                                        @else
                                            <span class="isp-noc-lat isp-noc-lat--danger">—</span>
                                        @endif
                                        @if($router->is_active && $noc['last_sync_sec'] !== null)
                                            <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">Sync {{ $noc['last_sync_sec'] }}s</span>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <x-dashboard.sparkline
                                            :data="$noc['spark']"
                                            :height="32"
                                            :width="112"
                                            :stroke="$tone === 'warn' ? '#f59e0b' : ($router->is_active ? '#10b981' : '#64748b')"
                                            :fill="$tone === 'warn' ? 'rgba(245, 158, 11, 0.12)' : ($router->is_active ? 'rgba(16, 185, 129, 0.12)' : 'rgba(100, 116, 139, 0.1)')"
                                        />
                                    </div>
                                </td>
                                <td class="isp-noc-td tabular-nums">
                                    <span class="text-base font-bold text-slate-900 dark:text-white">{{ number_format($router->customers_count) }}</span>
                                    <p class="text-[10px] text-slate-500">assigned</p>
                                </td>
                                <td class="isp-noc-td isp-noc-td--sticky text-right">
                                    <div class="isp-noc-actions flex justify-end gap-1">
                                        <button type="button" class="isp-noc-icon-btn" title="Quick view" @click.prevent="openDetail({{ \Illuminate\Support\Js::from($detailPayload($router)) }})">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                        <a href="{{ route('isp.routers.show', $router) }}" class="isp-noc-icon-btn" title="Full detail page">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        </a>
                                        <a href="{{ route('isp.routers.edit', $router) }}" class="isp-noc-icon-btn" title="Edit router">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                        <div class="relative" x-data="{ open: false }">
                                            <button type="button" class="isp-noc-icon-btn isp-noc-icon-btn--more" @click="open = !open" :aria-expanded="open" title="More operations">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                            </button>
                                            <div x-show="open" x-cloak @click.outside="open = false" x-transition class="isp-noc-dropdown absolute right-0 z-50 mt-1 w-56 py-1">
                                                <form method="POST" action="{{ route('isp.routers.poll', $router) }}" class="block">
                                                    @csrf
                                                    <button type="submit" class="isp-noc-dropdown__item w-full text-left">Poll telemetry now</button>
                                                </form>
                                                <form method="POST" action="{{ route('isp.routers.test', $router) }}" class="block" onsubmit="confirmFormSubmit(event, {title: 'Test API session', text: 'Run API handshake to {{ $router->name }}?', icon: 'question', confirmButtonText: 'Test'}); return false;">
                                                    @csrf
                                                    <button type="submit" class="isp-noc-dropdown__item w-full text-left">Test API connection</button>
                                                </form>
                                                <form method="POST" action="{{ route('isp.routers.backup', $router) }}" class="block">
                                                    @csrf
                                                    <button type="submit" class="isp-noc-dropdown__item w-full text-left">Backup config (/export)</button>
                                                </form>
                                                @if($router->is_active)
                                                    <a href="{{ route('isp.hotspot-servers.index') }}" class="isp-noc-dropdown__item">Hotspot servers</a>
                                                    <a href="{{ route('isp.customers.index', ['router_id' => $router->id]) }}" class="isp-noc-dropdown__item">PPPoE / subscribers</a>
                                                    <form method="POST" action="{{ route('isp.routers.reboot', $router) }}" onsubmit="confirmFormSubmit(event, {title: 'Reboot router', text: 'Disconnect every subscriber on {{ $router->name }} for ~90 seconds?', icon: 'warning', confirmButtonText: 'Reboot'}); return false;">
                                                        @csrf
                                                        <button type="submit" class="isp-noc-dropdown__item isp-noc-dropdown__item--danger w-full text-left">Reboot router</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('isp.routers.deactivate', $router) }}" onsubmit="confirmFormSubmit(event, {title: 'Disable router', text: 'Mark this router inactive in billing?', icon: 'warning', confirmButtonText: 'Disable'}); return false;">
                                                        @csrf
                                                        <button type="submit" class="isp-noc-dropdown__item isp-noc-dropdown__item--danger w-full text-left">Disable in billing</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('isp.routers.activate', $router) }}" onsubmit="confirmFormSubmit(event, {title: 'Enable router', text: 'Activate {{ $router->name }}?', icon: 'question', confirmButtonText: 'Enable'}); return false;">
                                                        @csrf
                                                        <button type="submit" class="isp-noc-dropdown__item w-full text-left">Enable</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="space-y-3 md:hidden">
            @foreach($routers as $router)
                @php $noc = $snapFor($router); $tone = $latencyTone($noc); @endphp
                <div class="isp-noc-mobile-card pb-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white">{{ $router->name }}</p>
                            <p class="text-[11px] text-slate-500">{{ $noc['model'] }}</p>
                        </div>
                        @if($router->is_active)
                            <span class="isp-noc-chip isp-noc-chip--ok">Online</span>
                        @else
                            <span class="isp-noc-chip isp-noc-chip--danger">Offline</span>
                        @endif
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <p class="text-[10px] font-bold uppercase text-slate-400">IP</p>
                            <p class="font-mono text-slate-800 dark:text-slate-200">{{ $router->ip_address }}:{{ $router->port }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase text-slate-400">Latency</p>
                            <p class="font-semibold {{ $tone === 'warn' ? 'text-amber-600' : ($router->is_active ? 'text-emerald-600' : 'text-rose-600') }}">
                                {{ $router->is_active && $noc['latency_ms'] !== null ? $noc['latency_ms'].' ms' : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase text-slate-400">Health</p>
                            <p class="font-bold">{{ $router->is_active ? $noc['health_pct'] : 0 }}%</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase text-slate-400">Customers</p>
                            <p class="font-bold tabular-nums">{{ number_format($router->customers_count) }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-200/80 pt-3 dark:border-slate-700/80">
                        <button type="button" class="pb-btn pb-btn-primary text-xs" @click="openDetail({{ \Illuminate\Support\Js::from($detailPayload($router)) }})">Quick view</button>
                        <a href="{{ route('isp.routers.show', $router) }}" class="pb-btn text-xs">Open</a>
                        <a href="{{ route('isp.routers.edit', $router) }}" class="pb-btn text-xs">Edit</a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pb-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Showing
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $routers->firstItem() ?? 0 }}</span>
                –
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $routers->lastItem() ?? 0 }}</span>
                of
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($routers->total()) }}</span>
            </p>
            @if ($routers->hasPages())
                <div class="pb-pagination">{{ $routers->withQueryString()->links() }}</div>
            @endif
        </div>
    @endif

    {{-- Quick view modal --}}
    <div
        x-show="detailOpen"
        x-cloak
        class="isp-noc-modal-root fixed inset-0 z-[100] flex items-end justify-center sm:items-center"
        x-transition.opacity
        aria-modal="true"
        role="dialog"
    >
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px]" @click="closeDetail()"></div>
        <div
            class="isp-noc-modal relative z-10 m-0 max-h-[min(92vh,720px)] w-full max-w-lg overflow-y-auto rounded-t-2xl border border-slate-200 bg-[var(--pb-panel)] shadow-2xl dark:border-slate-700 sm:m-4 sm:rounded-2xl"
            x-show="detailOpen"
            x-transition
            @click.stop
        >
            <template x-if="detail">
                <div>
                    <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-slate-200/80 bg-[var(--pb-panel)]/95 px-5 py-4 backdrop-blur dark:border-slate-700/80">
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-violet-600 dark:text-violet-300">Router detail</p>
                            <h3 class="truncate text-lg font-extrabold text-slate-900 dark:text-white" x-text="detail.name"></h3>
                            <p class="mt-0.5 font-mono text-xs text-slate-500" x-text="detail.ip_address + ':' + detail.port"></p>
                        </div>
                        <button type="button" class="isp-noc-icon-btn shrink-0" @click="closeDetail()" title="Close">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-4 px-5 py-5">
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-700/80">
                                <p class="text-[10px] font-bold uppercase text-slate-400">API</p>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-white" x-text="detail.noc.api_label"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-700/80">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Firmware</p>
                                <p class="mt-1 font-mono font-semibold text-slate-900 dark:text-white" x-text="detail.noc.firmware"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-700/80">
                                <p class="text-[10px] font-bold uppercase text-slate-400">CPU</p>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-white"><span x-text="detail.noc.cpu_pct ?? '—'"></span><span x-show="detail.noc.cpu_pct != null">%</span></p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-700/80">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Memory</p>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-white"><span x-text="detail.noc.mem_pct ?? '—'"></span><span x-show="detail.noc.mem_pct != null">%</span></p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-700/80">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Hotspot users</p>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-white" x-text="detail.noc.hotspot_users ?? '—'"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-700/80">
                                <p class="text-[10px] font-bold uppercase text-slate-400">PPPoE users</p>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-white" x-text="detail.noc.pppoe_users ?? '—'"></p>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-200/80 p-3 text-xs dark:border-slate-700/80">
                            <p class="text-[10px] font-bold uppercase text-slate-400">Interfaces (preview)</p>
                            <p class="mt-1 font-semibold text-slate-900 dark:text-white">
                                <span x-text="detail.noc.interfaces_up"></span> / <span x-text="detail.noc.interfaces_total"></span> up
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200/80 p-3 text-xs dark:border-slate-700/80">
                            <p class="text-[10px] font-bold uppercase text-slate-400">Traffic</p>
                            <p class="mt-1 text-slate-600 dark:text-slate-400">Relative load index <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="detail.noc.traffic_score"></span> · sparkline shown in fleet grid.</p>
                        </div>
                        <a class="pb-btn pb-btn-primary block w-full text-center text-sm" :href="`{{ url('/isp/routers') }}/${detail.id}`">Open full router page</a>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
