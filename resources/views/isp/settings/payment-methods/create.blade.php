@extends('layouts.admin')

@section('title', 'Add Payment Method')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Payment Method') }}
        </h2>
        <a href="{{ route('isp.payment-settings.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to Settings
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('isp.payment-settings.store') }}" class="space-y-6">
                @csrf
                
                <!-- Provider Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Provider *</label>
                    <select name="provider" id="provider" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            onchange="toggleProviderFields()">
                        <option value="">Select a payment provider</option>
                        @foreach($providers as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('provider')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- M-Pesa Fields -->
                <div id="mpesa-fields" class="hidden space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">M-Pesa Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Consumer Key *</label>
                            <input type="text" name="consumer_key" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Daraja developer portal">
                            @error('consumer_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Consumer Secret *</label>
                            <input type="password" name="consumer_secret" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Daraja developer portal">
                            @error('consumer_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Passkey *</label>
                            <input type="password" name="passkey" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Daraja developer portal">
                            @error('passkey')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shortcode *</label>
                            <input type="text" name="shortcode" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Paybill or Till Number">
                            @error('shortcode')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- PayPal Fields -->
                <div id="paypal-fields" class="hidden space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">PayPal Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client ID *</label>
                            <input type="text" name="client_id" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From PayPal Developer Dashboard">
                            @error('client_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client Secret *</label>
                            <input type="password" name="client_secret" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From PayPal Developer Dashboard">
                            @error('client_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Stripe Fields -->
                <div id="stripe-fields" class="hidden space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Stripe Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Publishable Key *</label>
                            <input type="text" name="publishable_key" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="pk_test_... or pk_live_...">
                            @error('publishable_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Secret Key *</label>
                            <input type="password" name="secret_key" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="sk_test_... or sk_live_...">
                            @error('secret_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Webhook Secret</label>
                            <input type="password" name="webhook_secret" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Optional, for webhook verification">
                            @error('webhook_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Flutterwave Fields -->
                <div id="flutterwave-fields" class="hidden space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Flutterwave Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Public Key *</label>
                            <input type="text" name="public_key" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="FLWPUBK_TEST-... or FLWPUBK_LIVE-...">
                            @error('public_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Secret Key *</label>
                            <input type="password" name="secret_key" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="FLWSECK_TEST-... or FLWSECK_LIVE-...">
                            @error('secret_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Encryption Key *</label>
                            <input type="password" name="encryption_key" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Flutterwave dashboard">
                            @error('encryption_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Selcom Fields -->
                <div id="selcom-fields" class="hidden space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Selcom Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Key *</label>
                            <input type="text" name="api_key" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Selcom developer portal">
                            @error('api_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Secret *</label>
                            <input type="password" name="api_secret" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Selcom developer portal">
                            @error('api_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Vendor *</label>
                            <input type="text" name="vendor" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Vendor identifier">
                            @error('vendor')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Environment Selection -->
                <div id="environment-fields" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Environment *</label>
                    <select name="environment" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="sandbox">Sandbox (Testing)</option>
                        <option value="production">Production (Live)</option>
                    </select>
                    @error('environment')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Common Fields -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Callback URL</label>
                    <input type="url" name="callback_url" 
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                           placeholder="https://your-domain.com/payment/callback">
                    @error('callback_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="active" value="1" id="active" 
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Activate this payment method immediately</label>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('isp.payment-settings.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Add Payment Method
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleProviderFields() {
            const provider = document.getElementById('provider').value;
            const environmentFields = document.getElementById('environment-fields');
            
            // Hide all provider-specific fields
            const allFields = ['mpesa-fields', 'paypal-fields', 'stripe-fields', 'flutterwave-fields', 'selcom-fields'];
            allFields.forEach(fieldId => {
                document.getElementById(fieldId).classList.add('hidden');
            });
            
            // Show selected provider fields and environment if needed
            const providersNeedingEnvironment = ['mpesa', 'paypal', 'flutterwave', 'selcom'];
            
            if (providersNeedingEnvironment.includes(provider)) {
                environmentFields.classList.remove('hidden');
            } else {
                environmentFields.classList.add('hidden');
            }
            
            // Show selected provider fields
            if (provider) {
                document.getElementById(provider + '-fields').classList.remove('hidden');
            }
        }
    </script>
@endsection
