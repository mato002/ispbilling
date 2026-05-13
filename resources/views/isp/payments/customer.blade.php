@extends('layouts.admin')

@section('title', 'Customer payments')

@section('header')
    <x-isp-page-header
        title="Payments"
        module="Billing"
        :description="$customer->full_name.' · '.$customer->phone_number"
        primaryLabel="Customer profile"
        :primaryHref="route('isp.customers.show', $customer)"
    />
@endsection

@section('content')
<div class="space-y-6">
    @if($payments->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state title="No payments" message="This customer has no recorded payments yet." :ctaHref="route('isp.customers.show', $customer)" ctaLabel="Back to profile" />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Invoice</th>
                    <th class="text-right">Amount</th>
                    <th>Method</th>
                    <th>Paid at</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td class="font-mono text-sm font-medium">{{ $payment->payment_reference }}</td>
                        <td>
                            @if($payment->invoice)
                                <a href="{{ route('isp.invoices.show', $payment->invoice) }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">#{{ $payment->invoice->id }}</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-right font-mono font-semibold">KES {{ number_format($payment->amount, 2) }}</td>
                        <td><span class="pb-pill pb-pill-info text-xs">{{ strtoupper($payment->payment_method) }}</span></td>
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
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('isp.payments.show', $payment) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
