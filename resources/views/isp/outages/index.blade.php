@extends('layouts.admin')

@section('title', 'Outages')

@section('header')
    <x-isp-page-header
        title="Outage Compensation"
        module="Network"
        description="Record downtime events and manually extend subscriptions or voucher validity for affected customers."
        primaryLabel="Record outage"
        :primaryHref="route('isp.outages.create')"
    />
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.outages.index')"
        :filter-chips="$filterChips ?? []"
        :paginator="$outages"
        :empty="$outages->isEmpty()"
        empty-title="No outages recorded"
        empty-message="Create an outage record when network downtime affects subscribers."
        empty-cta-label="Record outage"
        :empty-cta-href="route('isp.outages.create')"
    >
        <x-slot:filters>
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All"
                :options="[
                    'active' => 'Active',
                    'resolved' => 'Resolved',
                ]"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Router</th>
                        <th>Started</th>
                        <th>Ended</th>
                        <th>Status</th>
                        <th>Compensations</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($outages as $outage)
                        <x-listing-table.row :href="route('isp.outages.show', $outage)">
                            <td>
                                <a href="{{ route('isp.outages.show', $outage) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">
                                    {{ $outage->title }}
                                </a>
                            </td>
                            <td>{{ $outage->router?->name ?? '—' }}</td>
                            <td>{{ $outage->started_at?->format('M d, Y H:i') }}</td>
                            <td>{{ $outage->ended_at?->format('M d, Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($outage->status === 'active')
                                    <x-status-badge status="active" />
                                @else
                                    <x-status-badge status="resolved" />
                                @endif
                            </td>
                            <td>{{ $outage->compensation_logs_count }}</td>
                            <x-table-actions>
                                <x-table-actions.link :href="route('isp.outages.show', $outage)" label="Manage" />
                            </x-table-actions>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
