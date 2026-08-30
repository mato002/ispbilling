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
    <x-resource-index
        :filter-action="route('isp.logs.router-actions.index')"
        :paginator="$logs"
        :empty="$logs->isEmpty()"
        empty-title="No router logs"
        empty-message="No entries match the current filters."
    >
        <x-slot:filters>
            <div class="min-w-[200px] flex-1">
                <label class="isp-form-label">Router</label>
                <select name="router_id" class="isp-form-select">
                    <option value="">All routers</option>
                    @foreach ($routers as $r)
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
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Router</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Description</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <x-listing-table.row :href="route('isp.logs.router-actions.show', $log)">
                            <td class="whitespace-nowrap text-sm">{{ $log->created_at->format('M j, Y H:i') }}</td>
                            <td>{{ $log->router?->name ?? '—' }}</td>
                            <td class="font-mono text-sm">{{ $log->action }}</td>
                            <td>
                                @if (($log->status ?? '') === 'success')
                                    <span class="pb-pill pb-pill-ok">Success</span>
                                @else
                                    <span class="pb-pill pb-pill-bad">{{ $log->status ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="max-w-md truncate text-slate-600 dark:text-slate-300">{{ Str::limit($log->description ?? $log->error_message ?? '', 80) }}</td>
                            <td class="text-right whitespace-nowrap">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('isp.logs.router-actions.show', $log)" label="View" />
                                </x-table-actions>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
