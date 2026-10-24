@extends('layouts.admin')

@section('title', 'Network Routers')

@section('header')
    <x-isp-page-header
        title="Network Routers"
        module="Network Operations"
        primaryLabel="Create router"
        :primaryHref="route('isp.routers.create')"
    >
        <x-slot name="actions">

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

        selected: [],
        openDetail(payload) { this.detail = payload; this.detailOpen = true; document.body.classList.add('overflow-hidden'); },
        closeDetail() { this.detailOpen = false; this.detail = null; document.body.classList.remove('overflow-hidden'); },
        toggleAll(checked, ids) { this.selected = checked ? [...ids] : []; },
    }"
    @keydown.escape.window="closeDetail()"
>
    {{-- Shared fleet filter bar --}}
    @if($fleetStats['total'] > 0)
        <x-filter-bar
            :action="route('isp.routers.index')"
            :reset-url="route('isp.routers.index')"
            :export-formats="$exportFormats ?? []"
            :chips="$filterChips ?? []"
        >
            <x-slot:heading><h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Routers</h2></x-slot:heading>
            <x-slot:filters>
                <x-listing-filters.search name="q" placeholder="Search name, IP, site…" class="min-w-[220px] flex-1" />
                <x-listing-filters.select name="is_active" label="Status" placeholder="All statuses" :options="['1' => 'Active / in service', '0' => 'Inactive / standby']" class="min-w-[150px]" />
                <div class="min-w-[150px]"><label class="isp-form-label">Branch / site</label><input type="text" name="branch" value="{{ request('branch') }}" placeholder="Branch / site" class="isp-form-input"></div>
                <x-listing-filters.select name="health" label="Reachability" placeholder="All" :options="['ok' => 'Online · healthy', 'online' => 'Any online', 'warn' => 'Degraded / rebooting', 'offline' => 'Offline', 'unknown' => 'Awaiting first poll']" class="min-w-[180px]" />
                <x-listing-filters.select name="conn_type" label="Transport" placeholder="All" :options="['ssl' => 'HTTPS API', 'plain' => 'HTTP API']" class="min-w-[140px]" />
                <x-listing-filters.select name="lifecycle" label="Lifecycle" placeholder="All" :options="array_combine(['live', 'stock', 'maintenance', 'retired'], ['Live', 'Stock', 'Maintenance', 'Retired'])" class="min-w-[150px]" />
                <x-listing-filters.select name="sort" label="Sort" :placeholder="null" :options="['latest' => 'Newest first', 'name' => 'Name', 'latency' => 'Latency (low → high)', 'health' => 'Health (high → low)', 'customers' => 'Most subscribers']" class="min-w-[180px]" />
                <x-listing-filters.per-page />
            </x-slot:filters>
        </x-filter-bar>
    @endif
    @if($routers->isEmpty() && $fleetStats['total'] > 0)
        <div class="pb-card p-8 text-center">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">No routers match these filters</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Adjust search or status, or reset the fleet filters.</p>
            <a href="{{ route('isp.routers.index') }}" class="pb-btn pb-btn-primary mt-4 inline-flex text-sm">Reset filters</a>
        </div>
    @elseif($routers->isEmpty())
        <div class="pb-card overflow-x-auto">
            <table class="w-full min-w-[520px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="px-4 py-3">Router</th>
                        <th class="px-4 py-3">IP address</th>
                        <th class="px-4 py-3">Site</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No routers yet. Select “Create router” to add one.</td></tr>
                </tbody>
            </table>
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
