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
<div class="space-y-6">
    <x-filter-bar :action="route('isp.invoices.index')">
        <div class="min-w-[200px] flex-1">
            <label class="isp-form-label">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="isp-form-input" placeholder="Invoice #, customer, phone…">
        </div>
        <div class="min-w-[140px]">
            <label class="isp-form-label">Status</label>
            <select name="status" class="isp-form-select">
                <option value="">All</option>
                <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="overdue" @selected(request('status') === 'overdue')>Overdue</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.invoices.index') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($invoices->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state
                title="No invoices"
                message="Generate invoices from customer profiles or billing automation."
                ctaLabel="Customers"
                :ctaHref="route('isp.customers.index')"
            />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Issued</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                    <tr>
                        <td class="font-mono font-semibold">{{ $invoice->invoice_number }}</td>
                        <td>
                            <div class="font-medium text-slate-900 dark:text-white">{{ $invoice->customer->full_name }}</div>
                            <div class="text-sm text-slate-500">{{ $invoice->customer->phone_number }}</div>
                        </td>
                        <td class="font-mono whitespace-nowrap font-semibold">KES {{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="whitespace-nowrap">{{ $invoice->issue_date->format('M j, Y') }}</td>
                        <td class="whitespace-nowrap">
                            {{ $invoice->due_date->format('M j, Y') }}
                            @if($invoice->isOverdue())
                                <span class="ml-1 text-xs font-semibold text-rose-600 dark:text-rose-400">Overdue</span>
                            @endif
                        </td>
                        <td>
                            @if($invoice->status === 'paid')
                                <x-status-badge status="paid" />
                            @elseif($invoice->status === 'overdue')
                                <x-status-badge status="overdue" />
                            @else
                                <x-status-badge status="pending" />
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('isp.invoices.show', $invoice) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View</a>
                            <a href="{{ route('isp.invoices.show', $invoice) }}?pdf=1" target="_blank" class="ml-3 text-sm font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-300">PDF</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $invoices->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
