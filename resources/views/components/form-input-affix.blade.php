@props([
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'prefix' => null,
    'suffix' => null,
    'unit' => null,
    'placeholder' => null,
    'required' => false,
    'min' => null,
    'max' => null,
    'step' => null,
    'pattern' => null,
])

@php
    $inputId = $id ?? $name;
    $resolvedValue = $value ?? old($name);
    $hasPrefix = filled($prefix);
    $hasSuffixBadge = filled($suffix);
    $hasUnit = filled($unit);
@endphp

<div {{ $attributes->merge(['class' => 'pb-input-affix group']) }}>
    <div @class([
        'flex w-full overflow-hidden rounded-xl border border-slate-200 bg-white transition-all duration-150 dark:border-slate-600 dark:bg-slate-900/60',
        'focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20',
    ])>
        @if ($hasPrefix)
            <span class="inline-flex shrink-0 items-center border-r border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-600 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-300">
                {{ $prefix }}
            </span>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $inputId }}"
            @if ($resolvedValue !== null && $resolvedValue !== '') value="{{ $resolvedValue }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required @endif
            @if ($min !== null) min="{{ $min }}" @endif
            @if ($max !== null) max="{{ $max }}" @endif
            @if ($step !== null) step="{{ $step }}" @endif
            @if ($pattern) pattern="{{ $pattern }}" @endif
            @class([
                'min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500',
                'pr-2' => $hasUnit && ! $hasSuffixBadge,
            ])
        >

        @if ($hasSuffixBadge)
            <span class="inline-flex shrink-0 items-center border-l border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-600 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-300">
                {{ $suffix }}
            </span>
        @elseif ($hasUnit)
            <span class="inline-flex shrink-0 items-center pr-3 text-xs font-medium text-slate-400 dark:text-slate-500">
                {{ $unit }}
            </span>
        @endif
    </div>
</div>
