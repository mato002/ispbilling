<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Platform control plane</p>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Global Communication & Master SMS Controller') }}
                </h2>
            </div>
            <a href="{{ route('admin.marketing.index') }}" class="pb-btn pb-btn-secondary shrink-0 text-xs" data-turbo-frame="pb-main">
                Messaging credentials →
            </a>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    {{-- Revenue metrics row --}}
    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-dashboard.kpi-card
            title="Total global SMS volume sent"
            :value="number_format($metrics['total_volume'])"
            subtext="Ledger deductions + dispatched queue messages"
            icon-bg="from-violet-600 to-indigo-500"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            title="Platform SMS revenue gross"
            :value="'KSh '.number_format($metrics['gross_revenue'], 2)"
            subtext="Sum of tenant wallet top-ups (M-Pesa & manual credits)"
            icon-bg="from-emerald-600 to-teal-500"
        >
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </x-slot:icon>
        </x-dashboard.kpi-card>

        <div class="pb-card pb-fade-up flex flex-col justify-between p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-[0_8px_18px_-8px_rgba(245,158,11,0.55)]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    </span>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Active service driver</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">Root SMS socket</p>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                @php
                    $driverPremium = in_array($metrics['driver_key'], ['africastalking', 'onfon'], true);
                @endphp
                <span @class([
                    'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-bold tracking-tight',
                    'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/25' => $driverPremium,
                    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' => ! $driverPremium,
                ])>
                    @if ($driverPremium)
                        <span class="h-2 w-2 rounded-full bg-emerald-300 shadow-[0_0_8px_rgba(110,231,183,0.9)]" aria-hidden="true"></span>
                    @endif
                    {{ $metrics['driver_label'] }}
                </span>
                <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
                    Override via <code class="text-[10px]">SMS_PLATFORM_DRIVER</code> or Marketing → SMS provider.
                </p>
            </div>
        </div>
    </div>

    {{-- Tenant balances grid --}}
    <div class="pb-card isp-settings-panel mb-8 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--pb-border)] px-4 py-4 sm:px-6">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Tenant SMS wallets</h3>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">All registered SaaS ISP tenants · balances below KSh 50 flagged</p>
            </div>
            <span class="pb-pill pb-pill-info text-[10px]">{{ $tenants->count() }} workspaces</span>
        </div>
        <div class="isp-table-wrap !rounded-none !border-0">
            <table class="isp-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>ISP name</th>
                        <th>Subdomain context</th>
                        <th>Active wallet balance</th>
                        <th>Messages dispatched</th>
                        <th class="text-right">SMS dispatch</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenants as $row)
                        @php /** @var \App\Models\ISP $isp */ $isp = $row['isp']; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.isps.show', $isp) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400" data-turbo-frame="pb-main">
                                    {{ $isp->name }}
                                </a>
                                <div class="text-[11px] text-slate-500">{{ $isp->company_name }}</div>
                            </td>
                            <td>
                                <code class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $row['subdomain'] }}</code>
                            </td>
                            <td>
                                @if ($row['low_balance'])
                                    <span class="inline-flex items-center gap-2 rounded-lg border border-amber-300/80 bg-amber-50 px-2.5 py-1 text-sm font-bold text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                                        <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        KSh {{ number_format($row['balance'], 2) }}
                                    </span>
                                @else
                                    <span class="font-semibold text-slate-900 dark:text-white">KSh {{ number_format($row['balance'], 2) }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="font-mono text-slate-800 dark:text-slate-100">{{ number_format($row['messages_dispatched']) }}</span>
                            </td>
                            <td class="text-right">
                                <form
                                    method="POST"
                                    action="{{ route('admin.communications.tenants.toggle', $isp) }}"
                                    class="inline-flex items-center justify-end gap-2"
                                    data-turbo-frame="pb-main"
                                >
                                    @csrf
                                    <input type="hidden" name="enabled" value="0">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide {{ $row['sms_enabled'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                        {{ $row['sms_enabled'] ? 'Live' : 'Paused' }}
                                    </span>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input
                                            type="checkbox"
                                            name="enabled"
                                            value="1"
                                            class="peer sr-only"
                                            @checked($row['sms_enabled'])
                                            onchange="this.form.submit()"
                                        >
                                        <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-emerald-500 dark:bg-slate-700"></span>
                                        <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                    </label>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">No ISP tenants registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent deposits timeline --}}
    <div class="pb-card isp-settings-panel overflow-hidden">
        <div class="border-b border-[var(--pb-border)] px-4 py-4 sm:px-6">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Recent deposits timeline</h3>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Real-time tenant wallet credits via M-Pesa and manual ledger top-ups</p>
        </div>
        <div class="divide-y divide-[var(--pb-border)]">
            @forelse ($recentDeposits as $deposit)
                @php
                    $tenantName = $deposit->tenant?->name ?? 'Unknown ISP';
                    $when = $deposit->created_at?->timezone(config('app.timezone'))->diffForHumans();
                    $absolute = $deposit->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A');
                @endphp
                <div class="flex gap-4 px-4 py-4 sm:px-6">
                    <div class="relative flex flex-col items-center">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        </span>
                        @if (! $loop->last)
                            <span class="mt-1 w-px flex-1 bg-[var(--pb-border)]" aria-hidden="true"></span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 pb-1">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                            <span class="text-emerald-700 dark:text-emerald-300">{{ $tenantName }}</span>
                            credited
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">+KSh {{ number_format((float) $deposit->amount, 2) }}</span>
                        </p>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $deposit->description }}</p>
                        <p class="mt-1.5 text-[11px] text-slate-400" title="{{ $absolute }}">
                            {{ $when }}
                            @if ($deposit->sms_count > 0)
                                · ~{{ number_format($deposit->sms_count) }} SMS credits
                            @endif
                        </p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">No wallet top-ups recorded yet.</p>
                    <p class="mt-1 text-xs text-slate-500">Tenant M-Pesa STK purchases will appear here once ledger entries are created.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-admin-layout>
