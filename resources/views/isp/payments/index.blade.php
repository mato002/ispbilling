@extends('layouts.admin')

@section('title', 'Payments')

@section('header')
    <x-isp-page-header
        title="Payments"
        module="Billing"
        description="Recorded payments, gateways, and activation follow-up."
        primaryLabel="Transaction logs"
        :primaryHref="route('isp.payments.transaction-logs')"
    />
@endsection

@section('content')
<div class="space-y-6">
    <x-filter-bar :action="route('isp.payments.index')">
        <div class="min-w-[200px] flex-1">
            <label class="isp-form-label">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="isp-form-input" placeholder="Reference, transaction ID, customer…">
        </div>
        <div class="min-w-[130px]">
            <label class="isp-form-label">Status</label>
            <select name="status" class="isp-form-select">
                <option value="">All</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="isp-form-label">Method</label>
            <select name="payment_method" class="isp-form-select">
                <option value="">All</option>
                <option value="mpesa" @selected(request('payment_method') === 'mpesa')>M-Pesa</option>
                <option value="selcom" @selected(request('payment_method') === 'selcom')>Selcom</option>
                <option value="manual" @selected(request('payment_method') === 'manual')>Manual</option>
                <option value="voucher" @selected(request('payment_method') === 'voucher')>Voucher</option>
            </select>
        </div>
        <div class="min-w-[120px]">
            <label class="isp-form-label">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="isp-form-input">
        </div>
        <div class="min-w-[120px]">
            <label class="isp-form-label">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="isp-form-input">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.payments.index') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($payments->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state title="No payments" message="No payments match the current filters." />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Paid at</th>
                    <th>Payment</th>
                    <th>Internet</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td class="font-mono text-sm font-medium">{{ $payment->payment_reference }}</td>
                        <td>
                            <div class="font-medium text-slate-900 dark:text-white">{{ $payment->customer->full_name ?? 'N/A' }}</div>
                            @if($payment->customer)
                                <div class="text-sm text-slate-500">{{ $payment->customer->phone_number }}</div>
                            @endif
                        </td>
                        <td>
                            @if($payment->invoice)
                                <a href="{{ route('isp.invoices.show', $payment->invoice) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">#{{ $payment->invoice->id }}</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="font-mono font-semibold whitespace-nowrap">KES {{ number_format($payment->amount, 2) }}</td>
                        <td><span class="pb-pill pb-pill-info">{{ strtoupper($payment->payment_method) }}</span></td>
                        <td class="whitespace-nowrap text-sm">{{ $payment->processed_at?->format('M j, Y H:i') ?? '—' }}</td>
                        <td>
                            @if($payment->status === 'completed')
                                <x-status-badge status="paid" />
                            @elseif($payment->status === 'pending')
                                <x-status-badge status="pending" />
                            @else
                                <span class="pb-pill pb-pill-bad">Failed</span>
                            @endif
                        </td>
                        <td>
                            @php $isActive = $payment->customer && $payment->customer->isActive(); @endphp
                            @if($isActive)
                                <span class="pb-pill pb-pill-ok">Active</span>
                            @elseif($payment->status === 'completed')
                                <span class="pb-pill pb-pill-warn">Pending</span>
                            @else
                                <span class="pb-pill">N/A</span>
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('isp.payments.show', $payment) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View</a>
                            @if($payment->payment_method === 'manual' && $payment->status === 'completed' && $payment->customer && !$payment->customer->isActive())
                                <form method="POST" action="{{ route('isp.payments.activate-internet', $payment) }}" class="inline ml-2">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Activate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
