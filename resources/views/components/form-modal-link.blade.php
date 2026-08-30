@props([
    'href',
    'variant' => 'primary',
])

@php
    $classes = match ($variant) {
        'secondary' => 'pb-btn pb-btn-secondary',
        default => 'pb-btn pb-btn-primary',
    };
@endphp

<a
    href="{{ $href }}"
    data-pb-modal-open
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</a>
