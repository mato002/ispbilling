@extends('layouts.admin')

@section('title', 'Router Migration History')

@section('header')
    <x-isp-page-header
        title="Fleet Router Migration History"
        module="Network Operations"
        description="Completed and in-progress subscriber cutovers between MikroTik routers."
    />
@endsection

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex justify-end">
        <a href="{{ route('isp.router-migration.create') }}" class="pb-btn">New migration</a>
    </div>

    <div class="overflow-hidden rounded-xl border border-[var(--pb-border)] bg-white shadow-sm dark:bg-slate-900">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60">
                <tr>
                    <th class="px-4 py-3">Batch</th>
                    <th class="px-4 py-3">From → To</th>
                    <th class="px-4 py-3">Progress</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Started</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--pb-border)]">
                @forelse($batches as $batch)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3 font-medium">#{{ $batch->id }}</td>
                        <td class="px-4 py-3">
                            {{ $batch->oldRouter?->name ?? '—' }}
                            <span class="text-slate-400">→</span>
                            {{ $batch->newRouter?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $batch->success_count }}/{{ $batch->total_customers }} ok
                            @if($batch->failed_count)
                                · <span class="text-rose-600">{{ $batch->failed_count }} failed</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($batch->status) {
                                    'completed' => 'bg-emerald-500/15 text-emerald-700',
                                    'running', 'pending' => 'bg-sky-500/15 text-sky-700',
                                    'failed' => 'bg-rose-500/15 text-rose-700',
                                    'rolled_back' => 'bg-amber-500/15 text-amber-800',
                                    default => 'bg-slate-500/15 text-slate-600',
                                };
                            @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $batch->status)) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $batch->started_at?->format('M j, Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('isp.router-migration.show', $batch) }}" class="text-indigo-600 text-xs font-medium">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">No migrations yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $batches->links() }}
</div>
@endsection
