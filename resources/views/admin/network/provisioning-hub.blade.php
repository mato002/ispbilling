@extends('layouts.admin')

@section('title', 'Provisioning Control Hub')

@section('header')
    <x-isp-page-header
        title="Subscribers, Device MAC & Queue Provisioning Control Hub"
        module="Network Operations"
        description="Pre-aggregated provisioning state — MAC bindings, queue health, and router profile alignment."
    />
@endsection

@section('content')
@php
    $activeBindings = $metrics['active_bindings_count'] ?? 0;
    $mismatchedMac = $metrics['mismatched_mac_count'] ?? 0;
    $saturatedQueues = $metrics['saturated_queue_count'] ?? 0;
    $orphanProfiles = $metrics['orphan_router_profiles_count'] ?? 0;
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Snapshot refreshed:
            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $refreshedAt ? \Illuminate\Support\Carbon::parse($refreshedAt)->diffForHumans() : 'pending' }}</span>
            · {{ count($snapshots) }} subscriber rows
        </p>
        <form method="POST" action="{{ route('isp.provisioning-hub.refresh') }}">
            @csrf
            <button type="submit" class="pb-btn text-xs">Refresh snapshot</button>
        </form>
    </div>

    {{-- KPI row --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-[var(--pb-border)] bg-white p-5 shadow-sm dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Active Network Bindings</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($activeBindings) }}</p>
            <p class="mt-1 text-xs text-slate-500">Active MACs | Synced</p>
        </div>

        <div class="rounded-xl border border-[var(--pb-border)] bg-white p-5 shadow-sm dark:bg-slate-900 {{ $mismatchedMac > 0 ? 'ring-1 ring-rose-300 dark:ring-rose-800' : '' }}">
            <div class="flex items-start justify-between gap-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Mismatched MAC Alerts</p>
                @if($mismatchedMac > 0)
                    <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $mismatchedMac }}</span>
                @endif
            </div>
            <p class="mt-2 text-3xl font-bold {{ $mismatchedMac > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">{{ number_format($mismatchedMac) }}</p>
            <p class="mt-1 text-xs text-slate-500">Hardware vs live session</p>
        </div>

        <div class="rounded-xl border border-[var(--pb-border)] bg-white p-5 shadow-sm dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Saturated Queues</p>
            <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($saturatedQueues) }}</p>
            <p class="mt-1 text-xs text-slate-500">Near limit or throttled</p>
        </div>

        <div class="rounded-xl border border-[var(--pb-border)] bg-white p-5 shadow-sm dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Orphaned Router Profiles</p>
            <p class="mt-2 text-3xl font-bold text-violet-600 dark:text-violet-400">{{ number_format($orphanProfiles) }}</p>
            <p class="mt-1 text-xs text-slate-500">Router users without billing match</p>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        {{-- Main table --}}
        <div class="overflow-hidden rounded-xl border border-[var(--pb-border)] bg-white shadow-sm dark:bg-slate-900">
            <div class="border-b border-[var(--pb-border)] px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Provisioning snapshot</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60">
                        <tr>
                            <th class="px-4 py-3">Customer Profile</th>
                            <th class="px-4 py-3">ISP Tenant</th>
                            <th class="px-4 py-3">Provisioned Plan</th>
                            <th class="px-4 py-3">Hardware MAC</th>
                            <th class="px-4 py-3">Current Session MAC</th>
                            <th class="px-4 py-3">Queue State</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--pb-border)]">
                        @forelse($snapshots as $row)
                            @php
                                $flags = $row['status_flags'] ?? [];
                                $queueState = $row['queue_state'] ?? 'normal';
                                $queueIndicator = match ($queueState) {
                                    'throttled' => 'bg-rose-500',
                                    'near_limit' => 'bg-amber-400',
                                    default => 'bg-emerald-500',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3">
                                    <a href="{{ route('isp.customers.show', $row['customer_id']) }}" class="font-medium text-indigo-600 hover:underline">{{ $row['customer_name'] }}</a>
                                    <p class="text-[11px] text-slate-500">{{ strtoupper($row['connection_type'] ?? '') }} · {{ ucfirst($row['customer_status'] ?? '') }}</p>
                                    @if(($flags['mac_verified'] ?? false) === true)
                                        <span class="mt-1 inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300">✓ Verified MAC</span>
                                    @elseif(($flags['mac_mismatch'] ?? false) === true)
                                        <span class="mt-1 inline-flex rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:text-amber-200">🛑 MAC MISMATCH</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row['isp_tenant_name'] ?? $ispName }}</td>
                                <td class="px-4 py-3">{{ $row['plan_name'] ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $row['hardware_mac'] ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $row['session_mac'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($row['queue_rate_limit'])
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2 py-1 font-mono text-xs dark:bg-slate-800">
                                            <span class="h-2 w-2 rounded-full {{ $queueIndicator }}"></span>
                                            {{ $row['queue_rate_limit'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('isp.customers.show', $row['customer_id']) }}" class="text-xs font-medium text-indigo-600">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                                    No provisioned subscribers in snapshot yet. Queue a refresh or wait for the scheduled job.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Control panel --}}
        <aside class="xl:sticky xl:top-24 xl:self-start">
            <div class="space-y-4 rounded-xl border border-[var(--pb-border)] bg-white p-4 shadow-sm dark:bg-slate-900">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Instant Network Correction Panel</h3>
                <p class="text-xs text-slate-500">All corrections are explicit admin actions and run as background jobs.</p>

                <div class="border-t border-[var(--pb-border)] pt-4">
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Tool A · Manual MAC Authorization</h4>
                    <form method="POST" action="{{ route('isp.provisioning-hub.force-bind-mac') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-300">Customer</label>
                            <select name="customer_id" required class="isp-form-select w-full text-sm">
                                <option value="">Select customer…</option>
                                @foreach($customerOptions as $option)
                                    <option value="{{ $option['id'] }}" data-router="{{ $option['router_id'] }}">{{ $option['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-300">New MAC address</label>
                            <input type="text" name="new_mac_address" required placeholder="AA:BB:CC:DD:EE:FF" class="isp-form-input w-full font-mono text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-300">Router</label>
                            <select name="router_id" required class="isp-form-select w-full text-sm">
                                <option value="">Select router…</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->id }}">{{ $router->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="pb-btn w-full justify-center text-xs" onclick="return confirm('Authorize this MAC on the router for the selected customer?');">
                            Authorize MAC
                        </button>
                    </form>
                </div>

                <div class="border-t border-[var(--pb-border)] pt-4">
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Tool B · Live Queue Flusher</h4>
                    <form method="POST" action="{{ route('isp.provisioning-hub.flush-queues') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-300">Customer</label>
                            <select name="customer_id" required class="isp-form-select w-full text-sm">
                                <option value="">Select customer…</option>
                                @foreach($customerOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-300">Router</label>
                            <select name="router_id" required class="isp-form-select w-full text-sm">
                                <option value="">Select router…</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->id }}">{{ $router->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="pb-btn w-full justify-center border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200 text-xs" onclick="return confirm('Disconnect active session and rebuild queues for this customer?');">
                            Flush Session &amp; Rebuild Queues
                        </button>
                    </form>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
