@extends('layouts.admin')

@section('title', 'Plan Details')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Plan Details') }}
        </h2>
        <div class="flex space-x-2">
            <a href="{{ route('isp.plans.edit', $plan->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit
            </a>
            <a href="{{ route('isp.plans.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Back to List
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Plan Information -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $plan->name }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $plan->description }}</p>
                        </div>
                        @if($plan->is_active)
                            <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Active
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                Inactive
                            </span>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Price</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($plan->price, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</dt>
                            <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $plan->duration_days }} days</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Access Type</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                    {{ strtoupper($plan->access_type) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Billing Type</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($plan->billing_type) }}</dd>
                        </div>
                        @if($plan->download_speed_kbps)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Speed</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->formatted_speed }}</dd>
                            </div>
                        @endif
                        @if($plan->data_limit_mb)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data Limit</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($plan->data_limit_mb) }} MB</dd>
                            </div>
                        @endif
                        @if($plan->time_limit_hours)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Time Limit</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->time_limit_hours }} hours</dd>
                            </div>
                        @endif
                        @if($plan->speed_profile)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Speed Profile Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->speed_profile }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Subscriptions</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->subscriptions_count }}</dd>
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
                        <a href="{{ route('isp.plans.edit', $plan->id) }}" class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Edit Plan
                        </a>
                        @if($plan->is_active)
                            <form method="POST" action="{{ route('isp.plans.deactivate', $plan->id) }}" class="inline-block w-full" onsubmit="confirmFormSubmit(event, {title: 'Deactivate Plan', text: 'Are you sure you want to deactivate {{ $plan->name }}? Existing subscriptions will not be affected.', icon: 'warning', confirmButtonText: 'Yes, deactivate'}); return false;">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 dark:bg-yellow-700 dark:hover:bg-yellow-600">
                                    Deactivate
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('isp.plans.activate', $plan->id) }}" class="inline-block w-full" onsubmit="confirmFormSubmit(event, {title: 'Activate Plan', text: 'Are you sure you want to activate {{ $plan->name }}?', icon: 'question', confirmButtonText: 'Yes, activate'}); return false;">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600">
                                    Activate
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

