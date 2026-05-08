@props([
    'title' => '',
    'message' => '',
    'href' => '#',
    'action' => 'Open',
    'severity' => 'warning',
    'icon',
])

@php
    $styles = [
        'danger' => 'border-rose-200 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-900/20 text-rose-700 dark:text-rose-200',
        'warning' => 'border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-900/20 text-amber-700 dark:text-amber-200',
        'info' => 'border-sky-200 bg-sky-50 dark:border-sky-900/40 dark:bg-sky-900/20 text-sky-700 dark:text-sky-200',
    ];
    $theme = $styles[$severity] ?? $styles['warning'];
@endphp

<article class="rounded-xl border p-4 {{ $theme }}">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3 min-w-0">
            <div class="h-9 w-9 rounded-lg bg-white/70 dark:bg-black/20 flex items-center justify-center shrink-0">
                {{ $icon }}
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold">{{ $title }}</h3>
                <p class="text-xs mt-1 opacity-90">{{ $message }}</p>
            </div>
        </div>
        <a href="{{ $href }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-white dark:bg-gray-900 text-xs font-medium text-gray-700 dark:text-gray-100 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
            {{ $action }}
        </a>
    </div>
</article>
