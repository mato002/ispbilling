@extends('layouts.admin')

@section('title', 'Device Assignments')

@section('header')
    <x-isp-page-header
        title="Device assignments"
        module="Inventory"
        description="Track which equipment is installed at customer premises."
        primaryLabel="Assign device"
        :primaryHref="route('isp.inventory.assignments.create')"
    />
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <x-resource-index
        :filter-action="route('isp.inventory.assignments.index')"
        :filter-chips="$filterChips ?? []"
        :paginator="$assignments"
        :empty="$assignments->isEmpty()"
        empty-title="No assignments"
        empty-message="Assign available stock to customers when installing CPE or other equipment."
        empty-cta-label="Assign device"
        :empty-cta-href="route('isp.inventory.assignments.create')"
    >
        <x-slot:filters>
            <x-listing-filters.search name="q" placeholder="Customer, serial…" />
            <x-listing-filters.select name="installation_status" label="Installation" placeholder="All" :options="\App\Support\InventoryCatalog::INSTALLATION_STATUSES" />
            <x-listing-filters.select name="active" label="Status" placeholder="All" :options="['1' => 'Active', '0' => 'Closed']" />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Customer</th>
                        <th>Router</th>
                        <th>Installation</th>
                        <th>Assigned</th>
                        <th>Status</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assignments as $assignment)
                        <x-listing-table.row :href="route('isp.inventory.assignments.show', $assignment)">
                            <td>
                                <span class="font-medium">{{ $assignment->item?->name }}</span>
                                <p class="font-mono text-xs text-slate-500">{{ $assignment->item?->serial_number }}</p>
                            </td>
                            <td>{{ $assignment->customer?->full_name }}</td>
                            <td>{{ $assignment->router?->name ?? '—' }}</td>
                            <td>{{ $assignment->installationStatusLabel() }}</td>
                            <td>{{ $assignment->assigned_at?->format('M d, Y') }}</td>
                            <td>
                                @if ($assignment->is_active)
                                    <x-status-badge status="active" />
                                @else
                                    <x-status-badge status="resolved" />
                                @endif
                            </td>
                            <x-table-actions>
                                <x-table-actions.link :href="route('isp.inventory.assignments.show', $assignment)" label="Manage" />
                            </x-table-actions>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
