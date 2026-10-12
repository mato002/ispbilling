@extends('layouts.admin')

@section('title', 'Vouchers')

@section('header')
    <x-isp-page-header
        title="Vouchers"
        module="Sales &amp; access"
        description="Time and data vouchers tied to plans and routers."
        primaryLabel="Create voucher"
        :primaryHref="route('isp.vouchers.create')"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.vouchers.create-bulk') }}" class="pb-btn">Bulk create</a>
            @if(request()->filled('plan_id'))
                <a href="{{ route('isp.vouchers.print', request()->only('plan_id')) }}" target="_blank" rel="noopener" class="pb-btn">Print codes</a>
                <a href="{{ route('isp.vouchers.export.csv', request()->only('plan_id')) }}" class="pb-btn">Export codes CSV</a>
            @endif
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.vouchers.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$vouchers"
        :empty="$vouchers->isEmpty()"
        empty-title="No vouchers"
        empty-message="Create vouchers or sync from your sales workflow."
        empty-cta-label="Create voucher"
        :empty-cta-href="route('isp.vouchers.create')"
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Code or notes…"
            />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All"
                :options="[
                    'active' => 'Active',
                    'used' => 'Used',
                    'expired' => 'Expired',
                ]"
            />
            <x-listing-filters.select
                name="router_id"
                label="Router"
                placeholder="All routers"
                class="min-w-[160px] flex-1"
                :options="$routers->pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()"
            />
            <x-listing-filters.select
                name="plan_id"
                label="Plan"
                placeholder="All plans"
                class="min-w-[160px] flex-1"
                :options="$plans->mapWithKeys(fn ($plan) => [(string) $plan->id => $plan->name])->all()"
            />
            <x-listing-filters.select
                name="voucher_type"
                label="Type"
                placeholder="All"
                :options="[
                    'time' => 'Time',
                    'data' => 'Data',
                    'unlimited' => 'Unlimited',
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
                        <th>Code</th>
                        <th>Plan</th>
                        <th>Router</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Used by</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vouchers as $voucher)
                        <x-listing-table.row :href="route('isp.vouchers.show', $voucher)">
                            <td class="font-mono font-semibold">{{ $voucher->voucher_code }}</td>
                            <td>
                                @if ($voucher->plan)
                                    <div class="font-medium text-slate-900 dark:text-white">{{ $voucher->plan->name }}</div>
                                    <div class="text-slate-500 text-sm">KES {{ number_format($voucher->plan->price, 0) }}</div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td>{{ $voucher->router?->name ?? '—' }}</td>
                            <td><span class="pb-pill">{{ ucfirst($voucher->voucher_type) }}</span></td>
                            <td>
                                @if ($voucher->status === 'active')
                                    <x-status-badge status="active" />
                                @elseif ($voucher->status === 'used')
                                    <span class="pb-pill">Used</span>
                                @else
                                    <x-status-badge status="expired" />
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-sm">{{ $voucher->created_at->format('M j, Y H:i') }}</td>
                            <td>
                                @if ($voucher->usedByCustomer)
                                    <div class="font-medium">{{ $voucher->usedByCustomer->full_name }}</div>
                                    <div class="text-sm text-slate-500">{{ $voucher->used_at?->format('M j, Y H:i') }}</div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('isp.vouchers.show', $voucher)" label="View" />
                                    @if ($voucher->status === 'active')
                                        <x-table-actions.form
                                            :action="route('isp.vouchers.deactivate', $voucher)"
                                            method="PATCH"
                                            label="Deactivate"
                                            onsubmit="return confirm('Deactivate this voucher?');"
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
@endsection
