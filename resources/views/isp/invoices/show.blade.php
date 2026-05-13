@extends('layouts.admin')

@section('title', 'Invoice')

@section('header')
    <x-isp-page-header
        :title="'Invoice '.$invoice->invoice_number"
        module="Billing"
        :description="$invoice->customer->full_name"
        primaryLabel="Download PDF"
        :primaryHref="route('isp.invoices.show', $invoice) . '?pdf=1'"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.invoices.index') }}" class="pb-btn">Back to list</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-form-section>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Invoice</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">#{{ $invoice->invoice_number }}</p>
                </div>
                <div>
                    @if($invoice->status === 'paid')
                        <x-status-badge status="paid" />
                    @elseif($invoice->status === 'overdue')
                        <x-status-badge status="overdue" />
                    @else
                        <x-status-badge status="pending" />
                    @endif
                </div>
            </div>
            <dl class="mt-6 grid grid-cols-2 gap-4">
                <div>
                    <dt class="isp-form-label mb-1">Issue date</dt>
                    <dd class="text-sm font-medium">{{ $invoice->issue_date->format('M j, Y') }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Due date</dt>
                    <dd class="text-sm font-medium">
                        {{ $invoice->due_date->format('M j, Y') }}
                        @if($invoice->isOverdue())
                            <span class="ml-1 text-xs font-semibold text-rose-600 dark:text-rose-400">Overdue</span>
                        @endif
                    </dd>
                </div>
                @if($invoice->paid_date)
                    <div>
                        <dt class="isp-form-label mb-1">Paid date</dt>
                        <dd class="text-sm font-medium">{{ $invoice->paid_date->format('M j, Y') }}</dd>
                    </div>
                @endif
            </dl>

            <div class="mt-6 border-t border-slate-100 pt-6 dark:border-slate-800">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-700">
                            <th class="py-2">Description</th>
                            <th class="py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="py-3 text-slate-800 dark:text-slate-100">
                                @if($invoice->subscription)
                                    Subscription: {{ $invoice->subscription->plan->name }}
                                @else
                                    Service charge
                                @endif
                            </td>
                            <td class="py-3 text-right font-mono font-medium">KES {{ number_format($invoice->amount, 2) }}</td>
                        </tr>
                        @if($invoice->tax > 0)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-3">Tax</td>
                                <td class="py-3 text-right font-mono">KES {{ number_format($invoice->tax, 2) }}</td>
                            </tr>
                        @endif
                        @if($invoice->penalty > 0)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-3">Penalty</td>
                                <td class="py-3 text-right font-mono">KES {{ number_format($invoice->penalty, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="py-3 font-semibold text-slate-900 dark:text-white">Total</td>
                            <td class="py-3 text-right text-lg font-bold font-mono text-slate-900 dark:text-white">KES {{ number_format($invoice->total_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if($invoice->notes)
                <div class="mt-6 border-t border-slate-100 pt-6 dark:border-slate-800">
                    <p class="isp-form-label mb-2">Notes</p>
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ $invoice->notes }}</p>
                </div>
            @endif
        </x-form-section>

        @if($invoice->payments->count() > 0)
            <div>
                <h3 class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-100">Payments</h3>
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Method</th>
                            <th class="text-right">Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                            <tr>
                                <td class="font-mono text-sm">{{ $payment->payment_reference }}</td>
                                <td><span class="pb-pill pb-pill-info text-xs">{{ strtoupper($payment->payment_method) }}</span></td>
                                <td class="text-right font-mono font-medium">KES {{ number_format($payment->amount, 2) }}</td>
                                <td class="text-sm">{{ $payment->created_at->format('M j, Y') }}</td>
                                <td>
                                    @if($payment->status === 'completed')
                                        <x-status-badge status="paid" />
                                    @else
                                        <x-status-badge :status="ucfirst($payment->status)" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            </div>
        @endif
    </div>

    <div class="space-y-6">
        <x-form-section title="Customer">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="isp-form-label mb-1">Name</dt>
                    <dd class="font-medium text-slate-900 dark:text-white">{{ $invoice->customer->full_name }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Phone</dt>
                    <dd>{{ $invoice->customer->phone_number }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Location</dt>
                    <dd>{{ $invoice->customer->location }}</dd>
                </div>
            </dl>
        </x-form-section>

        @if($invoice->status !== 'paid')
            <x-form-section title="Pay invoice">
                <form method="POST" action="{{ route('isp.payments.initiate.store') }}" id="paymentForm" class="space-y-4">
                    @csrf
                    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                    <div>
                        <label class="isp-form-label" for="payment_method">Payment method</label>
                        <select name="payment_method" id="payment_method" required class="isp-form-select">
                            <option value="">Select method</option>
                            <option value="mpesa">M-Pesa (STK push)</option>
                            <option value="manual">Manual payment</option>
                        </select>
                    </div>
                    <div id="phone_number_field" class="hidden">
                        <label class="isp-form-label" for="phone_number">Phone number</label>
                        <input type="text" name="phone_number" id="phone_number" value="{{ $invoice->customer->phone_number }}" placeholder="254XXXXXXXXX" class="isp-form-input">
                        <p class="mt-1 text-xs text-slate-500">Format: 254XXXXXXXXX</p>
                    </div>
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 p-4 text-sm text-indigo-900 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-100">
                        <strong>Amount due:</strong> KES {{ number_format($invoice->total_amount, 2) }}
                    </div>
                    <button type="submit" class="pb-btn pb-btn-primary w-full justify-center">Initiate payment</button>
                </form>
            </x-form-section>
        @endif
    </div>
</div>

<script>
    document.getElementById('payment_method')?.addEventListener('change', function() {
        const phoneField = document.getElementById('phone_number_field');
        const input = phoneField?.querySelector('input');
        if (!phoneField || !input) return;
        if (this.value === 'mpesa') {
            phoneField.classList.remove('hidden');
            input.required = true;
        } else {
            phoneField.classList.add('hidden');
            input.required = false;
        }
    });
</script>
@endsection
