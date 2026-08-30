@props([
    'fields' => [],
    'name',
])

@php
    $cfg = $fields[$name] ?? ['visible' => true, 'required' => false, 'read_only' => false];
@endphp

@if ($cfg['visible'] ?? true)
    <div {{ $attributes }}>
        {{ $slot }}
    </div>
@endif
