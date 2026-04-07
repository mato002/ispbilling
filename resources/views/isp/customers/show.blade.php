@extends('layouts.admin')

@section('title', 'Customer Details')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Customer Details') }}
        </h2>
        <div class="flex space-x-2">
            <a href="{{ route('isp.customers.edit', $customer->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit
            </a>
            <a href="{{ route('isp.customers.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Back to List
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Customer Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Details -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Personal Information</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Full Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $customer->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone Number</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $customer->phone_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $customer->email ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $customer->location }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Installation Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $customer->installation_date->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                @if($customer->status === 'active')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                        Active
                                    </span>
                                @elseif($customer->status === 'suspended')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                        Suspended
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                        {{ ucfirst($customer->status) }}
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Connection Details -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Connection Information</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Username</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $customer->username }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Connection Type</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                    {{ strtoupper($customer->connection_type) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Router</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $customer->router->name ?? 'N/A' }} 
                                @if($customer->router)
                                    <span class="text-gray-500">({{ $customer->router->ip_address }})</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Plan</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @php
                                    $activeSubscription = $customer->subscriptions->first();
                                @endphp
                                @if($activeSubscription && $activeSubscription->plan)
                                    {{ $activeSubscription->plan->name }}
                                @else
                                    <span class="text-gray-400">No active plan</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Active Subscription -->
            @php
                $activeSubscription = $customer->subscriptions->first();
            @endphp
            @if($activeSubscription)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Active Subscription</h3>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $activeSubscription->plan->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $activeSubscription->start_date->format('M d, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $activeSubscription->end_date->format('M d, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Auto Renew</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $activeSubscription->auto_renew ? 'Yes' : 'No' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        @if($customer->status === 'active')
                            <form method="POST" action="{{ route('isp.customers.suspend', $customer->id) }}" class="inline-block w-full" onsubmit="confirmFormSubmit(event, {title: 'Suspend Customer', text: 'Are you sure you want to suspend {{ $customer->full_name }}? This will disconnect their internet access.', icon: 'warning', confirmButtonText: 'Yes, suspend'}); return false;">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 dark:bg-yellow-700 dark:hover:bg-yellow-600">
                                    Suspend Customer
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('isp.customers.activate', $customer->id) }}" class="inline-block w-full" onsubmit="confirmFormSubmit(event, {title: 'Activate Customer', text: 'Are you sure you want to activate {{ $customer->full_name }}?', icon: 'question', confirmButtonText: 'Yes, activate'}); return false;">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600">
                                    Activate Customer
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('isp.invoices.create-for-customer', $customer->id) }}" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-center">
                            Create Invoice
                        </a>
                        <a href="{{ route('isp.payments.customer', $customer->id) }}" class="block px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 dark:bg-purple-700 dark:hover:bg-purple-600 text-center">
                            View Payments
                        </a>
                        <a href="{{ route('isp.sessions.customer-history', $customer->id) }}" class="block px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-600 text-center">
                            View Sessions
                        </a>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($customer->notes)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Notes</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $customer->notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

