@props([
    'name',
    'label',
    'value' => null,
    'class' => 'min-w-[130px]',
])

@php
    $value ??= request($name);
@endphp

<div @class([$class])>
    <label class="isp-form-label">{{ $label }}</label>
    <input type="date" name="{{ $name }}" value="{{ $value }}" class="isp-form-input" {{ $attributes }}>
</div>
