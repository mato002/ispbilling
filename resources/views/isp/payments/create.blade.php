@extends('layouts.admin')

@section('title', 'Initiate Payment')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Initiate Payment') }}
        </h2>
        <a href="{{ route('isp.payments.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to List
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form id="paymentForm" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Invoice *</label>
                        <select name="invoice_id" id="invoice_id" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">Select Invoice</option>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice->id }}" 
                                        data-amount="{{ $invoice->total_amount }}"
                                        data-customer-phone="{{ $invoice->customer->phone_number }}">
                                    {{ $invoice->invoice_number }} - {{ $invoice->customer->full_name }} - KES {{ number_format($invoice->total_amount, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method *</label>
                        <select name="payment_method" id="payment_method" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="mpesa">MPesa</option>
                            <option value="selcom">Selcom</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>

                    <div id="phone_number_field">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number *</label>
                        <input type="text" name="phone_number" id="phone_number" 
                               placeholder="254712345678"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount</label>
                        <input type="text" id="amount_display" readonly
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 bg-gray-100"
                               value="KES 0.00">
                    </div>
                </div>

                <!-- Payment Status -->
                <div id="payment_status" class="hidden">
                    <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center">
                            <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-blue-800 dark:text-blue-200" id="status_message">Processing payment...</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('isp.payments.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" id="submit_btn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Initiate Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('invoice_id').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const amount = option.getAttribute('data-amount');
            const phone = option.getAttribute('data-customer-phone');
            
            if (amount) {
                document.getElementById('amount_display').value = 'KES ' + parseFloat(amount).toLocaleString('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            
            if (phone) {
                // Format phone number
                let formattedPhone = phone.replace(/[^0-9]/g, '');
                if (formattedPhone.startsWith('0')) {
                    formattedPhone = '254' + formattedPhone.substring(1);
                } else if (!formattedPhone.startsWith('254')) {
                    formattedPhone = '254' + formattedPhone;
                }
                document.getElementById('phone_number').value = formattedPhone;
            }
        });

        document.getElementById('payment_method').addEventListener('change', function() {
            const phoneField = document.getElementById('phone_number_field');
            if (this.value === 'mpesa' || this.value === 'selcom') {
                phoneField.style.display = 'block';
                document.getElementById('phone_number').required = true;
            } else {
                phoneField.style.display = 'none';
                document.getElementById('phone_number').required = false;
            }
        });

        document.getElementById('paymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submit_btn');
            const statusDiv = document.getElementById('payment_status');
            const statusMessage = document.getElementById('status_message');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            statusDiv.classList.remove('hidden');
            statusMessage.textContent = 'Initiating payment...';

            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('{{ route("isp.payments.initiate.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    statusDiv.className = 'p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800';
                    statusMessage.textContent = result.message || 'Payment initiated successfully!';
                    
                    if (data.payment_method === 'mpesa') {
                        statusMessage.textContent += ' Please check your phone to complete the payment.';
                    }

                    const nextUrl = result.payment_url || result.redirect || '{{ route("isp.payments.index") }}';
                    setTimeout(() => {
                        window.location.href = nextUrl;
                    }, result.payment_url ? 800 : 2000);
                } else {
                    statusDiv.className = 'p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
                    statusMessage.textContent = result.message || 'Payment initiation failed.';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Initiate Payment';
                }
            } catch (error) {
                statusDiv.className = 'p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
                statusMessage.textContent = 'An error occurred: ' + error.message;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Initiate Payment';
            }
        });
    </script>
@endsection

