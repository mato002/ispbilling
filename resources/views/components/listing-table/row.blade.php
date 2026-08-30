@props([
    'href' => null,
    'target' => null,
])

<tr
    @if ($href)
        class="isp-table-row--clickable"
        data-row-href="{{ $href }}"
        @if ($target) data-row-target="{{ $target }}" @endif
        tabindex="0"
    @endif
    {{ $attributes }}
>
    {{ $slot }}
</tr>
