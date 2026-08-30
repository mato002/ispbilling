@extends('layouts.admin')

@section('title', 'Faulty Devices')

@section('header')
    <x-isp-page-header
        title="Faulty devices"
        module="Inventory"
        description="Equipment marked as damaged or defective. Repair or retire before re-assigning."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.inventory.index') }}" class="pb-btn">All inventory</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <x-resource-index
        :filter-action="route('isp.inventory.faulty')"
        :filter-chips="[]"
        :paginator="$items"
        :empty="$items->isEmpty()"
        empty-title="No faulty devices"
        empty-message="Faulty equipment will appear here when marked from the inventory list or assignment screen."
        empty-cta-label="View inventory"
        :empty-cta-href="route('isp.inventory.index')"
    >
        <x-slot:filters>
            <x-listing-filters.search name="q" placeholder="Serial, name…" />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Serial</th>
                        <th>Category</th>
                        <th>Last customer</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <x-listing-table.row :href="route('isp.inventory.show', $item)">
                            <td class="font-semibold">{{ $item->name }}</td>
                            <td class="font-mono text-sm">{{ $item->serial_number }}</td>
                            <td>{{ $item->categoryLabel() }}</td>
                            <td>{{ $item->assignments->first()?->customer?->full_name ?? '—' }}</td>
                            <x-table-actions>
                                <x-table-actions.link :href="route('isp.inventory.show', $item)" label="Manage" />
                            </x-table-actions>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
