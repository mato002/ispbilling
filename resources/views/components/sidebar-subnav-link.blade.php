@props([
    'href',
    'label',
    'routes',
])

<a
    href="{{ $href }}"
    data-nav-active-routes="{{ collect(\Illuminate\Support\Arr::wrap($routes))->implode(',') }}"
    {{ $attributes->merge(['class' => 'pb-subnav-item']) }}
>
    {{ $label }}
</a>
