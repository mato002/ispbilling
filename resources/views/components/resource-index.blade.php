@props([
    'filterAction' => null,
    'filterMethod' => 'GET',
    'resetUrl' => null,
    'exportUrl' => null,
    'exportLabel' => 'Export CSV',
    'exportFormats' => [],
    'paginator' => null,
    'empty' => false,
    'emptyTitle' => 'No records found',
    'emptyMessage' => 'Try adjusting your filters or create a new record.',
    'emptyCtaLabel' => null,
    'emptyCtaHref' => null,
    'showFlash' => true,
    'filterChips' => [],
    'filterAutoSearch' => true,
])

@php
    $filterChips = $filterChips ?? [];
    $exportFormats = $exportFormats ?? [];
@endphp

<div data-listing-root {{ $attributes->merge(['class' => 'space-y-6']) }}>
    @if ($showFlash && session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($filterAction)
        <x-filter-bar
            :action="$filterAction"
            :method="$filterMethod"
            :reset-url="$resetUrl ?? $filterAction"
            :export-url="$exportUrl"
            :export-label="$exportLabel"
            :export-formats="$exportFormats"
            :auto-search="$filterAutoSearch"
            :chips="$filterChips"
        >
            @isset($filters)
                <x-slot:filters>{{ $filters }}</x-slot:filters>
            @endisset
            @isset($exports)
                <x-slot:exports>{{ $exports }}</x-slot:exports>
            @endisset
        </x-filter-bar>
    @elseif (isset($filters))
        {{ $filters }}
    @endif

    @if ($empty)
        @isset($emptySlot)
            {{ $emptySlot }}
        @else
            <div class="pb-card p-0">
                <x-empty-state
                    :title="$emptyTitle"
                    :message="$emptyMessage"
                    :cta-label="$emptyCtaLabel"
                    :cta-href="$emptyCtaHref"
                />
            </div>
        @endisset
    @else
        @isset($table)
            {{ $table }}
        @endisset

        @if ($paginator)
            <div class="pb-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing
                    <span data-listing-from class="font-medium text-slate-700 dark:text-slate-200">{{ $paginator->firstItem() ?? 0 }}</span>
                    –
                    <span data-listing-to class="font-medium text-slate-700 dark:text-slate-200">{{ $paginator->lastItem() ?? 0 }}</span>
                    of
                    <span data-listing-total class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($paginator->total()) }}</span>
                </p>
                @if ($paginator->hasPages())
                    <div class="pb-pagination" data-listing-pagination>{{ $paginator->withQueryString()->links() }}</div>
                @else
                    <div class="pb-pagination" data-listing-pagination></div>
                @endif
            </div>
        @endif
    @endif

    @isset($footer)
        {{ $footer }}
    @endisset
</div>
