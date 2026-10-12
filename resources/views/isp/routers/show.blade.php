@extends('layouts.admin')

@section('title', $router->name)

@section('header')
    <x-isp-page-header
        :title="$router->name"
        module="Network"
        :description="$router->location ?: ('MikroTik router · ' . $router->ip_address)"
        primaryLabel="Edit router"
        :primaryHref="route('isp.routers.edit', $router)"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.routers.index') }}" class="pb-btn">Back to fleet</a>
            <a href="{{ route('isp.routers.hotspot-portal', $router) }}" class="pb-btn">Download hotspot login page</a>
            <form method="POST" action="{{ route('isp.routers.poll', $router) }}" class="inline">
                @csrf
                <button type="submit" class="pb-btn pb-btn-primary">Sync now</button>
            </form>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
<div class="mb-5 rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-950 dark:border-cyan-500/30 dark:bg-cyan-500/10 dark:text-cyan-100">
    <strong>Captive portal setup:</strong> set the Hotspot gateway IP on this router to the client-reachable gateway address, upload the downloaded <code>login.html</code> into the router's hotspot files, set the Hotspot server profile's HTML Directory to that folder, and allow your PrimeBilling domain in the Hotspot walled garden. RouterOS API credentials on this record are used for post-payment provisioning.
</div>
@php
    $tone = match ($snapshot['reachability'] ?? 'unknown') {
        'online' => 'ok',
        'degraded', 'rebooting' => 'warn',
        'offline' => 'danger',
        default => 'muted',
    };
    $reachLabel = match ($snapshot['reachability'] ?? 'unknown') {
        'online' => 'Online',
        'degraded' => 'Degraded',
        'rebooting' => 'Rebooting',
        'offline' => 'Offline',
        default => 'Awaiting telemetry',
    };
@endphp

<div
    class="space-y-6"
    x-data="{ tab: 'overview' }"
>
    {{-- Status bar --}}
    <div class="pb-card p-4 sm:p-5 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-3">
            <span class="isp-noc-router-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="4" width="16" height="10" rx="1"/><path d="M9 20h6"/><path d="M12 14v6"/></svg>
            </span>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Reachability</p>
                <p class="mt-0.5 text-lg font-bold {{ $tone === 'ok' ? 'text-emerald-600' : ($tone === 'warn' ? 'text-amber-600' : ($tone === 'danger' ? 'text-rose-600' : 'text-slate-500')) }}">
                    {{ $reachLabel }}
                </p>
                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                    @if($snapshot['has_live'] ?? false)
                        Last poll {{ $snapshot['last_seen_human'] }}{{ $snapshot['last_sync_sec'] !== null ? ' · '.$snapshot['last_sync_sec'].'s ago' : '' }}
                    @else
                        Awaiting the first telemetry poll (runs every minute)
                    @endif
                </p>
            </div>
        </div>
        <div class="ml-auto flex flex-wrap gap-2">
            @if($router->is_active)
                <span class="isp-noc-chip isp-noc-chip--ok">Active in billing</span>
            @else
                <span class="isp-noc-chip isp-noc-chip--danger">Inactive in billing</span>
            @endif
            <span class="isp-noc-chip isp-noc-chip--info">{{ ucfirst($router->lifecycle_status ?? 'live') }}</span>
            @foreach($router->tagsArray() as $tag)
                <span class="isp-noc-chip isp-noc-chip--muted">#{{ $tag }}</span>
            @endforeach
        </div>
    </div>

    @if(! empty($snapshot['last_poll_error']))
        <div class="pb-card border-l-4 border-amber-400 bg-amber-50/60 p-4 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-100">
            <p class="font-semibold">Last poll error</p>
            <p class="mt-1 font-mono text-xs">{{ $snapshot['last_poll_error'] }}</p>
        </div>
    @endif

    {{-- KPI strip --}}
    <div class="isp-noc-kpi-grid">
        <div class="isp-noc-kpi pb-card p-4">
            <p class="isp-noc-kpi__label">Latency</p>
            <p class="isp-noc-kpi__value">{{ $snapshot['latency_ms'] !== null ? $snapshot['latency_ms'].'ms' : '—' }}</p>
            <p class="isp-noc-kpi__hint">RouterOS API RTT</p>
        </div>
        <div class="isp-noc-kpi pb-card p-4 {{ ($snapshot['health_pct'] ?? 0) >= 80 ? 'isp-noc-kpi--ok' : 'isp-noc-kpi--health' }}">
            <p class="isp-noc-kpi__label">Health</p>
            <p class="isp-noc-kpi__value">{{ $snapshot['health_pct'] ?? 0 }}<span class="text-base font-bold text-slate-400">%</span></p>
            <div class="isp-noc-health-bar mt-2"><span style="width: {{ min(100, $snapshot['health_pct'] ?? 0) }}%"></span></div>
        </div>
        <div class="isp-noc-kpi pb-card p-4">
            <p class="isp-noc-kpi__label">CPU</p>
            <p class="isp-noc-kpi__value">{{ $snapshot['cpu_pct'] !== null ? $snapshot['cpu_pct'].'%' : '—' }}</p>
            <p class="isp-noc-kpi__hint">/system/resource</p>
        </div>
        <div class="isp-noc-kpi pb-card p-4">
            <p class="isp-noc-kpi__label">Memory</p>
            <p class="isp-noc-kpi__value">{{ $snapshot['mem_pct'] !== null ? $snapshot['mem_pct'].'%' : '—' }}</p>
            <p class="isp-noc-kpi__hint">Used</p>
        </div>
        <div class="isp-noc-kpi pb-card p-4">
            <p class="isp-noc-kpi__label">Hotspot users</p>
            <p class="isp-noc-kpi__value">{{ $snapshot['hotspot_users'] !== null ? number_format($snapshot['hotspot_users']) : '—' }}</p>
            <p class="isp-noc-kpi__hint">/ip/hotspot/active</p>
        </div>
        <div class="isp-noc-kpi pb-card p-4">
            <p class="isp-noc-kpi__label">PPP users</p>
            <p class="isp-noc-kpi__value">{{ $snapshot['pppoe_users'] !== null ? number_format($snapshot['pppoe_users']) : '—' }}</p>
            <p class="isp-noc-kpi__hint">/ppp/active</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="pb-card p-0 overflow-hidden">
        <div class="flex flex-wrap gap-1 border-b border-slate-200/80 px-3 py-2 dark:border-slate-700/80">
            @php
                $tabs = [
                    'overview' => 'Overview',
                    'telemetry' => 'Telemetry',
                    'subscribers' => 'Subscribers',
                    'hotspot' => 'Hotspot servers',
                    'ops' => 'Operations',
                    'backups' => 'Backups',
                    'audit' => 'Audit log',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <button type="button"
                    class="rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-wider transition"
                    :class="tab === '{{ $key }}' ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-200' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'"
                    @click="tab = '{{ $key }}'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Overview tab --}}
        <div x-show="tab === 'overview'" x-cloak class="p-4 sm:p-6 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4">
                <h3 class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Connection</h3>
                <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt class="isp-form-label mb-1">IP address</dt>
                        <dd class="font-mono text-lg font-semibold text-slate-900 dark:text-white">{{ $router->ip_address }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Port</dt>
                        <dd class="text-lg text-slate-800 dark:text-slate-100">
                            {{ $router->port }}
                            @if($router->use_ssl)<span class="text-sm text-emerald-600 dark:text-emerald-400">(TLS)</span>@endif
                        </dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="isp-form-label mb-1">API URL</dt>
                        <dd class="break-all font-mono text-sm text-slate-800 dark:text-slate-100">{{ $router->getApiUrl() }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Firmware</dt>
                        <dd class="font-mono text-sm text-slate-800 dark:text-slate-100">{{ $snapshot['firmware'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Board</dt>
                        <dd class="font-mono text-sm text-slate-800 dark:text-slate-100">{{ $snapshot['model'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Interfaces</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $snapshot['interfaces_up'] ?? 0 }} / {{ $snapshot['interfaces_total'] ?? 0 }} up</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Connected customers</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ number_format($router->customers_count) }}</dd>
                    </div>
                </dl>

                <h3 class="mt-6 text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Asset / CMDB</h3>
                <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt class="isp-form-label mb-1">Vendor / model</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ trim(($router->vendor ?? '').' '.($router->model ?? '')) ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Serial / MAC</dt>
                        <dd class="font-mono text-xs text-slate-800 dark:text-slate-100">{{ $router->serial_number ?? '—' }} · {{ $router->mac_address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Branch / location</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $router->branch ?: $router->location ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Owner</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ optional($router->owner)->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Purchase / warranty</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">
                            {{ $router->purchase_date?->format('Y-m-d') ?: '—' }}
                            @if($router->warranty_expiry)
                                · warranty until {{ $router->warranty_expiry->format('Y-m-d') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Monthly cost</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $router->monthly_cost !== null ? number_format($router->monthly_cost, 2) : '—' }}</dd>
                    </div>
                </dl>
            </div>

            <aside class="space-y-3">
                <h3 class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Quick actions</h3>
                <a href="{{ route('isp.routers.edit', $router) }}" class="pb-btn pb-btn-primary w-full justify-center">Edit router</a>
                <form method="POST" action="{{ route('isp.routers.poll', $router) }}">@csrf
                    <button type="submit" class="pb-btn w-full justify-center">Run telemetry poll now</button>
                </form>
                <form method="POST" action="{{ route('isp.routers.test', $router) }}" onsubmit="confirmFormSubmit(event, {title: 'Test API session', text: 'Open a fresh RouterOS API session?', icon: 'question', confirmButtonText: 'Test'}); return false;">@csrf
                    <button type="submit" class="pb-btn w-full justify-center">Test API connection</button>
                </form>
                <form method="POST" action="{{ route('isp.routers.backup', $router) }}">@csrf
                    <button type="submit" class="pb-btn w-full justify-center">Backup config (/export)</button>
                </form>
                <form method="POST" action="{{ route('isp.routers.reboot', $router) }}" onsubmit="confirmFormSubmit(event, {title: 'Reboot router', text: 'This will momentarily disconnect every subscriber on {{ $router->name }}.', icon: 'warning', confirmButtonText: 'Reboot'}); return false;">@csrf
                    <button type="submit" class="pb-btn w-full justify-center border-rose-200 bg-rose-50 text-rose-900 hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100">Reboot router</button>
                </form>
                @if($router->is_active)
                    <form method="POST" action="{{ route('isp.routers.deactivate', $router) }}" onsubmit="confirmFormSubmit(event, {title: 'Deactivate router', text: 'Mark router as inactive in billing?', icon: 'warning', confirmButtonText: 'Deactivate'}); return false;">@csrf
                        <button type="submit" class="pb-btn w-full justify-center border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">Deactivate</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('isp.routers.activate', $router) }}" onsubmit="confirmFormSubmit(event, {title: 'Activate router', text: 'Activate {{ $router->name }}?', icon: 'question', confirmButtonText: 'Activate'}); return false;">@csrf
                        <button type="submit" class="pb-btn w-full justify-center border-emerald-200 bg-emerald-50 text-emerald-900 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">Activate</button>
                    </form>
                @endif
            </aside>
        </div>

        {{-- Telemetry tab --}}
        <div x-show="tab === 'telemetry'" x-cloak class="p-4 sm:p-6 space-y-6">
            @if(empty($metrics) || count($metrics) === 0)
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-600">
                    <p class="font-semibold text-slate-700 dark:text-slate-200">No telemetry yet</p>
                    <p class="mt-1">The scheduler runs <code class="font-mono text-xs">PollRouterHealthJob</code> every minute. Make sure <code class="font-mono text-xs">php artisan schedule:work</code> (or your cron) is running.</p>
                </div>
            @else
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="pb-card p-4">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Latency (last {{ count($latencySeries) }} polls)</p>
                        <div class="mt-3">
                            <x-dashboard.sparkline :data="$latencySeries" :height="64" :width="400" stroke="#6366f1" fill="rgba(99, 102, 241, 0.1)" />
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Lower is better. Spikes above 35 ms flip the row to "degraded".</p>
                    </div>
                    <div class="pb-card p-4">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Health</p>
                        <div class="mt-3">
                            <x-dashboard.sparkline :data="$healthSeries" :height="64" :width="400" stroke="#10b981" fill="rgba(16, 185, 129, 0.1)" />
                        </div>
                    </div>
                    <div class="pb-card p-4">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">CPU %</p>
                        <div class="mt-3">
                            <x-dashboard.sparkline :data="$cpuSeries" :height="64" :width="400" stroke="#f59e0b" fill="rgba(245, 158, 11, 0.1)" />
                        </div>
                    </div>
                    <div class="pb-card p-4">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Memory %</p>
                        <div class="mt-3">
                            <x-dashboard.sparkline :data="$memSeries" :height="64" :width="400" stroke="#0ea5e9" fill="rgba(14, 165, 233, 0.1)" />
                        </div>
                    </div>
                </div>

                <div class="pb-card p-0 overflow-hidden">
                    <div class="border-b border-slate-200/80 px-4 py-3 dark:border-slate-700/80">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Recent poll history</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="isp-noc-table min-w-[800px] w-full text-left text-sm">
                            <thead>
                                <tr>
                                    <th class="isp-noc-th">When</th>
                                    <th class="isp-noc-th">Reach</th>
                                    <th class="isp-noc-th">Latency</th>
                                    <th class="isp-noc-th">CPU</th>
                                    <th class="isp-noc-th">RAM</th>
                                    <th class="isp-noc-th">Health</th>
                                    <th class="isp-noc-th">Hotspot</th>
                                    <th class="isp-noc-th">PPP</th>
                                    <th class="isp-noc-th">Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($metrics->reverse()->take(20) as $m)
                                    <tr class="isp-noc-tr">
                                        <td class="isp-noc-td text-xs text-slate-500">{{ $m->recorded_at->diffForHumans() }}</td>
                                        <td class="isp-noc-td">
                                            <span class="isp-noc-chip isp-noc-chip--{{ $m->reachability === 'online' ? 'ok' : ($m->reachability === 'degraded' ? 'warn' : 'danger') }}">{{ $m->reachability }}</span>
                                        </td>
                                        <td class="isp-noc-td font-mono">{{ $m->latency_ms !== null ? $m->latency_ms.' ms' : '—' }}</td>
                                        <td class="isp-noc-td">{{ $m->cpu_pct !== null ? $m->cpu_pct.'%' : '—' }}</td>
                                        <td class="isp-noc-td">{{ $m->mem_pct !== null ? $m->mem_pct.'%' : '—' }}</td>
                                        <td class="isp-noc-td">{{ $m->health_pct !== null ? $m->health_pct.'%' : '—' }}</td>
                                        <td class="isp-noc-td">{{ $m->active_hotspot_users ?? '—' }}</td>
                                        <td class="isp-noc-td">{{ $m->active_pppoe_users ?? '—' }}</td>
                                        <td class="isp-noc-td text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($m->error_message, 40) ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Subscribers tab --}}
        <div x-show="tab === 'subscribers'" x-cloak class="p-4 sm:p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ number_format($router->customers_count) }} subscriber(s) attached to this router.
                </p>
                <a href="{{ route('isp.customers.index', ['router_id' => $router->id]) }}" class="pb-btn pb-btn-primary text-xs">Open in customers</a>
            </div>
        </div>

        {{-- Hotspot tab --}}
        <div x-show="tab === 'hotspot'" x-cloak class="p-4 sm:p-6 space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-600 dark:text-slate-300">Hotspot servers imported from this router.</p>
                <a href="{{ route('isp.hotspot-servers.index', ['router_id' => $router->id]) }}" class="pb-btn pb-btn-primary text-xs">Open hotspot servers</a>
            </div>
            <ul class="divide-y divide-slate-200/80 rounded-xl border border-slate-200/80 dark:divide-slate-700/80 dark:border-slate-700/80">
                @forelse($router->hotspotServers ?? collect() as $hs)
                    <li class="flex items-center justify-between px-4 py-2 text-sm">
                        <span class="font-semibold">{{ $hs->name }}</span>
                        <span class="font-mono text-xs text-slate-500">{{ $hs->interface }}</span>
                        <span class="isp-noc-chip isp-noc-chip--{{ $hs->is_active ? 'ok' : 'muted' }}">{{ $hs->is_active ? 'Active' : 'Disabled' }}</span>
                    </li>
                @empty
                    <li class="px-4 py-4 text-sm text-slate-500">No hotspot servers imported. Run "Test API connection" above to re-scan.</li>
                @endforelse
            </ul>
        </div>

        {{-- Ops tab — on-demand ping --}}
        <div x-show="tab === 'ops'" x-cloak class="p-4 sm:p-6 space-y-5">
            <div class="pb-card p-4">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Ping from router</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Runs <code class="font-mono">/ping</code> on the device and stores the result in the audit log.</p>
                <form method="POST" action="{{ route('isp.routers.ping', $router) }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="grow min-w-[200px]">
                        <label class="isp-form-label mb-1">Target</label>
                        <input type="text" name="address" required placeholder="8.8.8.8 or example.com" class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" />
                    </div>
                    <div class="w-24">
                        <label class="isp-form-label mb-1">Count</label>
                        <input type="number" name="count" value="4" min="1" max="20" class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" />
                    </div>
                    <button type="submit" class="pb-btn pb-btn-primary">Ping</button>
                </form>
            </div>

            <div class="pb-card p-4">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Re-import hotspot servers</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Runs the same import wizard used on router creation. Safe to run repeatedly.</p>
                <form method="POST" action="{{ route('isp.routers.test', $router) }}" class="mt-3" onsubmit="confirmFormSubmit(event, {title: 'Re-sync hotspot servers', text: 'Pull the latest hotspot server config from {{ $router->name }}?', icon: 'question', confirmButtonText: 'Re-sync'}); return false;">
                    @csrf
                    <button type="submit" class="pb-btn pb-btn-primary">Re-sync hotspot servers</button>
                </form>
            </div>
        </div>

        {{-- Backups tab --}}
        <div x-show="tab === 'backups'" x-cloak class="p-4 sm:p-6 space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ count($backups) }} backup(s) on file.</p>
                <form method="POST" action="{{ route('isp.routers.backup', $router) }}">@csrf
                    <button type="submit" class="pb-btn pb-btn-primary text-xs">Queue new backup</button>
                </form>
            </div>
            <ul class="divide-y divide-slate-200/80 rounded-xl border border-slate-200/80 dark:divide-slate-700/80 dark:border-slate-700/80">
                @forelse($backups as $b)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div>
                            <p class="font-semibold">{{ $b->filename }}</p>
                            <p class="text-xs text-slate-500">{{ $b->created_at->format('Y-m-d H:i') }} · {{ $b->humanSize() }} · {{ $b->status }}</p>
                        </div>
                        <a href="{{ route('isp.routers.backups.download', [$router, $b]) }}" class="pb-btn text-xs">Download</a>
                    </li>
                @empty
                    <li class="px-4 py-4 text-sm text-slate-500">No backups yet. Click "Queue new backup" above.</li>
                @endforelse
            </ul>
        </div>

        {{-- Audit log tab --}}
        <div x-show="tab === 'audit'" x-cloak class="p-4 sm:p-6 space-y-2">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-600 dark:text-slate-300">Most recent 30 router actions for this device.</p>
                <a href="{{ route('isp.logs.router-actions.index', ['router_id' => $router->id]) }}" class="pb-btn text-xs">Full action log</a>
            </div>
            <ol class="space-y-2">
                @forelse($actionLogs as $log)
                    <li class="flex flex-wrap items-start gap-3 rounded-xl border border-slate-200/80 px-4 py-3 dark:border-slate-700/80">
                        <span class="isp-noc-chip isp-noc-chip--{{ $log->status === 'success' ? 'ok' : ($log->status === 'failed' ? 'danger' : 'warn') }}">{{ $log->status }}</span>
                        <div class="grow min-w-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $log->action }}</p>
                            <p class="text-xs text-slate-600 dark:text-slate-300">{{ $log->description }}</p>
                            @if($log->error_message)
                                <p class="mt-1 font-mono text-[11px] text-rose-600 dark:text-rose-400">{{ \Illuminate\Support\Str::limit($log->error_message, 200) }}</p>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-500">{{ $log->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-600">No actions recorded yet.</li>
                @endforelse
            </ol>
        </div>
    </div>
</div>
@endsection
