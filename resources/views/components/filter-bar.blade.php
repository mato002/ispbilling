@props([
    'action' => '',
    'method' => 'GET',
    'resetUrl' => null,
    'exportUrl' => null,
    'exportLabel' => 'Export CSV',
    'exportFormats' => [],
    'toolbar' => true,
    'autoSearch' => true,
    'chips' => [],
])

@php
    $activeFilterCount = collect(request()->except(['search', 'q', 'page', 'per_page', 'export']))
        ->filter(fn ($value) => is_array($value) ? count(array_filter($value, fn ($item) => filled($item))) > 0 : filled($value))
        ->count();
@endphp

<div class="isp-resource-filter pb-card p-3 sm:p-4" x-data="{ filtersOpen: false }" x-init="$nextTick(() => { const bar = $el.querySelector('.isp-filter-bar'); const input = bar?.querySelector('input[type=search], input[name=q], input[name=search]'); if (input && bar) { let field = input; while (field.parentElement && field.parentElement !== bar) field = field.parentElement; $el.querySelector('.isp-filter-search-slot')?.append(field); } })" @keydown.escape.window="filtersOpen = false" {{ $attributes->except(['action', 'method', 'resetUrl', 'exportUrl', 'exportLabel', 'toolbar', 'autoSearch', 'chips']) }}>
    <form
        action="{{ $action }}"
        method="{{ $method }}"
        class="flex flex-col gap-2"
        @submit="filtersOpen = false"
        data-turbo-frame="pb-main"
        x-data="pbFilterForm({ autoSearch: @json($autoSearch) })"
    >
        <div class="isp-filter-search-row">
            @isset($heading)<div class="isp-filter-heading">{{ $heading }}</div>@endisset
            <div class="isp-filter-search-slot"></div>
            <button type="button" class="isp-filter-open pb-btn shrink-0" @click="filtersOpen = true" aria-label="Open filters">
                <x-font-awesome-icon name="filter" class="h-4 w-4" />
                <span>Filter</span>
                @if($activeFilterCount > 0)<span class="isp-filter-count">{{ $activeFilterCount }}</span>@endif
            </button>
            @isset($actions)
                <div class="isp-filter-actions">{{ $actions }}</div>
            @endisset
            @if ($toolbar)
                @if (count($exportFormats) > 0)
                    <div class="isp-filter-export"><x-export-dropdown :formats="$exportFormats" label="Export" /></div>
                @elseif ($exportUrl)
                    <a href="{{ $exportUrl }}" class="isp-filter-export pb-btn" data-turbo="false" download>
                        <x-font-awesome-icon name="download" class="h-4 w-4 opacity-70" />
                        {{ $exportLabel }}
                    </a>
                @endif
                @isset($exports){{ $exports }}@endisset
            @endif
        </div>

        <div class="isp-filter-modal" role="dialog" aria-modal="true" :class="{ 'is-open': filtersOpen }" @click.self="filtersOpen = false">
            <div class="isp-filter-modal-panel">
                <div class="isp-filter-modal-heading">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Filters</h2>
                    <button type="button" class="pb-btn px-2 py-1" aria-label="Close filters" @click="filtersOpen = false">✕</button>
                </div>
                <div class="isp-filter-bar">
                    @isset($filters)
                        {{ $filters }}
                    @else
                        {{ $slot }}
                    @endisset
                </div>

                @if (! $autoSearch && $toolbar)
                    <div class="isp-filter-apply-row">
                        <button type="submit" class="pb-btn pb-btn-primary">Apply filters</button>
                    </div>
                @endif

                @if (count($chips) > 0)
                    <x-filter-chips :chips="$chips" />
                @endif

                @if ($resetUrl && $toolbar)
                    <div class="isp-filter-reset-row">
                        <a href="{{ $resetUrl }}" class="pb-btn" data-turbo-frame="pb-main">Reset filters</a>
                    </div>
                @endif
                <div class="isp-filter-modal-footer">
                    @if ($toolbar)<button type="submit" class="pb-btn pb-btn-primary flex-1">Apply filters</button>@endif
                    <button type="button" class="pb-btn flex-1" @click="filtersOpen = false">Close</button>
                </div>
            </div>
        </div>
    </form>
</div>
