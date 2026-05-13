@extends('layouts.admin')

@section('title', 'Router action log')

@section('header')
    <x-isp-page-header
        title="Router action detail"
        module="Audit"
        description="Full payload and outcome for this MikroTik operation."
        primaryLabel="Back to list"
        :primaryHref="route('isp.logs.router-actions.index')"
    />
@endsection

@section('content')
<div class="pb-card p-4 sm:p-6 space-y-5 text-sm">
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <dt class="isp-form-label mb-1">Date</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $routerActionLog->created_at->format('M j, Y H:i:s') }}</dd>
        </div>
        <div>
            <dt class="isp-form-label mb-1">Router</dt>
            <dd class="text-slate-800 dark:text-slate-100">{{ $routerActionLog->router?->name ?? '—' }} <span class="font-mono text-slate-500">({{ $routerActionLog->router?->ip_address ?? '—' }})</span></dd>
        </div>
        <div>
            <dt class="isp-form-label mb-1">Action</dt>
            <dd class="font-mono text-slate-900 dark:text-white">{{ $routerActionLog->action }}</dd>
        </div>
        <div>
            <dt class="isp-form-label mb-1">Status</dt>
            <dd>
                @if(($routerActionLog->status ?? '') === 'success')
                    <span class="pb-pill pb-pill-ok">Success</span>
                @else
                    <span class="pb-pill pb-pill-bad">{{ $routerActionLog->status ?? '—' }}</span>
                @endif
            </dd>
        </div>
    </dl>
    <div>
        <p class="isp-form-label mb-1">Description</p>
        <p class="text-slate-700 dark:text-slate-300">{{ $routerActionLog->description ?? '—' }}</p>
    </div>
    @if($routerActionLog->customer)
        <div>
            <p class="isp-form-label mb-1">Customer</p>
            <p class="text-slate-800 dark:text-slate-100">{{ $routerActionLog->customer->full_name }} <span class="font-mono text-slate-500">({{ $routerActionLog->customer->username ?? '—' }})</span></p>
        </div>
    @endif
    @if($routerActionLog->error_message)
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100">
            <p class="isp-form-label mb-1 text-rose-800 dark:text-rose-200">Error</p>
            <p>{{ $routerActionLog->error_message }}</p>
        </div>
    @endif
    @if(!empty($routerActionLog->request_data))
        <div>
            <p class="isp-form-label mb-2">Request data</p>
            <pre class="max-h-48 overflow-auto rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-700 dark:bg-slate-950">{{ json_encode($routerActionLog->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
    @if(!empty($routerActionLog->response_data))
        <div>
            <p class="isp-form-label mb-2">Response data</p>
            <pre class="max-h-48 overflow-auto rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-700 dark:bg-slate-950">{{ json_encode($routerActionLog->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
</div>
@endsection
