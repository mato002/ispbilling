@props([
    'href',
    'label',
    'danger' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'block px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 ' . ($danger
            ? 'text-rose-600 dark:text-rose-400'
            : 'text-slate-700 dark:text-slate-200'),
    ]) }}
>
    {{ $label }}
</a>
