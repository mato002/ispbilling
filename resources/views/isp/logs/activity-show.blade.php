@extends('layouts.admin')

@section('title', 'Activity Log Details')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Activity Log Details') }}</h2>
        <a href="{{ route('isp.logs.activity.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Back to Activity Logs</a>
    </div>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 space-y-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</p>
                <p class="text-gray-900 dark:text-gray-100">{{ $activityLog->created_at->format('M d, Y H:i:s') }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">User</p>
                <p class="text-gray-900 dark:text-gray-100">{{ $activityLog->user?->name ?? '—' }} ({{ $activityLog->user?->email ?? '—' }})</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Action</p>
                <p class="text-gray-900 dark:text-gray-100">{{ $activityLog->action }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</p>
                <p class="text-gray-900 dark:text-gray-100">{{ $activityLog->description }}</p>
            </div>
            @if($activityLog->model_type)
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Model</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ class_basename($activityLog->model_type) }} #{{ $activityLog->model_id }}</p>
                </div>
            @endif
            @if($activityLog->ip_address)
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $activityLog->ip_address }}</p>
                </div>
            @endif
            @if(!empty($activityLog->old_values) || !empty($activityLog->new_values))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if(!empty($activityLog->old_values))
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Old values</p>
                            <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-4 rounded overflow-auto max-h-48">{{ json_encode($activityLog->old_values, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                    @if(!empty($activityLog->new_values))
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">New values</p>
                            <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-4 rounded overflow-auto max-h-48">{{ json_encode($activityLog->new_values, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>
            @endif
            @if(!empty($activityLog->metadata))
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Metadata</p>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-4 rounded overflow-auto">{{ json_encode($activityLog->metadata, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
    </div>
@endsection
