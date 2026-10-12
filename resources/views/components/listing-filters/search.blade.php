@props([
    'name' => 'search',
    'label' => 'Search',
    'placeholder' => 'Search…',
    'value' => null,
    'class' => 'min-w-[200px] flex-1',
])

@php
    $value ??= request($name);
@endphp

<div @class([$class])>
    <label class="isp-form-label">{{ $label }}</label>
    <input
        type="search"
        name="{{ $name }}"
        value="{{ $value }}"
        autocomplete="off"
        autocorrect="off"
        spellcheck="false"
        class="isp-form-input"
        placeholder="{{ $placeholder }}"
        {{ $attributes->except(['name', 'label', 'placeholder', 'value', 'class']) }}
    >
</div>
