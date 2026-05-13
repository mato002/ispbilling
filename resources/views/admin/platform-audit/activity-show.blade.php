<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Activity detail') }}
        </h2>
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.audit.activity') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">← Back to global activity</a>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-5 text-sm">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Date</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $activityLog->created_at->format('M j, Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">ISP</dt>
                <dd class="text-gray-800 dark:text-gray-100">{{ $activityLog->isp?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">User</dt>
                <dd class="text-gray-800 dark:text-gray-100">{{ $activityLog->user?->name ?? '—' }} <span class="text-gray-500">({{ $activityLog->user?->email ?? '—' }})</span></dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Action</dt>
                <dd class="font-mono text-gray-900 dark:text-gray-100">{{ $activityLog->action }}</dd>
            </div>
        </dl>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description</p>
            <p class="text-gray-700 dark:text-gray-200">{{ $activityLog->description }}</p>
        </div>
        @if ($activityLog->model_type)
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Model</p>
                <p class="font-mono text-gray-800 dark:text-gray-100">{{ class_basename($activityLog->model_type) }} #{{ $activityLog->model_id }}</p>
            </div>
        @endif
        @if ($activityLog->ip_address)
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">IP address</p>
                <p class="font-mono">{{ $activityLog->ip_address }}</p>
            </div>
        @endif
        @if (! empty($activityLog->old_values) || ! empty($activityLog->new_values))
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @if (! empty($activityLog->old_values))
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Old values</p>
                        <pre class="max-h-48 overflow-auto rounded-md border border-gray-200 bg-gray-50 p-3 text-xs dark:border-gray-700 dark:bg-gray-950">{{ json_encode($activityLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif
                @if (! empty($activityLog->new_values))
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">New values</p>
                        <pre class="max-h-48 overflow-auto rounded-md border border-gray-200 bg-gray-50 p-3 text-xs dark:border-gray-700 dark:bg-gray-950">{{ json_encode($activityLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif
            </div>
        @endif
        @if (! empty($activityLog->metadata))
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Metadata</p>
                <pre class="max-h-64 overflow-auto rounded-md border border-gray-200 bg-gray-50 p-3 text-xs dark:border-gray-700 dark:bg-gray-950">{{ json_encode($activityLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
    </div>
</x-admin-layout>
