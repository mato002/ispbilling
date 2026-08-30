@extends('layouts.admin')

@section('title', 'Migration Progress')

@section('header')
    <x-isp-page-header
        title="Migration Batch #{{ $batch->id }}"
        module="Network Operations"
        description="{{ $batch->oldRouter?->name }} → {{ $batch->newRouter?->name }}"
    />
@endsection

@section('content')
<div class="space-y-6" x-data="migrationProgress()" x-init="init()">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-[var(--pb-border)] bg-white p-4 shadow-sm dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total</p>
            <p class="mt-1 text-2xl font-bold" x-text="stats.total_customers">{{ $batch->total_customers }}</p>
        </div>
        <div class="rounded-xl border border-[var(--pb-border)] bg-white p-4 shadow-sm dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Processed</p>
            <p class="mt-1 text-2xl font-bold" x-text="stats.processed_customers">{{ $batch->processed_customers }}</p>
        </div>
        <div class="rounded-xl border border-[var(--pb-border)] bg-white p-4 shadow-sm dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Success</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600" x-text="stats.success_count">{{ $batch->success_count }}</p>
        </div>
        <div class="rounded-xl border border-[var(--pb-border)] bg-white p-4 shadow-sm dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Failed</p>
            <p class="mt-1 text-2xl font-bold text-rose-600" x-text="stats.failed_count">{{ $batch->failed_count }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-[var(--pb-border)] bg-white p-5 shadow-sm dark:bg-slate-900">
        <div class="mb-2 flex items-center justify-between text-sm">
            <span class="font-medium">Progress</span>
            <span class="text-slate-500" x-text="stats.progress_percent + '%'">{{ $batch->progressPercent() }}%</span>
        </div>
        <div class="h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-cyan-500 transition-all duration-500"
                 :style="`width: ${stats.progress_percent}%`"
                 style="width: {{ $batch->progressPercent() }}%"></div>
        </div>
        <p class="mt-2 text-xs text-slate-500">
            Status: <span class="font-semibold uppercase" x-text="stats.status">{{ $batch->status }}</span>
            · Mode:
            <span class="font-medium">
                {{ ($batch->metadata['eligibility'] ?? 'active_only') === 'all_on_router' ? 'All on source router' : 'Active subscriptions only' }}
            </span>
        </p>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('isp.router-migration.index') }}" class="pb-btn pb-btn-secondary text-xs">Back to history</a>
        @if($batch->canRollback())
            <form method="POST" action="{{ route('isp.router-migration.rollback', $batch) }}" onsubmit="return confirm('Rollback all successfully migrated customers to the source router?');">
                @csrf
                <button type="submit" class="pb-btn border-amber-300 bg-amber-50 text-amber-900 text-xs">Rollback batch</button>
            </form>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-[var(--pb-border)] bg-white shadow-sm dark:bg-slate-900">
        <div class="border-b border-[var(--pb-border)] px-4 py-3">
            <h3 class="text-sm font-semibold">Live migration log</h3>
        </div>
        <div class="max-h-96 overflow-y-auto p-4 font-mono text-xs space-y-2" id="log-feed">
            @foreach($recentLogs as $log)
                <div class="@if($log->status === 'failed') text-rose-600 @elseif($log->status === 'success') text-emerald-700 @else text-slate-600 @endif">
                    [{{ $log->created_at->format('H:i:s') }}] {{ $log->customer?->full_name }} · {{ $log->action }} · {{ $log->status }}
                    @if($log->error_message) — {{ $log->error_message }} @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
function migrationProgress() {
    return {
        stats: @json([
            'status' => $batch->status,
            'total_customers' => $batch->total_customers,
            'processed_customers' => $batch->processed_customers,
            'success_count' => $batch->success_count,
            'failed_count' => $batch->failed_count,
            'progress_percent' => $batch->progressPercent(),
            'is_terminal' => $batch->isTerminal(),
        ]),
        pollTimer: null,
        init() {
            if (!this.stats.is_terminal) {
                this.poll();
                this.pollTimer = setInterval(() => this.poll(), 4000);
            }
        },
        async poll() {
            try {
                const res = await fetch('{{ route('isp.router-migration.status', $batch) }}', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.stats = data.batch;
                const feed = document.getElementById('log-feed');
                if (feed && data.logs) {
                    feed.innerHTML = data.logs.map(l => {
                        const cls = l.status === 'failed' ? 'text-rose-600' : (l.status === 'success' ? 'text-emerald-700' : 'text-slate-600');
                        const err = l.error ? ` — ${l.error}` : '';
                        const t = l.at ? new Date(l.at).toLocaleTimeString() : '';
                        return `<div class="${cls}">[${t}] ${l.customer || '—'} · ${l.action} · ${l.status}${err}</div>`;
                    }).join('');
                }
                if (this.stats.is_terminal && this.pollTimer) {
                    clearInterval(this.pollTimer);
                }
            } catch (e) {}
        },
    };
}
</script>
@endsection
