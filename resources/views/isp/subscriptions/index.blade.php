@extends('layouts.admin')

@section('title', 'Subscriptions')

@section('header')
    <x-isp-page-header
        title="Subscriptions"
        module="Billing &amp; plans"
        description="Active and historical subscriber plans, renewal windows, and billing alignment."
        primaryLabel="Create subscription"
        :primaryHref="route('isp.subscriptions.create')"
    />
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.subscriptions.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$subscriptions"
        :empty="$subscriptions->isEmpty()"
        empty-title="No subscriptions match"
        empty-message="Adjust filters or create a subscription for a customer."
        empty-cta-label="Create subscription"
        :empty-cta-href="route('isp.subscriptions.create')"
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Customer, phone, username, plan…"
            />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All"
                :options="[
                    'active' => 'Active',
                    'expired' => 'Expired',
                    'cancelled' => 'Cancelled',
                    'suspended' => 'Suspended',
                ]"
            />
            <x-listing-filters.select
                name="plan_id"
                label="Plan"
                placeholder="All plans"
                class="min-w-[180px] flex-1"
                :options="$plans->pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Plan</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Auto renew</th>
                        <th>Status</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subscriptions as $subscription)
                        <x-listing-table.row :href="route('isp.subscriptions.show', $subscription)">
                            <td>
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $subscription->customer->full_name }}</div>
                                <div class="text-slate-500 dark:text-slate-400">{{ $subscription->customer->phone_number }}</div>
                            </td>
                            <td>{{ $subscription->plan->name }}</td>
                            <td class="whitespace-nowrap">{{ $subscription->start_date->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap">
                                {{ $subscription->end_date->format('M j, Y') }}
                                @if ($subscription->end_date < now() && $subscription->status === 'active')
                                    <span class="ml-1 text-xs font-semibold text-rose-600 dark:text-rose-400">(Expired)</span>
                                @endif
                            </td>
                            <td>
                                @if ($subscription->auto_renew)
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">Yes</span>
                                @else
                                    <span class="text-slate-400">No</span>
                                @endif
                            </td>
                            <td>
                                @if ($subscription->status === 'active')
                                    <x-status-badge status="active" />
                                @elseif ($subscription->status === 'expired')
                                    <x-status-badge status="expired" />
                                @else
                                    <x-status-badge :status="ucfirst($subscription->status)" />
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <x-table-actions width="w-52">
                                    <x-table-actions.link :href="route('isp.subscriptions.show', $subscription)" label="View" />
                                    @if ($subscription->status === 'active' || $subscription->status === 'expired')
                                        <x-table-actions.form
                                            :action="route('isp.subscriptions.renew', $subscription)"
                                            label="Renew"
                                            :danger="false"
                                            onsubmit="confirmFormSubmit(event, {title: 'Create renewal invoice', text: 'Create an invoice for {{ $subscription->customer->full_name }}? Service extends after payment.', icon: 'question', confirmButtonText: 'Create invoice'}); return false;"
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
