@extends('layouts.admin')

@section('title', 'Plans')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Plans') }}
        </h2>
        <a href="{{ route('isp.plans.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Create Plan
        </a>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('isp.plans.index') }}" class="flex gap-4">
                <div>
                    <select name="access_type" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All Types</option>
                        <option value="pppoe" {{ request('access_type') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                        <option value="hotspot" {{ request('access_type') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                    </select>
                </div>
                <div>
                    <select name="is_active" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All Status</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    Filter
                </button>
                @if(request()->has('access_type') || request()->has('is_active'))
                    <a href="{{ route('isp.plans.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                        Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($plans as $plan)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $plan->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $plan->description }}</p>
                        </div>
                        @if($plan->is_active)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Active
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                Inactive
                            </span>
                        @endif
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Price:</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($plan->price, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Duration:</span>
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $plan->duration_days }} days</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Type:</span>
                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                {{ strtoupper($plan->access_type) }}
                            </span>
                        </div>
                        @if($plan->download_speed_kbps)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Speed:</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $plan->formatted_speed }}</span>
                            </div>
                        @endif
                        @if($plan->speed_profile)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Speed Profile:</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $plan->speed_profile }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Subscriptions:</span>
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $plan->subscriptions_count }}</span>
                        </div>
                    </div>

                    <div class="flex space-x-2 mt-4">
                        <a href="{{ route('isp.plans.edit', $plan->id) }}" class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            Edit
                        </a>
                        <a href="{{ route('isp.plans.show', $plan->id) }}" class="flex-1 text-center px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            View
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-gray-500 dark:text-gray-400">No plans found</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $plans->links() }}
    </div>
@endsection
