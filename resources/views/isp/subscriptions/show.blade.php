@extends('layouts.admin')

@section('title', 'Subscription Details')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Subscription Details') }}
        </h2>
        <a href="{{ route('isp.subscriptions.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to List
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Subscription Information -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Subscription #{{ $subscription->id }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $subscription->plan->name }}</p>
                        </div>
                        @if($subscription->status === 'active')
                            <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Active
                            </span>
                        @elseif($subscription->status === 'expired')
                            <span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                Expired
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $subscription->customer->full_name }}</dd>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">{{ $subscription->customer->phone_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan</dt>
                            <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $subscription->plan->name }}</dd>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">KES {{ number_format($subscription->plan->price, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $subscription->start_date->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $subscription->end_date->format('M d, Y') }}
                                @if($subscription->end_date < now() && $subscription->status === 'active')
                                    <span class="text-red-600 dark:text-red-400 text-xs">(Expired)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Renewal Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $subscription->renewal_date ? $subscription->renewal_date->format('M d, Y') : 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Auto Renew</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $subscription->auto_renew ? 'Yes' : 'No' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        @if($subscription->status === 'active' || $subscription->status === 'expired')
                            <form method="POST" action="{{ route('isp.subscriptions.renew', $subscription->id) }}" onsubmit="confirmFormSubmit(event, {title: 'Renew Subscription', text: 'Are you sure you want to renew this subscription?', icon: 'question', confirmButtonText: 'Yes, renew'}); return false;">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600">
                                    Renew Subscription
                                </button>
                            </form>
                        @endif
                        @if($subscription->status === 'active')
                            <form method="POST" action="{{ route('isp.subscriptions.cancel', $subscription->id) }}" onsubmit="confirmFormSubmit(event, {title: 'Cancel Subscription', text: 'Are you sure you want to cancel this subscription? This action cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, cancel', confirmButtonColor: '#ef4444'}); return false;">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 dark:bg-yellow-700 dark:hover:bg-yellow-600">
                                    Cancel Subscription
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('isp.customers.show', $subscription->customer_id) }}" class="block w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-center">
                            View Customer
                        </a>
                        <a href="{{ route('isp.plans.show', $subscription->plan_id) }}" class="block w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-center">
                            View Plan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

