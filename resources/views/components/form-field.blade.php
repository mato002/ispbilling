@props([
    'fields',
    'name',
    'label' => null,
    'type' => null,
    'value' => null,
    'required' => null,
    'readonly' => null,
])

@php
    $config = $fields[$name] ?? ['visible' => true, 'required' => false, 'read_only' => false, 'label' => $label ?? $name];
    $visible = (bool) ($config['visible'] ?? true);
    $fieldLabel = $label ?? ($config['label'] ?? $name);
    $isRequired = $required ?? (bool) ($config['required'] ?? false);
    $isReadonly = $readonly ?? (bool) ($config['read_only'] ?? false);
    $fieldType = $type ?? ($config['type'] ?? 'text');
    $fieldValue = $value ?? old($name, $config['default'] ?? '');
@endphp

@if ($visible)
    <div {{ $attributes->merge(['class' => '']) }}>
        <label class="isp-form-label" for="{{ $name }}">
            {{ $fieldLabel }}
            @if ($isRequired)<span class="text-rose-500">*</span>@endif
        </label>
        {{ $slot }}
    </div>
@endif
