@extends('layouts.admin')

@section('title', 'Inventory')

@section('header')
    <x-isp-page-header
        title="Inventory"
        module="Operations"
        description="Track ISP equipment from warehouse to customer installation — routers, CPE, access points, switches, and radios."
        primaryLabel="Add item"
        :primaryHref="route('isp.inventory.create')"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.inventory.scan') }}" class="pb-btn">Scan device</a>
            <a href="{{ route('isp.inventory.assignments.create') }}" class="pb-btn">Assign device</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    @if ($lowStockAlerts->isNotEmpty())
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-950/30">
            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Low stock alerts</p>
            <ul class="mt-2 flex flex-wrap gap-2 text-xs text-amber-800 dark:text-amber-300">
                @foreach ($lowStockAlerts as $alert)
                    <li class="rounded-full bg-amber-100 px-3 py-1 dark:bg-amber-900/40">
                        {{ $alert['label'] }}: {{ $alert['available'] }} available (threshold {{ $alert['threshold'] }})
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('isp.inventory.thresholds') }}" class="mt-2 inline-block text-xs font-medium text-amber-900 underline dark:text-amber-200">Adjust thresholds</a>
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['label' => 'Available', 'value' => $stockCounts['available'], 'class' => 'text-emerald-600'],
            ['label' => 'Assigned', 'value' => $stockCounts['assigned'], 'class' => 'text-indigo-600'],
            ['label' => 'Faulty', 'value' => $stockCounts['faulty'], 'class' => 'text-rose-600'],
            ['label' => 'Retired', 'value' => $stockCounts['retired'], 'class' => 'text-slate-500'],
            ['label' => 'Total', 'value' => $stockCounts['total'], 'class' => 'text-slate-900 dark:text-white'],
        ] as $stat)
            <div class="pb-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $stat['class'] }}">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
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
