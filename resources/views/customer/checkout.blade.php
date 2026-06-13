<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $customer->isp->company_name ?? $customer->isp->name ?? 'Select a Plan' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
<div class="min-h-screen bg-gradient-to-br from-purple-500 via-blue-500 to-indigo-600 flex items-center justify-center p-2 sm:p-4">
    <div class="max-w-4xl w-full">
        <!-- Header -->
        <div class="text-center mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white mb-2">{{ $customer->isp->company_name ?? $customer->isp->name ?? 'Select a Plan' }}</h1>
            <p class="text-white/80 text-sm sm:text-base lg:text-lg">Select a plan to get started</p>
        </div>

        @if(session('info'))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-3 sm:px-4 py-2 sm:py-3 rounded mb-4 sm:mb-6 text-center text-sm sm:text-base">
                {{ session('info') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-2 sm:py-3 rounded mb-4 sm:mb-6 text-center text-sm sm:text-base">
                {{ session('error') }}
            </div>
        @endif

        @if (! empty(trim($paymentExperience['customer_instructions'] ?? '')))
            <div class="bg-white/95 text-gray-800 rounded-lg shadow-lg p-4 sm:p-5 mb-4 sm:mb-6 border border-white/40">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-2">Payment instructions</h2>
                <div class="text-sm sm:text-base text-gray-700 whitespace-pre-line">{{ $paymentExperience['customer_instructions'] }}</div>
            </div>
        @endif

        <!-- Pending Invoices Section -->
        @if($pendingInvoices->count() > 0)
            <div class="bg-white rounded-lg shadow-xl p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Pending Invoices</h2>
                <div class="space-y-4">
                    @foreach($pendingInvoices as $invoice)
                        <div class="border border-gray-200 rounded-lg p-4 flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ $invoice->invoice_number }}</h3>
                                <p class="text-sm text-gray-600">
                                    @if($invoice->subscription && $invoice->subscription->plan)
                                        Plan: {{ $invoice->subscription->plan->name }}
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500">Due: {{ $invoice->due_date->format('M d, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-gray-800">KES {{ number_format($invoice->total_amount, 2) }}</p>
                                <form method="POST" action="{{ route('customer.payments.initiate') }}" class="mt-2 payment-form" onsubmit="return false;">
                                    @csrf
                                    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                                    <div class="flex gap-2 items-center">
                                        <select name="payment_method" class="px-3 py-2 border border-gray-300 rounded text-sm" required>
                                            <option value="mpesa">MPesa</option>
                                            <option value="manual">Manual Payment</option>
                                        </select>
                                        <input type="hidden" name="phone_number" value="{{ $customer->phone_number }}" id="phone_{{ $invoice->id }}">
                                        <button type="submit" 
                                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                            Pay Now
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Available Plans -->
        <div class="bg-white rounded-lg shadow-xl p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Available Plans</h2>
            
            @if($availablePlans->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($availablePlans as $plan)
                        <div class="border-2 border-gray-200 rounded-lg p-4 sm:p-6 hover:border-blue-500 transition-colors {{ request('plan') == $plan->id ? 'border-blue-500 bg-blue-50' : '' }}">
                            <div class="text-center mb-4">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2">{{ $plan->name }}</h3>
                                @if($plan->description)
                                    <p class="text-xs sm:text-sm text-gray-600 mb-4">{{ $plan->description }}</p>
                                @endif
                            </div>

                            <div class="space-y-2 sm:space-y-3 mb-4 sm:mb-6">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm">Price:</span>
                                    <span class="text-lg sm:text-2xl font-bold text-gray-800">KES {{ number_format($plan->price, 2) }}</span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm">Duration:</span>
                                    <span class="font-semibold text-gray-800 text-sm sm:text-base">{{ $plan->duration_days }} days</span>
                                </div>

                                @if($plan->download_speed_kbps)
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 text-sm">Download:</span>
                                        <span class="font-semibold text-gray-800 text-sm sm:text-base">{{ number_format($plan->download_speed_kbps / 1024, 1) }} Mbps</span>
                                    </div>
                                @endif

                                @if($plan->upload_speed_kbps)
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 text-sm">Upload:</span>
                                        <span class="font-semibold text-gray-800 text-sm sm:text-base">{{ number_format($plan->upload_speed_kbps / 1024, 1) }} Mbps</span>
                                    </div>
                                @endif

                                @if($plan->billing_type === 'data' && $plan->data_limit_mb)
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 text-sm">Data Limit:</span>
                                        <span class="font-semibold text-gray-800 text-sm sm:text-base">{{ number_format($plan->data_limit_mb / 1024, 1) }} GB</span>
                                    </div>
                                @endif

                                @if($plan->billing_type === 'time' && $plan->time_limit_hours)
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Time Limit:</span>
                                        <span class="font-semibold text-gray-800">{{ $plan->time_limit_hours }} hours</span>
                                    </div>
                                @endif

                                @if($plan->billing_type === 'unlimited')
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Data:</span>
                                        <span class="font-semibold text-green-600">Unlimited</span>
                                    </div>
                                @endif
                            </div>

                            @php
                                $hasInvoice = request('invoice') && $pendingInvoices->contains('id', request('invoice'));
                            @endphp
                            
                            @if($hasInvoice)
                                @php
                                    $selectedInvoice = $pendingInvoices->firstWhere('id', request('invoice'));
                                @endphp
                                @if($selectedInvoice && (!$selectedInvoice->subscription || $selectedInvoice->subscription->plan_id == $plan->id))
                                    {{-- If invoice exists and matches this plan, show payment button --}}
                                    <form method="POST" action="{{ route('customer.payments.initiate') }}" class="mt-4 payment-form" onsubmit="return false;">
                                        @csrf
                                        <input type="hidden" name="invoice_id" value="{{ request('invoice') }}">
                                        <div class="mb-3">
                                            <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded" required>
                                                <option value="mpesa">MPesa</option>
                                                <option value="manual">Manual Payment</option>
                                            </select>
                                        </div>
                                        <input type="hidden" name="phone_number" value="{{ $customer->phone_number }}">

                                        <button type="submit" 
                                                class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all transform hover:scale-105">
                                            Pay KES {{ number_format($selectedInvoice->total_amount, 2) }}
                                        </button>
                                    </form>
                                @endif
                            @else
                                {{-- If no invoice, create one first --}}
                                <form method="POST" action="{{ route('customer.checkout.select-plan') }}" class="mt-4">
                                    @csrf
                                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                                    <button type="submit" 
                                            class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all transform hover:scale-105">
                                        Select Plan - KES {{ number_format($plan->price, 2) }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No plans available</h3>
                    <p class="mt-1 text-sm text-gray-500">Please contact support for assistance.</p>
                </div>
            @endif
        </div>

        <!-- Customer Info -->
        <div class="mt-6 text-center">
            <p class="text-white/80 text-sm">
                Customer: <strong>{{ $customer->full_name }}</strong> | 
                Phone: <strong>{{ $customer->phone_number }}</strong>
            </p>
            <a href="{{ route('hotspot.login') }}" class="text-white/80 hover:text-white text-sm underline mt-2 inline-block">
                Back to Login
            </a>
        </div>
    </div>
</div>

<script>
    // Handle payment form submission
    document.addEventListener('DOMContentLoaded', function() {
        // Find all payment forms
        const paymentForms = document.querySelectorAll('form.payment-form, form[action*="payments/initiate"]');
        
        if (paymentForms.length === 0) {
            console.log('No payment forms found');
        }
        
        paymentForms.forEach(form => {
            console.log('Found payment form:', form.action);
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const button = this.querySelector('button[type="submit"]');
                if (!button) {
                    console.error('Submit button not found');
                    return;
                }
                
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = 'Processing...';

                const formData = new FormData(this);
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                
                if (!csrfToken) {
                    alert('CSRF token not found. Please refresh the page.');
                    button.disabled = false;
                    button.textContent = originalText;
                    return;
                }
                
                console.log('Submitting payment form to:', this.action);
                
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || 'Payment initiation failed');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Payment response:', data);
                    if (data.success) {
                        alert(data.message || 'Payment initiated successfully! Please check your phone to complete the payment.');
                        // Redirect to status page after payment
                        setTimeout(() => {
                            window.location.href = '{{ route("hotspot.status") }}?username={{ $customer->username }}&token=' + btoa(JSON.stringify({customer_id: {{ $customer->id }}}));
                        }, 2000);
                    } else {
                        alert(data.message || 'Payment initiation failed. Please try again.');
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Payment error:', error);
                    alert('Error: ' + (error.message || 'An error occurred. Please try again.'));
                    button.disabled = false;
                    button.textContent = originalText;
                });
                
                return false;
            });
        });
    });
</script>
</body>
</html>

