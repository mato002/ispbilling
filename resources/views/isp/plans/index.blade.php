@extends('layouts.admin')

@section('title', 'Plans')

@section('header')
    <x-isp-page-header
        title="Plans &amp; packages"
        module="Catalog"
        description="Hotspot, PPPoE, data bundles, unlimited, static IP, installation, and add-on packages."
        primaryLabel="Create plan"
        :primaryHref="route('isp.plans.create')"
    />
@endsection

@section('content')
    @php use App\Support\PlanPackageTypes; @endphp
    <x-resource-index
        :filter-action="route('isp.plans.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$plans"
        :empty="$plans->isEmpty()"
        empty-title="No plans found"
        empty-message="Create a plan to attach subscriptions and vouchers."
        empty-cta-label="Create plan"
        :empty-cta-href="route('isp.plans.create')"
    >
        <x-slot:filters>
            <x-listing-filters.select
                name="package_type"
                label="Package type"
                placeholder="All types"
                class="min-w-[180px]"
                :options="PlanPackageTypes::labels()"
            />
            <x-listing-filters.select
                name="access_type"
                label="Access type"
                placeholder="All"
                :options="[
                    'pppoe' => 'PPPoE',
                    'hotspot' => 'Hotspot',
                ]"
            />
            <x-listing-filters.select
                name="is_active"
                label="Status"
                placeholder="All"
                :options="['1' => 'Active', '0' => 'Inactive']"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Package type</th>
                        <th>Access</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Limits</th>
                        <th>Subs</th>
                        <th>Status</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plans as $plan)
                        <x-listing-table.row :href="route('isp.plans.show', $plan)">
                            <td class="max-w-[220px]">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $plan->name }}</div>
                                <div class="truncate text-sm text-slate-500 dark:text-slate-400">{{ $plan->description ?: '—' }}</div>
                            </td>
                            <td>
                                <span class="pb-pill">{{ $plan->packageTypeLabel() }}</span>
                            </td>
                            <td>
                                @if ($plan->access_type === 'hotspot')
                                    <span class="pb-pill pb-pill-cyan">Hotspot</span>
                                @elseif (in_array($plan->package_type, [PlanPackageTypes::INSTALLATION_SERVICE, PlanPackageTypes::ADDON_SERVICE, PlanPackageTypes::STATIC_IP_ADDON], true))
                                    <span class="text-xs text-slate-500">—</span>
                                @else
                                    <span class="pb-pill pb-pill-info">PPPoE</span>
                                @endif
                            </td>
                            <td class="font-mono whitespace-nowrap">KES {{ number_format($plan->price, 2) }}</td>
                            <td>
                                {{ $plan->duration_days }}d
                                @if ($plan->is_one_time)
                                    <span class="text-[10px] text-slate-400">· once</span>
                                @endif
                            </td>
                            <td class="max-w-[160px] truncate text-sm text-slate-600 dark:text-slate-300">
                                @if ($plan->data_limit_mb)
                                    {{ number_format($plan->data_limit_mb) }} MB
                                @elseif ($plan->isUnlimitedPackage())
                                    {{ $plan->formatted_speed }}
                                @elseif ($plan->service_type)
                                    {{ str_replace('_', ' ', $plan->service_type) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="tabular-nums">{{ $plan->subscriptions_count }}</td>
                            <td>
                                @if ($plan->is_active)
                                    <x-status-badge status="active" />
                                @else
                                    <x-status-badge status="inactive" />
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('isp.plans.show', $plan)" label="View" />
                                    <x-table-actions.link :href="route('isp.plans.edit', $plan)" label="Edit" />
                                </x-table-actions>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
