@props(['action' => '', 'method' => 'GET'])

<div class="pb-card p-4 sm:p-5">
    <form action="{{ $action }}" method="{{ $method }}" {{ $attributes->merge(['class' => 'isp-filter-bar']) }}>
        {{ $slot }}
    </form>
</div>
