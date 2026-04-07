@extends('layouts.admin')

@section('title', 'Router Action Log Details')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Router Action Log Details') }}
        </h2>
        <a href="{{ route('isp.logs.router-actions.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to Router Action Logs
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 space-y-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</p>
                <p class="text-gray-900 dark:text-gray-100">{{ $routerActionLog->created_at->format('M d, Y H:i:s') }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Router</p>
                <p class="text-gray-900 dark:text-gray-100">{{ $routerActionLog->router?->name ?? '—' }} ({{ $routerActionLog->router?->ip_address ?? '—' }})</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Action</p>
                <p class="text-gray-900 dark:text-gray-100">{{ $routerActionLog->action }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                <p class="text-gray-900 dark:text-gray-100">{{ $routerActionLog->status ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</p>
                <p class="text-gray-900 dark:text-gray-100">{{ $routerActionLog->description ?? '—' }}</p>
            </div>
            @if($routerActionLog->customer)
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $routerActionLog->customer->full_name }} ({{ $routerActionLog->customer->username ?? '—' }})</p>
                </div>
            @endif
            @if($routerActionLog->error_message)
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Error</p>
                    <p class="text-red-600 dark:text-red-400">{{ $routerActionLog->error_message }}</p>
                </div>
            @endif
            @if(!empty($routerActionLog->request_data))
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Request data</p>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-4 rounded overflow-auto max-h-48">{{ json_encode($routerActionLog->request_data, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
            @if(!empty($routerActionLog->response_data))
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Response data</p>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-4 rounded overflow-auto max-h-48">{{ json_encode($routerActionLog->response_data, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
    </div>
@endsection
