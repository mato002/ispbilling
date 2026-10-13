@props([
    'name',
    'title' => null,
])

<svg
    {{ $attributes->class(['inline-block shrink-0'])->merge([
        'aria-hidden' => $title ? 'false' : 'true',
        'role' => $title ? 'img' : null,
        'focusable' => 'false',
    ]) }}
>
    @if ($title)
        <title>{{ $title }}</title>
    @endif
    <use href="{{ asset('icons/fontawesome.svg') }}#fa-{{ preg_replace('/[^a-z0-9-]/', '', $name) }}"></use>
</svg>
