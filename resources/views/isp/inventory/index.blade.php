@extends('layouts.admin')

@section('title', 'Inventory')

@section('header')
    <x-isp-page-header
        title="Inventory"
        primaryLabel="Add item"
        :primaryHref="route('isp.inventory.create')"
    />
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    @php
        $inventorySummary = [
            ['label' => 'Available', 'value' => $stockCounts['available'], 'class' => 'text-emerald-600 dark:text-emerald-400'],
            ['label' => 'Assigned', 'value' => $stockCounts['assigned'], 'class' => 'text-indigo-600 dark:text-indigo-400'],
            ['label' => 'Faulty', 'value' => $stockCounts['faulty'], 'class' => 'text-rose-600 dark:text-rose-400'],
            ['label' => 'Retired', 'value' => $stockCounts['retired'], 'class' => 'text-slate-500 dark:text-slate-400'],
        ];
    @endphp
    <div x-data="{ summaryOpen: false }" @keydown.escape.window="summaryOpen = false">
        <div class="mb-3 flex justify-end sm:hidden">
            <button type="button" class="pb-btn pb-btn-sm" @click="summaryOpen = true" aria-haspopup="dialog">
                Summary
                <span class="ml-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs dark:bg-slate-800">{{ number_format($stockCounts['total']) }}</span>
            </button>
        </div>

        <section aria-label="Inventory summary" class="mb-4 hidden grid-cols-2 gap-3 sm:grid lg:grid-cols-4">
            @foreach ($inventorySummary as $stat)
                <div class="pb-card flex items-center justify-between gap-3 px-4 py-3">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $stat['label'] }}</p>
                    <p class="text-xl font-bold tabular-nums {{ $stat['class'] }}">{{ number_format($stat['value']) }}</p>
                </div>
            @endforeach
        </section>

        @if ($lowStockAlerts->isNotEmpty())
            <div class="mb-4 hidden flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs sm:flex dark:border-amber-800/50 dark:bg-amber-950/30">
                <span class="font-semibold text-amber-900 dark:text-amber-200">Low stock:</span>
                <ul class="flex flex-wrap gap-1.5 text-amber-800 dark:text-amber-300">
                    @foreach ($lowStockAlerts as $alert)
                        <li class="rounded-full bg-amber-100 px-2.5 py-1 dark:bg-amber-900/40">{{ $alert['label'] }} {{ $alert['available'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div x-cloak x-show="summaryOpen" x-transition.opacity class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:hidden" @click.self="summaryOpen = false">
            <section x-show="summaryOpen" x-transition class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900" role="dialog" aria-modal="true" aria-labelledby="inventory-summary-title">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                    <h2 id="inventory-summary-title" class="font-semibold text-slate-900 dark:text-white">Inventory summary</h2>
                    <button type="button" class="pb-btn pb-btn-sm" aria-label="Close summary" @click="summaryOpen = false">Close</button>
                </div>
                <div class="grid grid-cols-2 gap-3 p-4">
                    @foreach ($inventorySummary as $stat)
                        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p>
                            <p class="mt-1 text-xl font-bold tabular-nums {{ $stat['class'] }}">{{ number_format($stat['value']) }}</p>
                        </div>
                    @endforeach
                </div>
                @if ($lowStockAlerts->isNotEmpty())
                    <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                        <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">Low stock</h3>
                        <ul class="mt-2 flex flex-wrap gap-2 text-xs text-amber-800 dark:text-amber-300">
                            @foreach ($lowStockAlerts as $alert)
                                <li class="rounded-full bg-amber-100 px-2.5 py-1 dark:bg-amber-900/40">{{ $alert['label'] }} {{ $alert['available'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        </div>
    </div>

    <x-resource-index
        :filter-action="route('isp.inventory.index')"
        :filter-chips="$filterChips ?? []"
        :paginator="$items"
        :empty="$items->isEmpty()"
        empty-title="No inventory items"
        empty-message="Add equipment to start tracking stock and customer assignments."
        empty-cta-label="Add item"
        :empty-cta-href="route('isp.inventory.create')"
    >
        <x-slot:filters>
            <x-listing-filters.search name="q" placeholder="Serial, name, brand…" />
            <x-listing-filters.select name="status" label="Status" placeholder="All" :options="\App\Support\InventoryCatalog::STATUSES" />
            <x-listing-filters.select name="category" label="Category" placeholder="All" :options="\App\Support\InventoryCatalog::CATEGORIES" />
            <x-listing-filters.select name="location" label="Location" placeholder="All" :options="\App\Support\InventoryCatalog::LOCATIONS" />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th class="w-16">QR</th>
                        <th>Item</th>
                        <th>Serial</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th>Customer</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <x-listing-table.row :href="route('isp.inventory.show', $item)">
                            <td onclick="event.stopPropagation()">
                                @include('isp.inventory.partials.qr-code', ['item' => $item, 'size' => 44, 'link' => true])
                            </td>
                            <td>
                                <a href="{{ route('isp.inventory.show', $item) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">
                                    {{ $item->name }}
                                </a>
                                @if ($item->brand || $item->model)
                                    <p class="text-xs text-slate-500">{{ trim($item->brand.' '.$item->model) }}</p>
                                @endif
                            </td>
                            <td class="font-mono text-sm">{{ $item->serial_number }}</td>
                            <td>{{ $item->categoryLabel() }}</td>
                            <td>
                                <x-status-badge :status="$item->status" />
                            </td>
                            <td>{{ $item->locationLabel() }}</td>
                            <td>{{ $item->activeAssignment?->customer?->full_name ?? '—' }}</td>
                            <x-table-actions>
                                <x-table-actions.link :href="route('isp.inventory.show', $item)" label="View" />
                                @if ($item->status !== 'assigned')
                                    <x-table-actions.link :href="route('isp.inventory.edit', $item)" label="Edit" />
                                @endif
                            </x-table-actions>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
