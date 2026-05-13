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
    <div class="space-y-6">
        <x-filter-bar :action="route('isp.tickets.index')">
                <div>
                    <label class="isp-form-label">Search</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="isp-form-input" placeholder="Subject, customer">
                </div>
                <div>
                    <label class="isp-form-label">Status</label>
                    <select name="status" class="isp-form-select">
                        <option value="">All</option>
                        @foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="isp-form-label">Priority</label>
                    <select name="priority" class="isp-form-select">
                        <option value="">All</option>
                        @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
                    <a href="{{ route('isp.tickets.index') }}" class="pb-btn ml-2">Reset</a>
                </div>
        </x-filter-bar>

        <x-data-table>
            <thead>
                <tr>
                    <th>ID</th><th>Subject</th><th>Customer</th><th>Priority</th><th>Status</th><th>Assigned To</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td>#{{ $ticket->id }}</td>
                        <td><a href="{{ route('isp.tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-700 font-semibold">{{ $ticket->subject }}</a></td>
                        <td>
                            @if($ticket->customer)
                                {{ $ticket->customer->full_name }} ({{ $ticket->customer->username }})
                            @else
                                -
                            @endif
                        </td>
                        <td><x-status-badge :status="$ticket->priority" /></td>
                        <td><x-status-badge :status="str_replace('_', ' ', $ticket->status)" /></td>
                        <td>{{ optional($ticket->assignedTo)->name ?? '-' }}</td>
                        <td class="text-right"><a href="{{ route('isp.tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-semibold">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-sm text-slate-500 py-10">
                            No tickets found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $tickets->links() }}</div>
    </div>
@endsection

