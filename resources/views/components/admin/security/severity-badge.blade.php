@props([
    'tier' => 'info',
    'severity' => null,
])

@php
    $resolvedTier = match (strtolower((string) ($severity ?? $tier))) {
        'critical', 'danger' => 'critical',
        'warning' => 'warning',
        default => 'info',
    };

    $classes = match ($resolvedTier) {
        'critical' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-200',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-100',
        default => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-300',
    };

    $label = match ($resolvedTier) {
        'critical' => '🔴 CRITICAL',
        'warning' => '🟡 WARNING',
        default => 'INFO',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-semibold {$classes}"]) }}>
    {{ $label }}
</span>
