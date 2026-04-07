@extends('layouts.admin')

@section('title', 'Payment Settings')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Payment Settings') }}
        </h2>
        <a href="{{ route('isp.payment-settings.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Add Payment Method
        </a>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Payment Methods Overview -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Configured Payment Methods</h3>
                
                @if($groupedMethods->isEmpty())
                    <div class="text-center py-8">
                        <i class="fas fa-credit-card text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">No payment methods configured yet.</p>
                        <a href="{{ route('isp.payment-settings.create') }}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add Your First Payment Method
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($groupedMethods as $provider => $methods)
                            @foreach($methods as $method)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ $method->active ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            @switch($provider)
                                                @case('mpesa')
                                                    <i class="fas fa-mobile-alt text-green-600 text-xl mr-3"></i>
                                                    <span class="font-medium">M-Pesa</span>
                                                    @break
                                                @case('paypal')
                                                    <i class="fab fa-paypal text-blue-600 text-xl mr-3"></i>
                                                    <span class="font-medium">PayPal</span>
                                                    @break
                                                @case('stripe')
                                                    <i class="fab fa-stripe text-purple-600 text-xl mr-3"></i>
                                                    <span class="font-medium">Stripe</span>
                                                    @break
                                                @case('flutterwave')
                                                    <i class="fas fa-wave-square text-orange-600 text-xl mr-3"></i>
                                                    <span class="font-medium">Flutterwave</span>
                                                    @break
                                                @case('selcom')
                                                    <i class="fas fa-mobile-alt text-blue-600 text-xl mr-3"></i>
                                                    <span class="font-medium">Selcom</span>
                                                    @break
                                                @default
                                                    <i class="fas fa-credit-card text-gray-600 text-xl mr-3"></i>
                                                    <span class="font-medium">{{ ucfirst($provider) }}</span>
                                            @endswitch
                                        </div>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $method->active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $method->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                        <p>Environment: {{ $method->credentials['environment'] ?? 'N/A' }}</p>
                                        @if($method->callback_url)
                                            <p>Callback: {{ Str::limit($method->callback_url, 30) }}</p>
                                        @endif
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <div class="space-x-2">
                                            <a href="{{ route('isp.payment-settings.edit', $method) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('isp.payment-settings.destroy', $method) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <form action="{{ route('isp.payment-settings.toggle', $method) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 text-xs rounded {{ $method->active ? 'bg-gray-200 hover:bg-gray-300 text-gray-700' : 'bg-green-600 hover:bg-green-700 text-white' }}">
                                                {{ $method->active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Integration Guide -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Integration Guide</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- M-Pesa Integration -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <i class="fas fa-mobile-alt text-green-600 text-xl mr-3"></i>
                            <h4 class="font-medium">M-Pesa Integration</h4>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                            <p><strong>Required Credentials:</strong></p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Consumer Key & Secret</li>
                                <li>Passkey</li>
                                <li>Shortcode (Paybill/Till Number)</li>
                                <li>Environment (Sandbox/Production)</li>
                            </ul>
                            <p class="mt-2"><strong>Flow:</strong> User enters phone → STK push → Confirmation → Internet activation</p>
                        </div>
                    </div>

                    <!-- PayPal Integration -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <i class="fab fa-paypal text-blue-600 text-xl mr-3"></i>
                            <h4 class="font-medium">PayPal Integration</h4>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                            <p><strong>Required Credentials:</strong></p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Client ID</li>
                                <li>Client Secret</li>
                                <li>Environment (Sandbox/Production)</li>
                            </ul>
                            <p class="mt-2"><strong>Flow:</strong> User selects package → PayPal checkout → Payment confirmation → Internet activation</p>
                        </div>
                    </div>

                    <!-- Stripe Integration -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <i class="fab fa-stripe text-purple-600 text-xl mr-3"></i>
                            <h4 class="font-medium">Stripe (Card Payments)</h4>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                            <p><strong>Required Credentials:</strong></p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Publishable Key</li>
                                <li>Secret Key</li>
                                <li>Webhook Secret (optional)</li>
                            </ul>
                            <p class="mt-2"><strong>Flow:</strong> User enters card details → Stripe processing → Payment confirmation → Internet activation</p>
                        </div>
                    </div>

                    <!-- Flutterwave Integration -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <i class="fas fa-wave-square text-orange-600 text-xl mr-3"></i>
                            <h4 class="font-medium">Flutterwave</h4>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                            <p><strong>Required Credentials:</strong></p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Public Key</li>
                                <li>Secret Key</li>
                                <li>Encryption Key</li>
                                <li>Environment (Sandbox/Production)</li>
                            </ul>
                            <p class="mt-2"><strong>Flow:</strong> Multi-method payment → Flutterwave processing → Confirmation → Internet activation</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
