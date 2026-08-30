@extends('layouts.admin')

@section('title', 'Technician Issues')

@section('header')
    <x-isp-page-header
        title="Technician issues"
        module="Inventory"
        description="Equipment currently issued to field technicians before customer installation."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.inventory.index') }}" class="pb-btn">All inventory</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <x-resource-index
        :filter-action="route('isp.inventory.technician-assignments.index')"
        :filter-chips="[]"
        :paginator="$assignments"
        :empty="$assignments->isEmpty()"
        empty-title="No technician issues"
        empty-message="Issue available stock to technicians from an inventory item detail page."
        empty-cta-label="View inventory"
        :empty-cta-href="route('isp.inventory.index')"
    >
        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Technician</th>
                        <th>Issued by</th>
                        <th>Issued at</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assignments as $assignment)
                        <tr>
                            <td>
                                <a href="{{ route('isp.inventory.show', $assignment->item) }}" class="font-medium text-indigo-600">{{ $assignment->item?->name }}</a>
                                <p class="font-mono text-xs text-slate-500">{{ $assignment->item?->serial_number }}</p>
                            </td>
                            <td>{{ $assignment->technician?->name }}</td>
                            <td>{{ $assignment->assignedBy?->name ?? '—' }}</td>
                            <td>{{ $assignment->assigned_at?->format('M d, Y H:i') }}</td>
                            <td>
                                @if ($assignment->is_active)
                                    <x-status-badge status="active" />
                                @else
                                    <x-status-badge status="resolved" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
