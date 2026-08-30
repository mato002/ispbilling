@extends('layouts.admin')

@section('title', 'Invoices')

@section('header')
    <x-isp-page-header
        title="Invoices"
        module="Billing"
        description="Issued invoices, payment state, and PDF exports."
        primaryLabel="New subscription"
        :primaryHref="route('isp.subscriptions.create')"
    />
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.invoices.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$invoices"
        :empty="$invoices->isEmpty()"
        empty-title="No invoices"
        empty-message="Generate invoices from customer profiles or billing automation."
        empty-cta-label="Customers"
        :empty-cta-href="route('isp.customers.index')"
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Invoice #, customer, phone…"
            />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All"
                :options="[
                    'paid' => 'Paid',
                    'pending' => 'Pending',
                    'overdue' => 'Overdue',
                    'cancelled' => 'Cancelled',
                ]"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Issued</th>
                        <th>Due</th>
                        <th>Status</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        <x-listing-table.row :href="route('isp.invoices.show', $invoice)">
                            <td class="font-mono font-semibold">{{ $invoice->invoice_number }}</td>
                            <td>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $invoice->customer->full_name }}</div>
                                <div class="text-sm text-slate-500">{{ $invoice->customer->phone_number }}</div>
                            </td>
                            <td class="font-mono whitespace-nowrap font-semibold">KES {{ number_format($invoice->total_amount, 2) }}</td>
                            <td class="whitespace-nowrap">{{ $invoice->issue_date->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap">
                                {{ $invoice->due_date->format('M j, Y') }}
                                @if ($invoice->isOverdue())
                                    <span class="ml-1 text-xs font-semibold text-rose-600 dark:text-rose-400">Overdue</span>
                                @endif
                            </td>
                            <td>
                                @if ($invoice->status === 'paid')
                                    <x-status-badge status="paid" />
                                @elseif ($invoice->status === 'overdue')
                                    <x-status-badge status="overdue" />
                                @else
                                    <x-status-badge status="pending" />
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('isp.invoices.show', $invoice)" label="View" />
                                    <x-table-actions.link :href="route('isp.invoices.show', $invoice).'?pdf=1'" label="Download PDF" target="_blank" />
                                </x-table-actions>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
