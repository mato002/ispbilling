@php
    /** @var list<array<string, mixed>> $modules */
    $moduleNav = app(\App\Support\Navigation\ModuleWorkspaceNav::class);
@endphp

<div class="pb-sidebar-modules">
    @foreach ($modules as $module)
        @php
            $isActive = $moduleNav->isModuleActive($module);
            $href = route($module['route']);
            $activeRoutes = implode(',', $module['active'] ?? [$module['route']]);
            $useTurbo = ($module['key'] ?? '') === 'settings';
        @endphp
        <a href="{{ $href }}"
           @if ($useTurbo)
               data-turbo-frame="pb-main"
               data-turbo-action="advance"
           @endif
           @click="$store.sidebar.closeMobile()"
           data-nav-active-routes="{{ $activeRoutes }}"
           class="pb-nav-item {{ $isActive ? 'is-active' : '' }}"
           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
           :title="$store.sidebar.collapsed ? '{{ $module['label'] }}' : ''">
            @include('layouts.partials.sidebar-module-icon', ['key' => $module['key'] ?? ''])
            <span x-show="!$store.sidebar.collapsed">{{ $module['label'] }}</span>
        </a>
    @endforeach
</div>
