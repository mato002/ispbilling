@extends('layouts.admin')

@section('title', 'Router Details')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Router Details') }}
        </h2>
        <div class="flex space-x-2">
            <a href="{{ route('isp.routers.edit', $router->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit
            </a>
            <a href="{{ route('isp.routers.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Back to List
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Router Information -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $router->name }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $router->location ?? 'No location specified' }}</p>
                        </div>
                        @if($router->is_active)
                            <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Active
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                Inactive
                            </span>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $router->ip_address }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Port</dt>
                            <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                                {{ $router->port }}
                                @if($router->use_ssl)
                                    <span class="text-green-600 dark:text-green-400 text-sm">(SSL)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">API URL</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $router->getApiUrl() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Connected Customers</dt>
                            <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $router->customers_count }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Connection Test -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Connection Test</h3>
                    <button type="button" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        Test Connection
                    </button>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Test the connection to this MikroTik router</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('isp.routers.edit', $router->id) }}" class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Edit Router
                        </a>
                        <form method="POST" action="{{ route('isp.routers.test', $router->id) }}" class="inline-block w-full" onsubmit="confirmFormSubmit(event, {title: 'Test Connection', text: 'Test connection to {{ $router->name }} ({{ $router->ip_address }})?', icon: 'question', confirmButtonText: 'Yes, test'}); return false;">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 dark:bg-purple-700 dark:hover:bg-purple-600">
                                Test Connection
                            </button>
                        </form>
                        @if($router->is_active)
                            <form method="POST" action="{{ route('isp.routers.deactivate', $router->id) }}" class="inline-block w-full" onsubmit="confirmFormSubmit(event, {title: 'Deactivate Router', text: 'Are you sure you want to deactivate {{ $router->name }}?', icon: 'warning', confirmButtonText: 'Yes, deactivate'}); return false;">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 dark:bg-yellow-700 dark:hover:bg-yellow-600">
                                    Deactivate
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('isp.routers.activate', $router->id) }}" class="inline-block w-full" onsubmit="confirmFormSubmit(event, {title: 'Activate Router', text: 'Are you sure you want to activate {{ $router->name }}?', icon: 'question', confirmButtonText: 'Yes, activate'}); return false;">
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

