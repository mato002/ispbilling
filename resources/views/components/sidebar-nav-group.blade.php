@props([
    'label',
    'href',
    'routes' => [],
    'open' => false,
])

@php
    $groupRoutes = collect($routes)->filter()->values();
    $groupActive = $groupRoutes->contains(fn (string $pattern) => request()->routeIs($pattern));
    $shouldOpen = $open || $groupActive;
@endphp

<div
    class="pb-nav-group"
    x-data="pbNavGroup(@js($shouldOpen))"
    data-nav-group-routes="{{ $groupRoutes->implode(',') }}"
>
    <div class="flex items-stretch gap-0.5">
        <a
            href="{{ $href }}"
            data-nav-group-trigger
            data-nav-active-routes="{{ $groupRoutes->implode(',') }}"
            class="pb-nav-item min-w-0 flex-1 {{ $groupActive ? 'is-active' : '' }}"
            :class="$store.sidebar.collapsed ? 'justify-center' : ''"
            :title="$store.sidebar.collapsed ? @js($label) : ''"
        >
            @if (isset($icon))
                {{ $icon }}
            @endif
            <span class="truncate" x-show="!$store.sidebar.collapsed">{{ $label }}</span>
        </a>
        <button
            type="button"
            class="hidden shrink-0 items-center justify-center rounded-lg px-1.5 text-slate-400 transition hover:bg-white/5 hover:text-white md:inline-flex"
            x-show="!$store.sidebar.collapsed"
            x-cloak
            @click.prevent="toggle()"
            :aria-expanded="open.toString()"
            aria-label="{{ __('Toggle :label menu', ['label' => $label]) }}"
        >
            <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>
    </div>

    <div
        x-show="open && ! $store.sidebar.collapsed"
        x-cloak
        class="mt-0.5 space-y-0.5"
    >
        {{ $slot }}
    </div>
</div>
