@props([
    'scope' => null,
])

@php
    /** @var \App\Support\Navigation\ModuleWorkspaceNav $nav */
    $nav = app(\App\Support\Navigation\ModuleWorkspaceNav::class);
    $scope = $scope ?? $nav->scopeForUser();
    $module = $nav->currentModule($scope);
    $tabs = $nav->workspaceTabs($scope);
@endphp

@if ($module && $tabs !== [])
    <nav
        class="pb-module-workspace pb-module-workspace--dock pb-fade-up"
        aria-label="{{ __(':module workspace', ['module' => $module['label']]) }}"
    >
        <div class="pb-module-workspace__head">
            <span class="pb-module-workspace__label">{{ $module['label'] }}</span>
        </div>
        <div class="pb-module-workspace__scroll">
            @foreach ($tabs as $item)
                @php
                    $isActive = $nav->isTabActive($item);
                    $href = $nav->hrefForItem($item);
                @endphp
                <a
                    href="{{ $href }}"
                    data-turbo-frame="pb-main"
                    data-turbo-action="advance"
                    @class([
                        'pb-module-workspace__tab',
                        'is-active' => $isActive,
                    ])
                    @if ($isActive) aria-current="page" @endif
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
@endif
