@extends('layouts.admin')

@section('title', 'Edit Payment Method')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Payment Method') }}
        </h2>
        <a href="{{ route('isp.payment-settings.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to Settings
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('isp.payment-settings.update', $paymentMethod) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <!-- Provider Selection (Readonly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Provider</label>
                    <input type="text" value="{{ $providers[$paymentMethod->provider] ?? $paymentMethod->provider }}" readonly
                           class="w-full rounded-md border-gray-300 bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                    <input type="hidden" name="provider" value="{{ $paymentMethod->provider }}">
                </div>

                <!-- M-Pesa Fields -->
                @if($paymentMethod->provider === 'mpesa')
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">M-Pesa Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Consumer Key *</label>
                            <input type="text" name="consumer_key" value="{{ $paymentMethod->getCredential('consumer_key') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Daraja developer portal">
                            @error('consumer_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Consumer Secret *</label>
                            <input type="password" name="consumer_secret" value="{{ $paymentMethod->getCredential('consumer_secret') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Daraja developer portal">
                            @error('consumer_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Passkey *</label>
                            <input type="password" name="passkey" value="{{ $paymentMethod->getCredential('passkey') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Daraja developer portal">
                            @error('passkey')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shortcode *</label>
                            <input type="text" name="shortcode" value="{{ $paymentMethod->getCredential('shortcode') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Paybill or Till Number">
                            @error('shortcode')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                @endif

                <!-- PayPal Fields -->
                @if($paymentMethod->provider === 'paypal')
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">PayPal Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client ID *</label>
                            <input type="text" name="client_id" value="{{ $paymentMethod->getCredential('client_id') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From PayPal Developer Dashboard">
                            @error('client_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client Secret *</label>
                            <input type="password" name="client_secret" value="{{ $paymentMethod->getCredential('client_secret') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From PayPal Developer Dashboard">
                            @error('client_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                @endif

                <!-- Stripe Fields -->
                @if($paymentMethod->provider === 'stripe')
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Stripe Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Publishable Key *</label>
                            <input type="text" name="publishable_key" value="{{ $paymentMethod->getCredential('publishable_key') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="pk_test_... or pk_live_...">
                            @error('publishable_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Secret Key *</label>
                            <input type="password" name="secret_key" value="{{ $paymentMethod->getCredential('secret_key') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="sk_test_... or sk_live_...">
                            @error('secret_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Webhook Secret</label>
                            <input type="password" name="webhook_secret" value="{{ $paymentMethod->getCredential('webhook_secret') }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Optional, for webhook verification">
                            @error('webhook_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                @endif

                <!-- Flutterwave Fields -->
                @if($paymentMethod->provider === 'flutterwave')
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Flutterwave Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Public Key *</label>
                            <input type="text" name="public_key" value="{{ $paymentMethod->getCredential('public_key') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="FLWPUBK_TEST-... or FLWPUBK_LIVE-...">
                            @error('public_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Secret Key *</label>
                            <input type="password" name="secret_key" value="{{ $paymentMethod->getCredential('secret_key') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="FLWSECK_TEST-... or FLWSECK_LIVE-...">
                            @error('secret_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Encryption Key *</label>
                            <input type="password" name="encryption_key" value="{{ $paymentMethod->getCredential('encryption_key') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Flutterwave dashboard">
                            @error('encryption_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                @endif

                <!-- Selcom Fields -->
                @if($paymentMethod->provider === 'selcom')
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Selcom Configuration</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Key *</label>
                            <input type="text" name="api_key" value="{{ $paymentMethod->getCredential('api_key') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Selcom developer portal">
                            @error('api_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Secret *</label>
                            <input type="password" name="api_secret" value="{{ $paymentMethod->getCredential('api_secret') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="From Selcom developer portal">
                            @error('api_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Vendor *</label>
                            <input type="text" name="vendor" value="{{ $paymentMethod->getCredential('vendor') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Vendor identifier">
                            @error('vendor')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                @endif

                <!-- Environment Selection -->
                @if(in_array($paymentMethod->provider, ['mpesa', 'paypal', 'flutterwave', 'selcom']))
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Environment *</label>
                    <select name="environment" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="sandbox" {{ $paymentMethod->getCredential('environment') === 'sandbox' ? 'selected' : '' }}>Sandbox (Testing)</option>
                        <option value="production" {{ $paymentMethod->getCredential('environment') === 'production' ? 'selected' : '' }}>Production (Live)</option>
                    </select>
                    @error('environment')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <!-- Common Fields -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Callback URL</label>
                    <input type="url" name="callback_url" value="{{ $paymentMethod->callback_url }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                           placeholder="https://your-domain.com/payment/callback">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional: URL for payment status notifications</p>
                    @error('callback_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="active" value="1" {{ $paymentMethod->active ? 'checked' : '' }} id="active"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Activate this payment method</label>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('isp.payment-settings.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Payment Method
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
