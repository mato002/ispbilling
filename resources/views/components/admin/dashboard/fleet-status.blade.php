@props([
    'totals' => [],
    'tenants' => collect(),
])

<section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400">NOC</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">Platform fleet status</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Live MikroTik reachability aggregated per active tenant.</p>
            </div>
            <a href="{{ route('admin.network.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                Network console →
            </a>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/60 px-3 py-2.5 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                <div class="flex items-center gap-2">
                    <span class="pb-noc-dot pb-noc-dot--healthy" aria-hidden="true"></span>
                    <span class="text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Online</span>
                </div>
                <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-800 dark:text-emerald-200">{{ number_format($totals['online'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl border border-rose-200/70 bg-rose-50/60 px-3 py-2.5 dark:border-rose-500/20 dark:bg-rose-500/10">
                <div class="flex items-center gap-2">
                    <span class="pb-noc-dot pb-noc-dot--critical" aria-hidden="true"></span>
                    <span class="text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">Offline</span>
                </div>
                <p class="mt-1 text-2xl font-bold tabular-nums text-rose-800 dark:text-rose-200">{{ number_format($totals['offline'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl border border-amber-200/70 bg-amber-50/60 px-3 py-2.5 dark:border-amber-500/20 dark:bg-amber-500/10">
                <div class="flex items-center gap-2">
                    <span class="pb-noc-dot pb-noc-dot--degraded" aria-hidden="true"></span>
                    <span class="text-[10px] font-bold uppercase tracking-wide text-amber-800 dark:text-amber-200">Degraded</span>
                </div>
                <p class="mt-1 text-2xl font-bold tabular-nums text-amber-900 dark:text-amber-100">{{ number_format($totals['degraded'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/50">
                <div class="flex items-center gap-2">
                    <span class="pb-noc-dot pb-noc-dot--unconfigured" aria-hidden="true"></span>
                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">Unconfigured</span>
                </div>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-800 dark:text-slate-100">{{ number_format($totals['unconfigured'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    <div class="max-h-[min(28rem,55vh)] overflow-y-auto px-3 py-2 sm:px-4">
        @forelse ($tenants as $tenant)
            @php
                $health = $tenant['health'];
                $dotClass = match ($health) {
                    'healthy' => 'pb-noc-dot--healthy',
                    'degraded' => 'pb-noc-dot--degraded',
                    'unconfigured' => 'pb-noc-dot--unconfigured',
                    default => 'pb-noc-dot--critical',
                };
            @endphp
            <div class="flex items-center gap-3 rounded-xl border border-transparent px-2 py-2.5 transition hover:border-slate-200 hover:bg-slate-50/80 dark:hover:border-slate-700 dark:hover:bg-slate-800/40">
                <span class="pb-noc-dot {{ $dotClass }}" title="{{ ucfirst($health) }}" aria-hidden="true"></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                        <a href="{{ $tenant['show_url'] }}" class="truncate text-sm font-semibold text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-300">
                            {{ $tenant['name'] }}
                        </a>
                        @if ($tenant['platform_locked'])
                            <span class="rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-600 dark:text-rose-300">Locked</span>
                        @endif
                        @if ($tenant['saas_status'])
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $tenant['saas_status'] }}</span>
                        @endif
                    </div>
                    <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $tenant['company_name'] }}</p>
                </div>
                <div class="hidden shrink-0 text-right text-[10px] font-mono text-slate-500 dark:text-slate-400 sm:block">
                    <span class="text-emerald-600 dark:text-emerald-400">{{ $tenant['counts']['online'] }}</span>
                    <span class="text-slate-300 dark:text-slate-600">/</span>
                    <span class="text-rose-600 dark:text-rose-400">{{ $tenant['counts']['offline'] }}</span>
                    <span class="text-slate-400"> routers</span>
                </div>
                <div class="flex shrink-0 items-center gap-1.5 text-[10px] font-semibold tabular-nums">
                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-emerald-700 dark:text-emerald-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ $tenant['counts']['online'] }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-md bg-rose-500/10 px-1.5 py-0.5 text-rose-700 dark:text-rose-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>{{ $tenant['counts']['offline'] }}
                    </span>
                    @if ($tenant['counts']['degraded'] > 0)
                        <span class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 px-1.5 py-0.5 text-amber-800 dark:text-amber-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $tenant['counts']['degraded'] }}
                        </span>
                    @endif
                </div>
                <a href="{{ $tenant['edit_url'] }}" class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-semibold text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-500/10">
                    Configure
                </a>
            </div>
        @empty
            <p class="px-3 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No active tenants with fleet telemetry yet.</p>
        @endforelse
    </div>
</section>
