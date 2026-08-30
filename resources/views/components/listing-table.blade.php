@props([
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'pb-card p-0']) }}>
    <div @class(['isp-table-wrap', 'pb-customers-table--compact' => $compact])>
        <table class="isp-table">
            {{ $slot }}
        </table>
    </div>
</div>
