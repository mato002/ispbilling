@extends('layouts.admin')

@section('title', 'Session Monitoring')

@section('header')
    <x-isp-page-header
        title="Session Monitoring"
        module="Network Operations"
        description="Track live hotspot and PPPoE sessions, data usage, uptime, and disconnection actions from one NOC view."
    />
@endsection

@section('content')
    <div class="space-y-6">
        <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-dashboard.kpi-card title="Active Sessions" :value="number_format($stats['active_sessions'] ?? 0)" subtext="Live subscribers" iconBg="from-indigo-500 to-cyan-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12a10 10 0 0 1 20 0"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Sessions Today" :value="number_format($stats['total_today'] ?? 0)" subtext="Daily traffic" iconBg="from-emerald-500 to-teal-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Bandwidth Today" :value="number_format(($stats['total_bandwidth_today'] ?? 0) / 1024 / 1024, 2).' GB'" subtext="Aggregate transfer" iconBg="from-violet-500 to-indigo-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3 9 4-18 3 9h4"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
        </section>

        <x-resource-index
            :filter-action="route('isp.sessions.index')"
            :filter-chips="$filterChips ?? []"
            :export-formats="$exportFormats ?? []"
            :paginator="$sessions"
            :empty="$sessions->isEmpty()"
            empty-title="No sessions found"
            empty-message="No hotspot or PPPoE sessions match the current filters."
        >
            <x-slot:filters>
                <x-listing-filters.select
                    name="router_id"
                    label="Router"
                    placeholder="All routers"
                    class="min-w-[180px] flex-1"
                    :options="$routers->pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()"
                />
                <x-listing-filters.search
                    placeholder="Username, customer, IP…"
                />
                <x-listing-filters.select
                    name="status"
                    label="Status"
                    placeholder="Active (default)"
                    :options="[
                        'active' => 'Active',
                        'terminated' => 'Terminated',
                        'expired' => 'Expired',
                    ]"
                />
                <x-listing-filters.date name="date_from" label="From" />
                <x-listing-filters.date name="date_to" label="To" />
                <x-listing-filters.per-page :default="25" />
            </x-slot:filters>

            <x-slot:table>
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Customer</th>
                            <th>Router</th>
                            <th>IP Address</th>
                            <th>Data Used</th>
                            <th>Uptime</th>
                            <th>Started</th>
                            <x-table-actions.header />
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <x-listing-table.row :href="route('isp.sessions.show', $session)">
                                <td>{{ $session->username }}</td>
                                <td>{{ $session->customer->full_name ?? 'Voucher User' }}</td>
                                <td>{{ $session->router->name }}</td>
                                <td class="font-mono text-xs">{{ $session->ip_address }}</td>
                                <td>{{ $session->formatted_total_bytes }}</td>
                                <td>{{ $session->uptime_formatted }}</td>
                                <td>{{ $session->started_at->format('M d, H:i') }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <x-table-actions>
                                        <x-table-actions.link :href="route('isp.sessions.show', $session)" label="View" />
                                        @if ($session->status === 'active')
                                            <x-table-actions.form
                                                :action="route('isp.sessions.disconnect', $session)"
                                                label="Disconnect"
                                            />
                                        @endif
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
