@extends('layouts.admin')

@section('title', 'Data Usage')

@section('header')
    <x-isp-page-header
        title="Data Usage Monitoring"
        module="Network"
        description="Read-only bandwidth reporting for hotspot and PPPoE subscribers. Does not affect billing or subscriptions."
    />
@endsection

@section('content')
    <div class="space-y-6">
        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-dashboard.kpi-card title="Total transfer" :value="\App\Models\DataUsage::formatBytes($stats['total_bytes'] ?? 0)" subtext="Selected period" iconBg="from-indigo-500 to-violet-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3 9 4-18 3 9h4"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Upload" :value="\App\Models\DataUsage::formatBytes($stats['upload_bytes'] ?? 0)" subtext="Upstream" iconBg="from-cyan-500 to-blue-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Download" :value="\App\Models\DataUsage::formatBytes($stats['download_bytes'] ?? 0)" subtext="Downstream" iconBg="from-emerald-500 to-teal-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Active sessions" :value="number_format($stats['active_sessions'] ?? 0)" :subtext="number_format($stats['customers'] ?? 0).' customers with usage'" iconBg="from-amber-500 to-orange-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M2 12a10 10 0 0 1 20 0"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
        </section>

        <x-form-section title="Router usage summary" description="Bandwidth totals, active users, and peak hour per edge router.">
            @if ($routerSummaries->isEmpty())
                <p class="text-sm text-slate-500">No router usage recorded for the selected period.</p>
            @else
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Router</th>
                            <th>Total bandwidth</th>
                            <th>Upload</th>
                            <th>Download</th>
                            <th>Customers</th>
                            <th>Active users</th>
                            <th>Peak hour</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($routerSummaries as $summary)
                            <tr>
                                <td>
                                    <a href="{{ route('isp.routers.show', $summary->router) }}" class="font-medium text-indigo-600 hover:text-indigo-700">
                                        {{ $summary->router->name }}
                                    </a>
                                    <p class="font-mono text-xs text-slate-500">{{ $summary->router->ip_address }}</p>
                                </td>
                                <td>{{ \App\Models\DataUsage::formatBytes($summary->total_bytes) }}</td>
                                <td>{{ \App\Models\DataUsage::formatBytes($summary->upload_bytes) }}</td>
                                <td>{{ \App\Models\DataUsage::formatBytes($summary->download_bytes) }}</td>
                                <td>{{ number_format($summary->unique_customers) }}</td>
                                <td>{{ number_format($summary->active_users) }}</td>
                                <td>{{ $summary->peak_hour_label ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </x-form-section>

        <x-resource-index
            :filter-action="route('isp.data-usage.index')"
            :filter-chips="$filterChips ?? []"
            :export-formats="$exportFormats ?? []"
            :paginator="$paginated"
            :empty="$paginated->isEmpty()"
            empty-title="No usage data"
            empty-message="No customer usage records match the current filters. Usage is collected when hotspot sessions end and during PPPoE polls."
        >
            <x-slot:filters>
                <x-listing-filters.select
                    name="customer_id"
                    label="Customer"
                    placeholder="All customers"
                    class="min-w-[180px] flex-1"
                    :options="$customers->pluck('full_name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()"
                />
                <x-listing-filters.select
                    name="router_id"
                    label="Router"
                    placeholder="All routers"
                    class="min-w-[160px] flex-1"
                    :options="$routers->pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()"
                />
                <x-listing-filters.select
                    name="package_type"
                    label="Package type"
                    placeholder="All types"
                    :options="[
                        'hotspot' => 'Hotspot',
                        'pppoe' => 'PPPoE',
                    ]"
                />
                <x-listing-filters.search placeholder="Customer name, MAC, or IP…" />
                <x-listing-filters.date name="date_from" label="From" />
                <x-listing-filters.date name="date_to" label="To" />
                <x-listing-filters.select
                    name="sort"
                    label="Sort"
                    placeholder="Highest usage"
                    :options="[
                        'usage_desc' => 'Highest usage',
                        'usage_asc' => 'Lowest usage',
                        'last_active' => 'Last active',
                    ]"
                />
                <x-listing-filters.per-page :default="25" />
            </x-slot:filters>

            <x-slot:table>
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Router</th>
                            <th>Upload</th>
                            <th>Download</th>
                            <th>Total</th>
                            <th>Last active</th>
                            <x-table-actions.header />
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paginated as $row)
                            @php $customer = $row->customer; @endphp
                            <x-listing-table.row :href="$customer ? route('isp.data-usage.customer', $customer) : null">
                                <td>
                                    @if ($customer)
                                        <a href="{{ route('isp.data-usage.customer', $customer) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">
                                            {{ $customer->full_name }}
                                        </a>
                                        <p class="font-mono text-xs text-slate-500">{{ $customer->username }}</p>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($customer?->connection_type === 'hotspot')
                                        <span class="pb-pill pb-pill-cyan">Hotspot</span>
                                    @elseif ($customer)
                                        <span class="pb-pill pb-pill-info">PPPoE</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $customer?->router?->name ?? '—' }}</td>
                                <td>{{ \App\Models\DataUsage::formatBytes((int) $row->upload_bytes) }}</td>
                                <td>{{ \App\Models\DataUsage::formatBytes((int) $row->download_bytes) }}</td>
                                <td class="font-medium">{{ \App\Models\DataUsage::formatBytes((int) $row->total_bytes) }}</td>
                                <td class="whitespace-nowrap text-slate-600">
                                    {{ $row->last_active_at ? \Illuminate\Support\Carbon::parse($row->last_active_at)->format('M j, Y H:i') : '—' }}
                                </td>
                                <x-table-actions>
                                    @if ($customer)
                                        <x-table-actions.link :href="route('isp.data-usage.customer', $customer)" label="Details" />
                                        <x-table-actions.link :href="route('isp.customers.show', $customer)" label="Profile" />
                                    @endif
                                </x-table-actions>
                            </x-listing-table.row>
                        @endforeach
                    </tbody>
                </x-data-table>
            </x-slot:table>
        </x-resource-index>
    </div>
@endsection
