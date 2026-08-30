@props([
    'tenants' => collect(),
])

<section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Tenant directory</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Compact roster with direct links to tenant micro-portals.</p>
        </div>
        <a href="{{ route('admin.isps.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">All ISPs →</a>
    </div>

    <div class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse ($tenants->take(12) as $isp)
            <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50/80 dark:hover:bg-slate-800/30">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500/15 to-cyan-500/15 text-[11px] font-bold text-indigo-700 dark:text-indigo-300">
                    {{ strtoupper(substr($isp->name, 0, 2)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.isps.show', $isp) }}" class="truncate text-sm font-semibold text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-300">
                            {{ $isp->name }}
                        </a>
                        @if ($isp->is_active)
                            <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700 dark:text-emerald-300">Active</span>
                        @else
                            <span class="rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-600 dark:text-rose-300">Inactive</span>
                        @endif
                    </div>
                    <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $isp->company_name }}</p>
                </div>
                <div class="hidden shrink-0 grid grid-cols-3 gap-3 text-center text-[10px] sm:grid">
                    <div>
                        <p class="font-bold tabular-nums text-slate-800 dark:text-slate-200">{{ number_format($isp->customers_count) }}</p>
                        <p class="text-slate-400">Customers</p>
                    </div>
                    <div>
                        <p class="font-bold tabular-nums text-slate-800 dark:text-slate-200">{{ number_format($isp->routers_count) }}</p>
                        <p class="text-slate-400">Routers</p>
                    </div>
                    <div>
                        <p class="font-bold tabular-nums text-slate-800 dark:text-slate-200">{{ number_format($isp->plans_count) }}</p>
                        <p class="text-slate-400">Plans</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <a href="{{ route('admin.isps.show', $isp) }}" class="rounded-lg px-2 py-1 text-[10px] font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Portal</a>
                    <a href="{{ route('admin.isps.edit', $isp) }}" class="rounded-lg px-2 py-1 text-[10px] font-semibold text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-500/10">Config</a>
                </div>
            </div>
        @empty
            <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No tenants onboarded yet.</p>
        @endforelse
    </div>
</section>
