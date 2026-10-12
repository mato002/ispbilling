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

@if (count($options) > 8)
    <x-searchable-select :name="$name" :label="$label" :options="$options" :placeholder="$placeholder ?? 'All'" :value="$value" :class="$class" {{ $attributes }} />
@else
    <div @class([$class]) {{ $attributes }}>
        <label class="isp-form-label">{{ $label }}</label>
        <select name="{{ $name }}" class="isp-form-select">
            @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    </div>
@endif
