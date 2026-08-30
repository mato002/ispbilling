@extends('layouts.admin')

@section('title', 'Support Tickets')

@section('header')
    <x-isp-page-header
        title="Support Tickets"
        module="Operations"
        description="Customer support issues, optional customer link, and assignment."
        primaryLabel="New Ticket"
        :primaryHref="route('isp.tickets.create')"
    />
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.tickets.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$tickets"
        :empty="$tickets->isEmpty()"
        empty-title="No tickets found"
        empty-message="Create a ticket or adjust your filters."
        empty-cta-label="New Ticket"
        :empty-cta-href="route('isp.tickets.create')"
    >
        <x-slot:filters>
            <x-listing-filters.search
                name="q"
                placeholder="Subject, customer…"
            />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All"
                :options="[
                    'open' => 'Open',
                    'in_progress' => 'In Progress',
                    'resolved' => 'Resolved',
                    'closed' => 'Closed',
                ]"
            />
            <x-listing-filters.select
                name="priority"
                label="Priority"
                placeholder="All"
                :options="[
                    'low' => 'Low',
                    'normal' => 'Normal',
                    'high' => 'High',
                ]"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <x-listing-table.row :href="route('isp.tickets.show', $ticket)">
                            <td>#{{ $ticket->id }}</td>
                            <td><a href="{{ route('isp.tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-700 font-semibold">{{ $ticket->subject }}</a></td>
                            <td>
                                @if ($ticket->customer)
                                    {{ $ticket->customer->full_name }} ({{ $ticket->customer->username }})
                                @else
                                    -
                                @endif
                            </td>
                            <td><x-status-badge :status="$ticket->priority" /></td>
                            <td><x-status-badge :status="str_replace('_', ' ', $ticket->status)" /></td>
                            <td>{{ optional($ticket->assignedTo)->name ?? '-' }}</td>
                            <td class="text-right">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('isp.tickets.show', $ticket)" label="View" />
                                </x-table-actions>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
