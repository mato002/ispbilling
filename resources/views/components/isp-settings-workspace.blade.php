@props([
    'metaTitle' => null,
    'metaDescription' => null,
    'hideMeta' => true,
    'contentClass' => '',
    'showHealth' => false,
])

<div @class(['isp-settings-layout min-w-0 space-y-5', $contentClass])>
    {{ $slot }}

    @if ($showHealth)
        @include('isp.settings.partials.health-sidebar')
    @endif
</div>
