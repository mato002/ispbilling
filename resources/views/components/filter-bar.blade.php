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

<div class="pb-card p-3 sm:p-4" {{ $attributes->except(['action', 'method', 'resetUrl', 'exportUrl', 'exportLabel', 'toolbar', 'autoSearch', 'chips']) }}>
    <form
        action="{{ $action }}"
        method="{{ $method }}"
        class="flex flex-col gap-2"
        data-turbo-frame="pb-main"
        x-data="pbFilterForm({ autoSearch: @json($autoSearch) })"
    >
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
    </form>
</div>
