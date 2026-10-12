@extends('layouts.admin')

@section('title', 'Custom report builder')

@section('header')
    <x-isp-page-header title="Custom report builder" module="Analytics" description="Choose an ISP dataset, fields, filters, grouping, and calculations." />
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600 dark:text-slate-300">Reports only include records owned by your ISP.</p>
        <a href="{{ route('isp.reports.index') }}" class="pb-btn">Back to reports</a>
    </div>

    <form method="GET" action="{{ route('isp.reports.builder') }}" class="pb-card space-y-5 p-5">
        <input type="hidden" name="run" value="1">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="isp-form-label" for="dataset">Data</label>
                <select class="isp-form-select" id="dataset" name="dataset" onchange="this.form.querySelector('[name=run]').remove(); this.form.querySelector('[name=group_by]').value=''; this.form.querySelector('[name=metric]').value='count'; if(this.form.querySelector('[name=measure]')) this.form.querySelector('[name=measure]').value=''; this.form.submit()">
                    @foreach($definitions as $key => $definition)
                        <option value="{{ $key }}" @selected($validated['dataset'] === $key)>{{ $definition['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="isp-form-label" for="search">Search</label>
                <input class="isp-form-input" id="search" name="search" value="{{ $validated['search'] ?? '' }}" maxlength="100" placeholder="Search available text fields">
            </div>
            <div>
                <label class="isp-form-label" for="date_from">From</label>
                <input class="isp-form-input" id="date_from" type="date" name="date_from" value="{{ $validated['date_from'] ?? '' }}">
            </div>
            <div>
                <label class="isp-form-label" for="date_to">To</label>
                <input class="isp-form-input" id="date_to" type="date" name="date_to" value="{{ $validated['date_to'] ?? '' }}">
            </div>
            <div>
                <label class="isp-form-label" for="status">Status</label>
                <select class="isp-form-select" id="status" name="status">
                    <option value="">Any status</option>
                    @foreach($dataset['status'] as $status)
                        <option value="{{ $status }}" @selected(($validated['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="isp-form-label" for="mode">Report type</label>
                <select class="isp-form-select" id="mode" name="mode">
                    <option value="rows" @selected(($validated['mode'] ?? 'rows') === 'rows')>Detailed rows</option>
                    <option value="aggregate" @selected(($validated['mode'] ?? '') === 'aggregate')>Summary / grouped</option>
                </select>
            </div>
            <div>
                <label class="isp-form-label" for="group_by">Group by</label>
                <select class="isp-form-select" id="group_by" name="group_by">
                    <option value="">Overall total</option>
                    @foreach($dataset['columns'] as $key => $field)
                        @if(!empty($field['group']))
                            <option value="{{ $key }}" @selected(($validated['group_by'] ?? '') === $key)>{{ $field['label'] }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label class="isp-form-label" for="metric">Calculation</label>
                <select class="isp-form-select" id="metric" name="metric">
                    <option value="count" @selected(($validated['metric'] ?? 'count') === 'count')>Count records</option>
                    @if($dataset['measures'])
                        <option value="sum" @selected(($validated['metric'] ?? '') === 'sum')>Sum</option>
                        <option value="average" @selected(($validated['metric'] ?? '') === 'average')>Average</option>
                    @endif
                </select>
            </div>
            @if($dataset['measures'])
                <div>
                    <label class="isp-form-label" for="measure">Numeric field</label>
                    <select class="isp-form-select" id="measure" name="measure">
                        @foreach($dataset['measures'] as $key => $sql)
                            <option value="{{ $key }}" @selected(($validated['measure'] ?? array_key_first($dataset['measures'])) === $key)>{{ $dataset['columns'][$key]['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <fieldset class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
            <legend class="px-2 text-sm font-semibold">Columns for detailed rows</legend>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($dataset['columns'] as $key => $field)
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, $validated['columns'] ?? [], true))>
                        <span>{{ $field['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        @error('date_to')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
        @error('group_by')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
        @error('measure')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('isp.reports.builder') }}" class="pb-btn">Reset</a>
            <button type="submit" class="pb-btn pb-btn-primary">Generate report</button>
        </div>
    </form>

    @if($records)
        <section class="pb-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-700">
                <div>
                    <h2 class="font-semibold text-slate-900 dark:text-white">{{ $dataset['label'] }} report</h2>
                    <p class="text-sm text-slate-500">{{ number_format($records->total()) }} matching records</p>
                </div>
                @if($selectedColumns)
                    <a class="pb-btn pb-btn-primary" href="{{ route('isp.reports.builder.export', request()->except('page', 'run')) }}">Download CSV</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        @foreach($selectedColumns as $field)<th class="px-4 py-3">{{ $field['label'] }}</th>@endforeach
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($records as $record)
                            <tr>@foreach($selectedColumns as $key => $field)<td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $record->{$key} ?? '—' }}</td>@endforeach</tr>
                        @empty
                            <tr><td class="px-4 py-8 text-center text-sm text-slate-500" colspan="{{ max(1, count($selectedColumns)) }}">No records match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $records->links() }}</div>
        </section>
    @elseif(is_array($results))
        <section class="pb-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-700">
                <div>
                    <h2 class="font-semibold text-slate-900 dark:text-white">{{ $dataset['label'] }} summary</h2>
                    <p class="text-sm text-slate-500">{{ $validated['metric_label'] ?? 'Record count' }} · {{ count($results) }} group(s)</p>
                </div>
                <a class="pb-btn pb-btn-primary" href="{{ route('isp.reports.builder.export', request()->except('page', 'run')) }}">Download CSV</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="px-4 py-3">{{ $validated['group_by'] ? $dataset['columns'][$validated['group_by']]['label'] : 'Total' }}</th><th class="px-4 py-3 text-right">{{ $validated['metric_label'] ?? 'Records' }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($results as [$label, $value])
                            <tr><td class="px-4 py-3 text-sm">{{ $label }}</td><td class="px-4 py-3 text-right text-sm font-semibold">{{ number_format($value, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="px-4 py-8 text-center text-sm text-slate-500">No records match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
