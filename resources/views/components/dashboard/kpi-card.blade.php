@props([
    'title' => '',
    'value' => 0,
    'valueId' => null,
    'subtext' => '',
    'comparison' => null,
    'trend' => 'neutral',
    'badge' => null,
    'iconBg' => 'from-indigo-500 to-cyan-500',
    'icon' => null,
    'spark' => null,
    'sparkColor' => '#6366f1',
    'sparkFill' => 'rgba(99, 102, 241, 0.14)',
    'href' => null,
])

@php
    $trendStyles = [
        'up' => ['color' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/20'],
        'down' => ['color' => 'text-rose-600 dark:text-rose-400', 'bg' => 'bg-rose-500/10', 'border' => 'border-rose-500/20'],
        'neutral' => ['color' => 'text-slate-500 dark:text-slate-400', 'bg' => 'bg-slate-500/10', 'border' => 'border-slate-500/20'],
    ];
    $tStyle = $trendStyles[$trend] ?? $trendStyles['neutral'];

    $tag = $href ? 'a' : 'div';
    $hrefAttr = $href ? 'href="' . e($href) . '"' : '';
@endphp

<{!! $tag !!} {!! $hrefAttr !!} class="pb-card pb-card-hover pb-fade-up block min-w-0 p-3 sm:p-5">
    <div class="flex items-start justify-between gap-2 sm:gap-3">
        <div class="flex items-center gap-2 min-w-0 sm:gap-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br {{ $iconBg }} text-white shadow-[0_8px_18px_-8px_rgba(99,102,241,0.55),inset_0_1px_0_rgba(255,255,255,0.18)] shrink-0 sm:h-10 sm:w-10 sm:rounded-xl">
                {{ $icon }}
            </span>
            <div class="min-w-0">
                <p class="text-[9px] font-bold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400 truncate sm:text-[10px] sm:tracking-[0.14em]">{{ $title }}</p>
                @if($badge)
                    <span class="mt-0.5 hidden text-[10px] font-medium text-slate-400 dark:text-slate-500 truncate sm:inline-block">{{ $badge }}</span>
                @endif
            </div>
        </div>

        @if($comparison !== null)
            <span class="inline-flex items-center gap-1 rounded-md border {{ $tStyle['border'] }} {{ $tStyle['bg'] }} px-1.5 py-0.5 text-[10px] font-bold {{ $tStyle['color'] }}">
                @if($trend === 'up')
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 15 12 9 18 15"/>
                    </svg>
                @elseif($trend === 'down')
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                @endif
                {{ $comparison }}
            </span>
        @endif
    </div>

    <div class="mt-2 flex items-end justify-between gap-2 sm:mt-4 sm:gap-3">
        <div class="min-w-0">
            <p @if($valueId) id="{{ $valueId }}" @endif class="text-[22px] font-extrabold leading-none tracking-tight text-slate-900 dark:text-white truncate sm:text-[26px]">
                {{ $value }}
            </p>
            @if($subtext)
                <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400 truncate sm:mt-1.5 sm:text-[11px]">{{ $subtext }}</p>
            @endif
        </div>

        @if($spark !== null)
            <div class="hidden shrink-0 -mb-1 sm:block">
                <x-dashboard.sparkline :data="$spark" :stroke="$sparkColor" :fill="$sparkFill" />
            </div>
        @endif
    </div>
</{!! $tag !!}>
