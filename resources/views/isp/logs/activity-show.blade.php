@extends('layouts.admin')

@section('title', 'Activity log')

@section('header')
    <x-isp-page-header
        title="Activity log detail"
        module="Audit"
        description="Staff action record with context and changed fields when available."
        primaryLabel="Back to list"
        :primaryHref="route('isp.logs.activity.index')"
    />
@endsection

@section('content')
<div class="pb-card p-4 sm:p-6 space-y-5 text-sm">
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <dt class="isp-form-label mb-1">Date</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $activityLog->created_at->format('M j, Y H:i:s') }}</dd>
        </div>
        <div>
            <dt class="isp-form-label mb-1">User</dt>
            <dd class="text-slate-800 dark:text-slate-100">{{ $activityLog->user?->name ?? '—' }} <span class="text-slate-500">({{ $activityLog->user?->email ?? '—' }})</span></dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="isp-form-label mb-1">Action</dt>
            <dd class="font-mono text-slate-900 dark:text-white">{{ $activityLog->action }}</dd>
        </div>
    </dl>
    <div>
        <p class="isp-form-label mb-1">Description</p>
        <p class="text-slate-700 dark:text-slate-300">{{ $activityLog->description }}</p>
    </div>
    @if($activityLog->model_type)
        <div>
            <p class="isp-form-label mb-1">Model</p>
            <p class="font-mono text-slate-800 dark:text-slate-100">{{ class_basename($activityLog->model_type) }} #{{ $activityLog->model_id }}</p>
        </div>
    @endif
    @if($activityLog->ip_address)
        <div>
            <p class="isp-form-label mb-1">IP address</p>
            <p class="font-mono">{{ $activityLog->ip_address }}</p>
        </div>
    @endif
    @if(!empty($activityLog->old_values) || !empty($activityLog->new_values))
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @if(!empty($activityLog->old_values))
                <div>
                    <p class="isp-form-label mb-2">Old values</p>
                    <pre class="max-h-48 overflow-auto rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-700 dark:bg-slate-950">{{ json_encode($activityLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
            @if(!empty($activityLog->new_values))
                <div>
                    <p class="isp-form-label mb-2">New values</p>
                    <pre class="max-h-48 overflow-auto rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-700 dark:bg-slate-950">{{ json_encode($activityLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        </div>
    @endif
    @if(!empty($activityLog->metadata))
        <div>
            <p class="isp-form-label mb-2">Metadata</p>
            <pre class="max-h-64 overflow-auto rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-700 dark:bg-slate-950">{{ json_encode($activityLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
</div>
@endsection
