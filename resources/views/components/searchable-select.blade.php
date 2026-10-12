@props([
    'name',
    'label' => null,
    'options' => [],
    'placeholder' => 'Select an option',
    'value' => null,
    'class' => 'w-full',
])

@php
    $normalizedOptions = collect($options)->map(fn ($optionLabel, $optionValue) => [
        'value' => (string) $optionValue,
        'label' => (string) $optionLabel,
    ])->values();
    $value = (string) ($value ?? request($name, ''));
    $selectedLabel = $normalizedOptions->firstWhere('value', $value)['label'] ?? '';
@endphp

<div @class([$class]) {{ $attributes }} x-data="pbSearchableSelect({ options: {{ Js::from($normalizedOptions) }}, selected: {{ Js::from($value) }}, placeholder: {{ Js::from($placeholder) }} })" @click.outside="open = false" @keydown.escape.stop="open = false">
    @if ($label)<label class="isp-form-label">{{ $label }}</label>@endif
    <div class="relative">
        <input type="hidden" name="{{ $name }}" :value="selected">
        <input type="text" value="{{ $selectedLabel }}" x-model="query" @focus="open = true" @input="search()" @keydown.arrow-down.prevent="highlightNext()" @keydown.arrow-up.prevent="highlightPrevious()" @keydown.enter.prevent="chooseHighlighted()" autocomplete="off" data-filter-search-only role="combobox" aria-autocomplete="list" :aria-expanded="open.toString()" class="isp-form-select pr-10" placeholder="{{ $placeholder }}">
        <button type="button" @click="open = !open; if (open) $nextTick(() => $el.previousElementSibling.focus())" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400" aria-label="Show options"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button>
        <div x-cloak x-show="open" x-transition class="absolute left-0 right-0 top-full z-[70] mt-1 max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl dark:border-slate-700 dark:bg-slate-900" role="listbox">
            <button type="button" @click="select('', '')" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800">{{ $placeholder }}</button>
            <template x-for="(option, index) in filteredOptions" :key="option.value">
                <button type="button" @mouseenter="highlighted = index" @click="select(option.value, option.label)" :class="highlighted === index ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200' : 'text-slate-700 dark:text-slate-200'" class="block w-full rounded-lg px-3 py-2 text-left text-sm" role="option" :aria-selected="selected === option.value" x-text="option.label"></button>
            </template>
            <p x-show="filteredOptions.length === 0" class="px-3 py-3 text-sm text-slate-500">No matching options.</p>
        </div>
    </div>
</div>
