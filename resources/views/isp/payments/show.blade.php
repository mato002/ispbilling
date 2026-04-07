@extends('layouts.admin')

@section('title', 'Payment Details')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Payment Details') }}
        </h2>
        <a href="{{ route('isp.payments.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to List
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Payment Information -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Payment #{{ $payment->payment_reference }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $payment->customer->full_name }}</p>
                        </div>
                        <div class="text-right">
                            @if($payment->status === 'completed')
                                <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Completed
                                </span>
                            @elseif($payment->status === 'pending')
                                <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                    Pending
                                </span>
                            @else
                                <span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                    Failed
                                </span>
                            @endif
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Amount</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($payment->amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Method</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-sm rounded bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                    {{ strtoupper($payment->payment_method) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaction ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $payment->transaction_id ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone Number</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $payment->phone_number ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $payment->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                        @if($payment->processed_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Processed At</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $payment->processed_at->format('M d, Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if($payment->notes)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Notes</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $payment->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Related Invoice -->
            @if($payment->invoice)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Related Invoice</h3>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Invoice #{{ $payment->invoice->invoice_number }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Due: {{ $payment->invoice->due_date->format('M d, Y') }}</p>
                            </div>
                            <a href="{{ route('isp.invoices.show', $payment->invoice->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                View Invoice
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Customer Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $payment->customer->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $payment->customer->phone_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $payment->customer->location }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if($payment->status === 'pending' && $payment->payment_method === 'mpesa')
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                <strong>Payment Pending:</strong> Please check your phone and complete the MPesa payment. The payment will be automatically processed once confirmed.
                            </p>
                            <button onclick="checkPaymentStatus()" class="mt-2 text-sm text-yellow-800 dark:text-yellow-400 underline">
                                Refresh Status
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            @if($payment->status === 'pending' && $payment->payment_method === 'manual')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Manual Payment Actions</h3>
                        <div class="space-y-2">
                            <form method="POST" action="{{ route('payments.confirm-manual', $payment->id) }}">
                                @csrf
                                <input type="hidden" name="confirm" value="1">
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    Mark as Completed
                                </button>
                            </form>
                            <form method="POST" action="{{ route('payments.confirm-manual', $payment->id) }}">
                                @csrf
                                <input type="hidden" name="confirm" value="0">
                                <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                    Mark as Failed
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
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
                            title: 'Payment Completed!',
                            text: 'Your payment has been confirmed.',
                            confirmButtonColor: '#10b981',
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Payment Pending',
                            text: 'Payment is still pending. Please complete the payment on your phone.',
                            confirmButtonColor: '#3b82f6',
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Auto-refresh every 10 seconds for pending MPesa payments
        setInterval(function() {
            if (document.querySelector('.bg-yellow-50')) {
                checkPaymentStatus();
            }
        }, 10000);
    </script>
    @endif
@endsection

