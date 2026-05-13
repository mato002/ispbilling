@extends('layouts.admin')

@section('title', 'PrimeBilling helpdesk')

@section('header')
    <x-isp-page-header
        title="PrimeBilling helpdesk"
        module="Platform"
        description="Open a ticket with PrimeBilling for billing sync, integrations, outages, or account issues."
        :primaryLabel="Auth::user()->isIspAdmin() ? 'New request' : null"
        :primaryHref="Auth::user()->isIspAdmin() ? route('isp.helpdesk.create') : null"
    />
@endsection

@section('content')
    <div class="space-y-6">
        @if(! Auth::user()->isIspAdmin())
            <div class="pb-card border border-amber-200/70 bg-amber-50/60 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                Only your ISP Administrator can open new platform tickets. You can still follow threads and reply here.
            </div>
        @endif

        <x-filter-bar :action="route('isp.helpdesk.index')">
            <div>
                <label class="isp-form-label">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" class="isp-form-input" placeholder="Subject or message">
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
                <a href="{{ route('isp.helpdesk.index') }}" class="pb-btn ml-2">Reset</a>
            </div>
        </x-filter-bar>

        <x-data-table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Opened by</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td>#{{ $ticket->id }}</td>
                        <td>
                            <a href="{{ route('isp.helpdesk.show', $ticket) }}" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">
                                {{ $ticket->subject }}
                            </a>
                        </td>
                        <td>{{ optional($ticket->createdBy)->name ?? '—' }}</td>
                        <td><x-status-badge :status="$ticket->priority" /></td>
                        <td><x-status-badge :status="str_replace('_', ' ', $ticket->status)" /></td>
                        <td class="text-right">
                            <a href="{{ route('isp.helpdesk.show', $ticket) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-sm text-slate-500">No helpdesk tickets yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $tickets->links() }}</div>
    </div>
@endsection
