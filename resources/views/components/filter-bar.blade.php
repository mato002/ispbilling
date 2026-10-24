@props([
    'action' => '',
    'method' => 'GET',
    'resetUrl' => null,
    'exportUrl' => null,
    'exportLabel' => 'Export CSV',
    'exportFormats' => [],
    /** When false, pages supply their own Apply / Reset / export controls in the filters slot. */
    'toolbar' => true,
    'autoSearch' => true,
    'chips' => [],
])

<div class="pb-card p-3 sm:p-4" x-data="{ filtersOpen: false }" @keydown.escape.window="filtersOpen = false" {{ $attributes->except(['action', 'method', 'resetUrl', 'exportUrl', 'exportLabel', 'toolbar', 'autoSearch', 'chips']) }}>
    <div class="mb-3 flex justify-end md:hidden">
        <button type="button" class="pb-btn" @click="filtersOpen = true">
            <x-font-awesome-icon name="filter" class="h-4 w-4" />
            Filter
        </button>
    </div>
    <div class="isp-filter-modal" role="dialog" aria-modal="true" :class="{ 'is-open': filtersOpen }" @click.self="filtersOpen = false">
    <form
        action="{{ $action }}"
        method="{{ $method }}"
        class="flex flex-col gap-2"
        @submit="filtersOpen = false"
        data-turbo-frame="pb-main"
        x-data="pbFilterForm({ autoSearch: @json($autoSearch) })"
    >
        <div class="isp-filter-modal-heading md:hidden">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Filters</h2>
            <button type="button" class="pb-btn px-2 py-1" aria-label="Close filters" @click="filtersOpen = false">✕</button>
        </div>
        <div class="isp-filter-bar flex flex-wrap items-end gap-2">
            @isset($filters)
                {{ $filters }}
            @else
                {{ $slot }}
            @endisset
        </div>

        @if (count($chips) > 0)
            <x-filter-chips :chips="$chips" />
        @endif

        @if ($toolbar)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
        @unless ($autoSearch)
                    <button type="submit" class="pb-btn pb-btn-primary">Apply filters</button>
                @endif
                @if ($resetUrl)
                    <a href="{{ $resetUrl }}" class="pb-btn" data-turbo-frame="pb-main">Reset</a>
                @endif
                @isset($exports)
                    {{ $exports }}
                @elseif (count($exportFormats) > 0)
                    <x-export-dropdown :formats="$exportFormats" />
                @elseif ($exportUrl)
                    <a href="{{ $exportUrl }}" class="pb-btn" data-turbo="false" download>
                        <x-font-awesome-icon name="download" class="h-4 w-4 opacity-70" />
                        {{ $exportLabel }}
                    </a>
                @endisset
            </div>
        @endif
        <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3 dark:border-slate-800 md:hidden">
            <button type="submit" class="pb-btn pb-btn-primary flex-1">Apply filters</button>
            <button type="button" class="pb-btn" @click="filtersOpen = false">Close</button>
        </div>
    </form>
    </div>
</div>
