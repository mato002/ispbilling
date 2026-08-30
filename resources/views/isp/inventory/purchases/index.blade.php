@extends('layouts.admin')

@section('title', 'Purchases')

@section('header')
    <x-isp-page-header
        title="Vendor purchases"
        module="Inventory"
        description="Track bulk equipment purchases from vendors."
        primaryLabel="Record purchase"
        :primaryHref="route('isp.inventory.purchases.create')"
    />
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <x-resource-index
        :filter-action="route('isp.inventory.purchases')"
        :filter-chips="[]"
        :paginator="$purchases"
        :empty="$purchases->isEmpty()"
        empty-title="No purchase records"
        empty-message="Log vendor invoices when stocking new equipment."
        empty-cta-label="Record purchase"
        :empty-cta-href="route('isp.inventory.purchases.create')"
    >
        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Total cost</th>
                        <th>Items linked</th>
                        <th>Recorded by</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $purchase)
                        <tr>
                            <td class="font-medium">{{ $purchase->vendor_name }}</td>
                            <td>{{ $purchase->invoice_number ?? '—' }}</td>
                            <td>{{ $purchase->purchase_date?->format('M d, Y') }}</td>
                            <td>{{ number_format($purchase->total_cost, 2) }}</td>
                            <td>{{ $purchase->items_count }}</td>
                            <td>{{ $purchase->createdBy?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
