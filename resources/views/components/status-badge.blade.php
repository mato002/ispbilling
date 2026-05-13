@props(['status' => 'inactive'])

@php
    $key = strtolower((string) $status);
    $label = ucfirst(str_replace('_', ' ', (string) $status));
    $class = match ($key) {
        'active', 'paid' => 'pb-pill pb-pill-ok',
        'pending' => 'pb-pill pb-pill-warn',
        'expired' => 'pb-pill pb-pill-bad',
        'suspended', 'overdue' => 'pb-pill pb-pill-bad',
        'online' => 'pb-pill pb-pill-ok',
        'offline', 'inactive' => 'pb-pill',
        'hotspot' => 'pb-pill pb-pill-cyan',
        'pppoe' => 'pb-pill pb-pill-info',
        default => 'pb-pill',
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    {{ $label }}
</span>
