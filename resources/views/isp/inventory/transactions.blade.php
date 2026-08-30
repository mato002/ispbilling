@extends('layouts.admin')

@section('title', 'Inventory History')

@section('header')
    <x-isp-page-header
        title="Inventory history"
        module="Inventory"
        description="Full audit trail of stock movements, assignments, returns, and replacements."
    />
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <x-resource-index
        :filter-action="route('isp.inventory.transactions')"
        :filter-chips="$filterChips ?? []"
        :paginator="$transactions"
        :empty="$transactions->isEmpty()"
        empty-title="No transactions"
        empty-message="Actions on inventory items will be logged here automatically."
    >
        <x-slot:filters>
            <x-listing-filters.search name="q" placeholder="Serial or item name…" />
            <x-listing-filters.select name="action" label="Action" placeholder="All" :options="\App\Support\InventoryCatalog::TRANSACTION_ACTIONS" />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>Item</th>
                        <th>Customer</th>
                        <th>By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $tx)
                        <tr>
                            <td class="whitespace-nowrap text-sm">{{ $tx->created_at?->format('M d, Y H:i') }}</td>
                            <td><span class="text-sm font-medium">{{ $tx->actionLabel() }}</span></td>
                            <td>
                                @if ($tx->item)
                                    <a href="{{ route('isp.inventory.show', $tx->item) }}" class="text-indigo-600">{{ $tx->item->serial_number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $tx->customer?->full_name ?? '—' }}</td>
                            <td>{{ $tx->performedBy?->name ?? '—' }}</td>
                            <td class="max-w-xs truncate text-sm text-slate-600">{{ $tx->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
