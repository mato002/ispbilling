@extends('layouts.admin')

@section('title', 'Invoice Details')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Invoice Details') }}
        </h2>
        <div class="flex space-x-2">
            <a href="{{ route('isp.invoices.show', $invoice->id) }}?pdf=1" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Download PDF
            </a>
            <a href="{{ route('isp.invoices.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Back to List
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Invoice Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Header -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Invoice #{{ $invoice->invoice_number }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $invoice->customer->full_name }}</p>
                        </div>
                        <div class="text-right">
                            @if($invoice->status === 'paid')
                                <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Paid
                                </span>
                            @elseif($invoice->status === 'overdue')
                                <span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                    Overdue
                                </span>
                            @else
                                <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                    Pending
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Issue Date</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $invoice->issue_date->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Due Date</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $invoice->due_date->format('M d, Y') }}
                                @if($invoice->isOverdue())
                                    <span class="text-red-600 dark:text-red-400">(Overdue)</span>
                                @endif
                            </p>
                        </div>
                        @if($invoice->paid_date)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid Date</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $invoice->paid_date->format('M d, Y') }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Invoice Items -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-2 text-sm font-medium text-gray-500 dark:text-gray-400">Description</th>
                                    <th class="text-right py-2 text-sm font-medium text-gray-500 dark:text-gray-400">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3 text-sm text-gray-900 dark:text-gray-100">
                                        @if($invoice->subscription)
                                            Subscription: {{ $invoice->subscription->plan->name }}
                                        @else
                                            Service Charge
                                        @endif
                                    </td>
                                    <td class="py-3 text-right text-sm text-gray-900 dark:text-gray-100">KES {{ number_format($invoice->amount, 2) }}</td>
                                </tr>
                                @if($invoice->tax > 0)
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="py-3 text-sm text-gray-900 dark:text-gray-100">Tax</td>
                                        <td class="py-3 text-right text-sm text-gray-900 dark:text-gray-100">KES {{ number_format($invoice->tax, 2) }}</td>
                                    </tr>
                                @endif
                                @if($invoice->penalty > 0)
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="py-3 text-sm text-gray-900 dark:text-gray-100">Penalty</td>
                                        <td class="py-3 text-right text-sm text-gray-900 dark:text-gray-100">KES {{ number_format($invoice->penalty, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Total</td>
                                    <td class="py-3 text-right text-lg font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($invoice->total_amount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if($invoice->notes)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Notes</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $invoice->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payments -->
            @if($invoice->payments->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Payments</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Reference</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Method</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Amount</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($invoice->payments as $payment)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $payment->payment_reference }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ strtoupper($payment->payment_method) }}</td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">KES {{ number_format($payment->amount, 2) }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $payment->created_at->format('M d, Y') }}</td>
                                            <td class="px-4 py-2">
                                                @if($payment->status === 'completed')
                                                    <span class="px-2 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Completed</span>
                                                @else
                                                    <span class="px-2 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">{{ ucfirst($payment->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->customer->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->customer->phone_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->customer->location }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if($invoice->status !== 'paid')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Pay Invoice</h3>
                        
                        <!-- Payment Method Selection -->
                        <form method="POST" action="{{ route('isp.payments.initiate.store') }}" id="paymentForm" class="space-y-4">
                            @csrf
                            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method *</label>
                                <select name="payment_method" id="payment_method" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="">Select Payment Method</option>
                                    <option value="mpesa">MPesa (STK Push)</option>
                                    <option value="manual">Manual Payment</option>
                                </select>
                            </div>

                            <div id="phone_number_field" style="display: none;">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number *</label>
                                <input type="text" name="phone_number" 
                                       value="{{ $invoice->customer->phone_number }}"
                                       placeholder="254XXXXXXXXX"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Format: 254XXXXXXXXX (e.g., 254712345678)</p>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    <strong>Amount to Pay:</strong> KES {{ number_format($invoice->total_amount, 2) }}
                                </p>
                            </div>

                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Initiate Payment
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.getElementById('payment_method').addEventListener('change', function() {
            const phoneField = document.getElementById('phone_number_field');
            if (this.value === 'mpesa') {
                phoneField.style.display = 'block';
                phoneField.querySelector('input').required = true;
            } else {
                phoneField.style.display = 'none';
                phoneField.querySelector('input').required = false;
            }
        });
    </script>
@endsection

