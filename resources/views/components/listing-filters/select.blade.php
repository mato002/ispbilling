@props([
    'name',
    'label',
    'options' => [],
    'placeholder' => null,
    'value' => null,
    'class' => 'min-w-[140px]',
])

@php
    $value ??= request($name);
@endphp

<div @class([$class])>
    <label class="isp-form-label">{{ $label }}</label>
    <select name="{{ $name }}" class="isp-form-select" {{ $attributes->except(['name', 'label', 'options', 'placeholder', 'value', 'class']) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
