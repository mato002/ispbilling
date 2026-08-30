@props([
    'options' => [10, 15, 25, 50],
    'default' => 15,
    'class' => 'min-w-[110px]',
])

<x-listing-filters.select
    name="per_page"
    label="Per page"
    :class="$class"
    :options="collect($options)->mapWithKeys(fn ($n) => [$n => (string) $n])->all()"
    :value="request('per_page', $default)"
/>
