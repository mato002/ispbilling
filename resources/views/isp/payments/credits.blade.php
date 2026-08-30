@extends('layouts.admin')

@section('title', 'Customer credits')

@section('header')
    <x-isp-page-header
        title="Credits & overpayments"
        module="Billing"
        primaryLabel="Posted payments"
        :primaryHref="route('isp.payments.index')"
    />
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.payments.credits')"
        :paginator="$customers"
        :empty="$customers->isEmpty()"
        empty-title="No customer credits"
        empty-message="No subscribers have a wallet balance from overpayments."
    >
        <x-slot:filters>
            <x-listing-filters.search placeholder="Customer name or phone…" />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Credit balance</th>
                        <th>Status</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <x-listing-table.row :href="route('isp.customers.show', $customer)">
                            <td class="font-medium">{{ $customer->full_name }}</td>
                            <td>{{ $customer->phone_number }}</td>
                            <td class="font-mono font-semibold text-emerald-700 dark:text-emerald-400">KES {{ number_format($customer->account_credit, 2) }}</td>
                            <td><x-status-badge :status="$customer->status" /></td>
                            <td class="text-right">
                                <x-table-actions width="w-44">
                                    <x-table-actions.link :href="route('isp.payments.customer', $customer)" label="Payment history" />
                                    <x-table-actions.link :href="route('isp.invoices.index', ['customer_id' => $customer->id])" label="Invoices" />
                                </x-table-actions>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
