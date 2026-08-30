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
        @if (! Auth::user()->isIspAdmin())
            <div class="pb-card border border-amber-200/70 bg-amber-50/60 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                Only your ISP Administrator can open new platform tickets. You can still follow threads and reply here.
            </div>
        @endif

        <x-resource-index
            :filter-action="route('isp.helpdesk.index')"
            :filter-chips="$filterChips ?? []"
            :export-formats="$exportFormats ?? []"
            :paginator="$tickets"
            :empty="$tickets->isEmpty()"
            empty-title="No helpdesk tickets yet"
            empty-message="Open a request with PrimeBilling for billing sync, integrations, or account issues."
            :empty-cta-label="Auth::user()->isIspAdmin() ? 'New request' : null"
            :empty-cta-href="Auth::user()->isIspAdmin() ? route('isp.helpdesk.create') : null"
        >
            <x-slot:filters>
                <x-listing-filters.search
                    name="q"
                    placeholder="Subject or message…"
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
                            <th>Opened by</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <x-table-actions.header />
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <x-listing-table.row :href="route('isp.helpdesk.show', $ticket)">
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
                                    <x-table-actions>
                                        <x-table-actions.link :href="route('isp.helpdesk.show', $ticket)" label="View" />
                                    </x-table-actions>
                                </td>
                            </x-listing-table.row>
                        @endforeach
                    </tbody>
                </x-data-table>
            </x-slot:table>
        </x-resource-index>
    </div>
@endsection
