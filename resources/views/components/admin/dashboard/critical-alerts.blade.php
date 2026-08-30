@props([
    'alerts' => [],
])

<section class="rounded-2xl border border-rose-200/80 bg-gradient-to-br from-rose-50/90 via-white to-amber-50/40 shadow-sm dark:border-rose-500/25 dark:from-rose-950/40 dark:via-slate-900 dark:to-amber-950/20">
    <div class="border-b border-rose-100/80 px-4 py-3 dark:border-rose-500/20">
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-300">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </span>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Critical exceptions</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Requires immediate platform action</p>
            </div>
        </div>
    </div>

    <ul class="space-y-2 px-3 py-3">
        @forelse ($alerts as $alert)
            @php
                $isCritical = $alert['severity'] === 'critical';
            @endphp
            <li>
                @if ($alert['href'])
                    <a href="{{ $alert['href'] }}" class="flex items-start gap-2 rounded-xl border px-3 py-2.5 transition {{ $isCritical ? 'border-rose-200/80 bg-white/80 hover:border-rose-300 dark:border-rose-500/30 dark:bg-slate-900/60' : 'border-amber-200/80 bg-white/60 hover:border-amber-300 dark:border-amber-500/20 dark:bg-slate-900/40' }}">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $isCritical ? 'bg-rose-500 pb-noc-dot--pulse' : 'bg-amber-500' }}" aria-hidden="true"></span>
                        <span class="text-xs font-medium leading-snug text-slate-800 dark:text-slate-200">{{ $alert['message'] }}</span>
                    </a>
                @else
                    <div class="flex items-start gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900/40">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-slate-400" aria-hidden="true"></span>
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $alert['message'] }}</span>
                    </div>
                @endif
            </li>
        @empty
            <li class="rounded-xl border border-emerald-200/60 bg-emerald-50/50 px-3 py-4 text-center text-xs text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">
                No critical platform exceptions right now.
            </li>
        @endforelse
    </ul>
</section>
