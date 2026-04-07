@extends('layouts.admin')

@section('title', 'Payment Method Settings')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Payment Method Settings') }}
    </h2>
@endsection

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Settings Navigation -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Settings</h3>
                    <nav class="space-y-2">
                        <a href="{{ route('isp.settings.company') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.company*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg font-medium">
                            Company Information
                        </a>
                        <a href="{{ route('isp.settings.payment') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.payment*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg">
                            Payment Method (MPESA)
                        </a>
                        <a href="{{ route('isp.settings.notifications') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.notifications*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg">
                            Notification Preferences
                        </a>
                        <a href="{{ route('isp.settings.users') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.users*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg">
                            User Management
                        </a>
                        <a href="{{ route('isp.settings.billing') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.billing*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg">
                            Billing Settings
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Payment Method Settings -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">MPESA Daraja Configuration</h3>
                        @if($paymentMethod && $paymentMethod->active)
                            <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Active
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                Inactive
                            </span>
                        @endif
                    </div>
                    
                    <form method="POST" action="{{ route('isp.settings.payment.store') }}" class="space-y-6">
                        @csrf
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Consumer Key <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="consumer_key" 
                                       value="{{ old('consumer_key', $paymentMethod ? $paymentMethod->getCredential('consumer_key') : '') }}"
                                       required
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 @error('consumer_key') border-red-500 @enderror"
                                       placeholder="Enter MPESA Consumer Key">
                                @error('consumer_key')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Consumer Secret <span class="text-red-500">*</span>
                                </label>
                                <input type="password" 
                                       name="consumer_secret" 
                                       value="{{ old('consumer_secret', $paymentMethod ? $paymentMethod->getCredential('consumer_secret') : '') }}"
                                       required
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 @error('consumer_secret') border-red-500 @enderror"
                                       placeholder="Enter MPESA Consumer Secret">
                                @error('consumer_secret')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Shortcode <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="shortcode" 
                                       value="{{ old('shortcode', $paymentMethod ? $paymentMethod->getCredential('shortcode') : '') }}"
                                       required
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 @error('shortcode') border-red-500 @enderror"
                                       placeholder="Enter MPESA Shortcode">
                                @error('shortcode')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Passkey <span class="text-red-500">*</span>
                                </label>
                                <input type="password" 
                                       name="passkey" 
                                       value="{{ old('passkey', $paymentMethod ? $paymentMethod->getCredential('passkey') : '') }}"
                                       required
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 @error('passkey') border-red-500 @enderror"
                                       placeholder="Enter MPESA Passkey">
                                @error('passkey')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Callback URL
                                </label>
                                <input type="url" 
                                       name="callback_url" 
                                       value="{{ old('callback_url', $paymentMethod ? $paymentMethod->callback_url : url('/api/payments/mpesa/callback')) }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 @error('callback_url') border-red-500 @enderror"
                                       placeholder="Default: System callback URL">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Default: {{ url('/api/payments/mpesa/callback') }}
                                </p>
                                @error('callback_url')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" 
                                       name="active" 
                                       value="1" 
                                       {{ old('active', $paymentMethod && $paymentMethod->active) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Activate this payment method
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                @if($paymentMethod)
                                    <form method="POST" action="{{ route('isp.settings.payment.destroy', $paymentMethod) }}" 
                                          onsubmit="confirmFormSubmit(event, {title: 'Delete Payment Method', text: 'Are you sure you want to delete this payment method? This action cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, delete', confirmButtonColor: '#ef4444'}); return false;"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                {{ $paymentMethod ? 'Update' : 'Create' }} Payment Method
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

