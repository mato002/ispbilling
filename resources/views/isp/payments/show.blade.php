@extends('layouts.admin')

@section('title', 'Payment')

@section('header')
    <x-isp-page-header
        :title="'Payment '.$payment->payment_reference"
        module="Billing"
        :description="$payment->customer?->full_name ?? 'Payment'"
        primaryLabel="All payments"
        :primaryHref="route('isp.payments.index')"
    >
        <x-slot:actions>
            @if ($payment->status === 'completed')
                <a href="{{ route('isp.payments.receipt.pdf', $payment) }}" class="pb-btn">Download receipt PDF</a>
            @endif
        </x-slot:actions>
    </x-isp-page-header>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-form-section>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Reference</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $payment->payment_reference }}</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $payment->customer?->full_name ?? 'Unassigned' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($payment->status === 'completed')
                        <x-status-badge status="paid" />
                    @elseif($payment->status === 'pending')
                        <x-status-badge status="pending" />
                    @else
                        <span class="pb-pill pb-pill-bad">Failed</span>
                    @endif
                    @if ($payment->isUnposted())
                        <span class="pb-pill pb-pill-warn">Unposted</span>
                    @elseif ($payment->posting_status === 'posted')
                        <span class="pb-pill pb-pill-ok">Posted</span>
                    @endif
                </div>
            </div>
            <dl class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <dt class="isp-form-label mb-1">Amount</dt>
                    <dd class="text-2xl font-bold font-mono text-slate-900 dark:text-white">KES {{ number_format($payment->amount, 2) }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Method</dt>
                    <dd><span class="pb-pill pb-pill-info">{{ strtoupper($payment->payment_method) }}</span></dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Service</dt>
                    <dd><span class="pb-pill {{ $payment->serviceTypeLabel() === 'Hotspot' ? 'pb-pill-cyan' : 'pb-pill-info' }}">{{ $payment->serviceTypeLabel() }}</span></dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Transaction ID</dt>
                    <dd class="font-mono text-sm">{{ $payment->transaction_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Phone</dt>
                    <dd class="text-sm">{{ $payment->phone_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Created</dt>
                    <dd class="text-sm">{{ $payment->created_at->format('M j, Y H:i') }}</dd>
                </div>
                @if($payment->processed_at)
                    <div>
                        <dt class="isp-form-label mb-1">Processed</dt>
                        <dd class="text-sm">{{ $payment->processed_at->format('M j, Y H:i') }}</dd>
                    </div>
                @endif
            </dl>
            @if($payment->notes)
                <div class="mt-6 border-t border-slate-100 pt-6 dark:border-slate-800">
                    <p class="isp-form-label mb-2">Notes</p>
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ $payment->notes }}</p>
                </div>
            @endif
        </x-form-section>

        @if($payment->invoice)
            <x-form-section title="Related invoice">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white">#{{ $payment->invoice->invoice_number }}</p>
                        <p class="text-sm text-slate-500">Due {{ $payment->invoice->due_date->format('M j, Y') }}</p>
                    </div>
                    <a href="{{ route('isp.invoices.show', $payment->invoice) }}" class="pb-btn pb-btn-primary">View invoice</a>
                </div>
            </x-form-section>
        @endif
    </div>

    <div class="space-y-6">
        @if ($payment->customer)
            <x-form-section title="Customer">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="isp-form-label mb-1">Name</dt>
                        <dd class="font-medium text-slate-900 dark:text-white">{{ $payment->customer->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Phone</dt>
                        <dd>{{ $payment->customer->phone_number }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Location</dt>
                        <dd>{{ $payment->customer->location }}</dd>
                    </div>
                </dl>
            </x-form-section>
        @elseif ($payment->isUnposted())
            <x-form-section title="Suspense account">
                <p class="text-sm text-slate-600 dark:text-slate-300">This payment has not been assigned to a subscriber.</p>
                <a href="{{ route('isp.payments.unposted') }}" class="pb-btn pb-btn-primary mt-3 w-full justify-center">Assign from unposted queue</a>
            </x-form-section>
        @endif

        @if ($payment->assigned_at)
            <x-form-section title="Manual posting">
                <p class="text-sm text-slate-600">Posted {{ $payment->assigned_at->format('M j, Y H:i') }} by {{ $payment->assignedBy?->name ?? 'Staff' }}</p>
            </x-form-section>
        @endif

        @if ($payment->status === 'completed')
            <x-form-section title="Service sync">
                <form method="POST" action="{{ route('isp.payments.force-sync', $payment) }}">
                    @csrf
                    <button type="submit" class="pb-btn w-full justify-center">Re-run auto-reconnect &amp; activation</button>
                </form>
            </x-form-section>
        @endif

        @if($payment->status === 'pending' && $payment->payment_method === 'mpesa')
            <div data-mpesa-pending class="rounded-xl border border-amber-200 bg-amber-50/90 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="text-sm text-amber-900 dark:text-amber-100">
                    <strong>Pending:</strong> Complete the M-Pesa prompt on the handset. This page can refresh status.
                </p>
                <button type="button" onclick="checkPaymentStatus()" class="mt-3 text-sm font-semibold text-amber-800 underline dark:text-amber-200">Refresh status</button>
            </div>
        @endif

        @if($payment->status === 'pending' && $payment->payment_method === 'manual')
            <x-form-section title="Manual payment">
                <div class="flex flex-col gap-2">
                    <form method="POST" action="{{ route('isp.payments.confirm-manual', $payment) }}">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button type="submit" class="pb-btn pb-btn-primary w-full justify-center">Mark completed</button>
                    </form>
                    <form method="POST" action="{{ route('isp.payments.confirm-manual', $payment) }}">
                        @csrf
                        <input type="hidden" name="confirm" value="0">
                        <button type="submit" class="pb-btn w-full justify-center border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100">Mark failed</button>
                    </form>
                </div>
            </x-form-section>
        @endif
    </div>
</div>

@if($payment->status === 'pending' && $payment->payment_method === 'mpesa')
<script>
    function checkPaymentStatus() {
        fetch('{{ route("isp.payments.status", $payment->id) }}')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Payment completed',
                        text: 'Your payment has been confirmed.',
                        confirmButtonColor: '#10b981',
                    }).then(() => { window.location.reload(); });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Still pending',
                        text: 'Complete the payment on the phone.',
                        confirmButtonColor: '#3b82f6',
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }
    setInterval(function() {
        if (document.querySelector('[data-mpesa-pending]')) {
            checkPaymentStatus();
        }
    }, 10000);
</script>
@endif
@endsection
