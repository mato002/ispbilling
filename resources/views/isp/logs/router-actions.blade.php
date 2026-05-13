@extends('layouts.admin')

@section('title', 'Router action logs')

@section('header')
    <x-isp-page-header
        title="Router action logs"
        module="Audit"
        description="MikroTik API actions, sync jobs, and subscriber operations per router."
        primaryLabel="Dashboard"
        :primaryHref="route('isp.dashboard')"
    />
@endsection

@section('content')
<div class="space-y-6">
    <x-filter-bar :action="route('isp.logs.router-actions.index')">
        <div class="min-w-[200px] flex-1">
            <label class="isp-form-label">Router</label>
            <select name="router_id" class="isp-form-select">
                <option value="">All routers</option>
                @foreach($routers as $r)
                    <option value="{{ $r->id }}" @selected((string) request('router_id') === (string) $r->id)>{{ $r->name }} ({{ $r->ip_address }})</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="isp-form-label">Action</label>
            <input type="text" name="action" value="{{ request('action') }}" class="isp-form-input" placeholder="e.g. sync…">
        </div>
        <div class="min-w-[120px]">
            <label class="isp-form-label">Status</label>
            <select name="status" class="isp-form-select">
                <option value="">All</option>
                <option value="success" @selected(request('status') === 'success')>Success</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
            </select>
        </div>
        <div class="min-w-[160px] flex-1">
            <label class="isp-form-label">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="isp-form-input" placeholder="Description, error…">
        </div>
        <div class="min-w-[120px]">
            <label class="isp-form-label">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="isp-form-input">
        </div>
        <div class="min-w-[120px]">
            <label class="isp-form-label">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="isp-form-input">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.logs.router-actions.index') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($logs->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state title="No router logs" message="No entries match the current filters." />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Router</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Description</th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-sm">{{ $log->created_at->format('M j, Y H:i') }}</td>
                        <td>{{ $log->router?->name ?? '—' }}</td>
                        <td class="font-mono text-sm">{{ $log->action }}</td>
                        <td>
                            @if(($log->status ?? '') === 'success')
                                <span class="pb-pill pb-pill-ok">Success</span>
                            @else
                                <span class="pb-pill pb-pill-bad">{{ $log->status ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="max-w-md truncate text-slate-600 dark:text-slate-300">{{ Str::limit($log->description ?? $log->error_message ?? '', 80) }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('isp.logs.router-actions.show', $log) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $logs->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
