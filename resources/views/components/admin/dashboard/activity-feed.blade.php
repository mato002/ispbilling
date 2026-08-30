@props([
    'events' => collect(),
])

<section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
        <div class="flex items-center justify-between gap-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Telemetry</p>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Global security & activity</h3>
            </div>
            <a href="{{ route('admin.audit.activity') }}" class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400">Full audit →</a>
        </div>
    </div>

    <div class="pb-audit-stream max-h-[min(24rem,50vh)] overflow-y-auto px-3 py-3">
        @forelse ($events as $event)
            @php
                $severityClass = match ($event['severity']) {
                    'critical', 'danger' => 'text-rose-600 dark:text-rose-400',
                    'warning' => 'text-amber-700 dark:text-amber-300',
                    'success' => 'text-emerald-600 dark:text-emerald-400',
                    default => 'text-slate-600 dark:text-slate-300',
                };
            @endphp
            <a href="{{ $event['href'] }}" class="group block rounded-lg px-2 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <p class="text-[11px] leading-snug {{ $severityClass }}">
                    <span class="font-mono text-[10px] text-slate-400 dark:text-slate-500">[{{ $event['time'] }}]</span>
                    <span class="ml-1">{{ $event['line'] }}</span>
                </p>
            </a>
        @empty
            <p class="px-2 py-6 text-center text-xs text-slate-500 dark:text-slate-400">No audit events recorded yet.</p>
        @endforelse
    </div>
</section>
