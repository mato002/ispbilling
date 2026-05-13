@props([
    'title' => '',
    'message' => '',
    'href' => '#',
    'action' => 'Open',
    'severity' => 'warning',
    'timestamp' => null,
    'icon' => null,
])

@php
    $themes = [
        'danger' => [
            'border'  => 'border-rose-200 dark:border-rose-500/30',
            'bg'      => 'bg-rose-50/70 dark:bg-rose-500/10',
            'iconBg'  => 'bg-rose-500/15 text-rose-600 dark:text-rose-300',
            'accent'  => 'before:bg-rose-500',
            'badge'   => 'bg-rose-500 text-white',
            'label'   => 'CRITICAL',
        ],
        'warning' => [
            'border'  => 'border-amber-200 dark:border-amber-500/30',
            'bg'      => 'bg-amber-50/70 dark:bg-amber-500/10',
            'iconBg'  => 'bg-amber-500/15 text-amber-600 dark:text-amber-300',
            'accent'  => 'before:bg-amber-500',
            'badge'   => 'bg-amber-500 text-white',
            'label'   => 'WARNING',
        ],
        'info' => [
            'border'  => 'border-sky-200 dark:border-sky-500/30',
            'bg'      => 'bg-sky-50/70 dark:bg-sky-500/10',
            'iconBg'  => 'bg-sky-500/15 text-sky-600 dark:text-sky-300',
            'accent'  => 'before:bg-sky-500',
            'badge'   => 'bg-sky-500 text-white',
            'label'   => 'INFO',
        ],
        'success' => [
            'border'  => 'border-emerald-200 dark:border-emerald-500/30',
            'bg'      => 'bg-emerald-50/70 dark:bg-emerald-500/10',
            'iconBg'  => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300',
            'accent'  => 'before:bg-emerald-500',
            'badge'   => 'bg-emerald-500 text-white',
            'label'   => 'OK',
        ],
    ];
    $t = $themes[$severity] ?? $themes['warning'];
@endphp

<article class="relative overflow-hidden rounded-2xl border {{ $t['border'] }} {{ $t['bg'] }} p-4 pl-5 before:absolute before:left-0 before:top-3 before:bottom-3 before:w-1 before:rounded-r {{ $t['accent'] }}">
    <div class="flex items-start gap-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $t['iconBg'] }} shrink-0">
            {{ $icon }}
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold tracking-wider {{ $t['badge'] }}">{{ $t['label'] }}</span>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">{{ $title }}</h3>
            </div>
            @if($message)
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ $message }}</p>
            @endif
            <div class="mt-2.5 flex items-center justify-between gap-3">
                @if($timestamp)
                    <span class="inline-flex items-center gap-1 text-[10px] text-slate-500 dark:text-slate-400">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        {{ $timestamp }}
                    </span>
                @else
                    <span></span>
                @endif
                <a href="{{ $href }}" class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-indigo-500/40 dark:hover:text-indigo-300">
                    {{ $action }}
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</article>
