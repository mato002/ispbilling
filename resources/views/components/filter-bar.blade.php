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

<div class="pb-card p-4 sm:p-5" {{ $attributes->except(['action', 'method', 'resetUrl', 'exportUrl', 'exportLabel', 'toolbar', 'autoSearch', 'chips']) }}>
    <form
        action="{{ $action }}"
        method="{{ $method }}"
        class="flex flex-col gap-4"
        data-turbo-frame="pb-main"
        x-data="pbFilterForm({ autoSearch: @json($autoSearch) })"
    >
        <div class="isp-filter-bar flex flex-wrap items-end gap-3">
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
                @endunless
                @if ($resetUrl)
                    <a href="{{ $resetUrl }}" class="pb-btn" data-turbo-frame="pb-main">Reset</a>
                @endif
                @isset($exports)
                    {{ $exports }}
                @elseif (count($exportFormats) > 0)
                    <x-export-dropdown :formats="$exportFormats" />
                @elseif ($exportUrl)
                    <a href="{{ $exportUrl }}" class="pb-btn" data-turbo="false" download>
                        <svg class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        {{ $exportLabel }}
                    </a>
                @endisset
            </div>
        @endif
    </form>
</div>
