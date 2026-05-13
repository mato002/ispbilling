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

<{!! $tag !!} {!! $hrefAttr !!} class="pb-card pb-card-hover pb-fade-up block p-5">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br {{ $iconBg }} text-white shadow-[0_8px_18px_-8px_rgba(99,102,241,0.55),inset_0_1px_0_rgba(255,255,255,0.18)] shrink-0">
                {{ $icon }}
            </span>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400 truncate">{{ $title }}</p>
                @if($badge)
                    <span class="mt-0.5 inline-block text-[10px] font-medium text-slate-400 dark:text-slate-500 truncate">{{ $badge }}</span>
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

    <div class="mt-4 flex items-end justify-between gap-3">
        <div class="min-w-0">
            <p @if($valueId) id="{{ $valueId }}" @endif class="text-[26px] font-extrabold leading-none tracking-tight text-slate-900 dark:text-white truncate">
                {{ $value }}
            </p>
            @if($subtext)
                <p class="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $subtext }}</p>
            @endif
        </div>

        @if($spark !== null)
            <div class="shrink-0 -mb-1">
                <x-dashboard.sparkline :data="$spark" :stroke="$sparkColor" :fill="$sparkFill" />
            </div>
        @endif
    </div>
</{!! $tag !!}>
