@extends('layouts.admin')

@section('title', 'Transaction logs')

@section('header')
    <x-isp-page-header
        title="Transaction logs"
        module="Billing"
        description="Gateway request and webhook audit trail for payment flows."
        primaryLabel="Payments"
        :primaryHref="route('isp.payments.index')"
    />
@endsection

@section('content')
@php
    $logDetailBase = url('/isp/payments/transaction-logs');
@endphp
<div class="space-y-6">
    <x-filter-bar :action="route('isp.payments.transaction-logs')">
        <div class="min-w-[200px] flex-1">
            <label class="isp-form-label">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="isp-form-input" placeholder="Reference, transaction ID…">
        </div>
        <div class="min-w-[130px]">
            <label class="isp-form-label">Status</label>
            <select name="status" class="isp-form-select">
                <option value="">All</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="isp-form-label">Type</label>
            <select name="transaction_type" class="isp-form-select">
                <option value="">All</option>
                <option value="initiated" @selected(request('transaction_type') === 'initiated')>Initiated</option>
                <option value="webhook" @selected(request('transaction_type') === 'webhook')>Webhook</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.payments.transaction-logs') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($logs->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state title="No transaction logs" message="No rows match the current filters." />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Date / time</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>IP</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-sm">{{ $log->created_at->format('M j, Y H:i:s') }}</td>
                        <td><span class="pb-pill pb-pill-info text-xs">{{ ucfirst($log->transaction_type) }}</span></td>
                        <td class="font-mono text-sm">{{ $log->payment_reference ?? '—' }}</td>
                        <td class="font-mono text-sm">{{ $log->transaction_id ?? '—' }}</td>
                        <td class="font-mono font-semibold whitespace-nowrap">
                            @if($log->amount)
                                KES {{ number_format($log->amount, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($log->status === 'completed')
                                <x-status-badge status="paid" />
                            @elseif($log->status === 'pending')
                                <x-status-badge status="pending" />
                            @else
                                <span class="pb-pill pb-pill-bad">Failed</span>
                            @endif
                        </td>
                        <td class="font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                        <td class="text-right whitespace-nowrap">
                            <button type="button" onclick="showLogDetails({{ $log->id }})" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Details</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $logs->withQueryString()->links() }}</div>
    @endif
</div>

<div id="logModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-full items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" onclick="if(event.target===this) closeLogModal()">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Log details</h3>
                <button type="button" onclick="closeLogModal()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="logDetails" class="mt-4 space-y-3 text-sm text-slate-700 dark:text-slate-300"></div>
        </div>
    </div>
</div>

<script>
    const logDetailBase = @json($logDetailBase);

    function showLogDetails(logId) {
        fetch(logDetailBase + '/' + logId, { headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(data => {
                document.getElementById('logDetails').innerHTML = `
                    <div><span class="font-semibold text-slate-500">Type</span> · ${escapeHtml(data.transaction_type)}</div>
                    <div><span class="font-semibold text-slate-500">Status</span> · ${escapeHtml(data.status)}</div>
                    <div><span class="font-semibold text-slate-500">Amount</span> · ${data.amount ? 'KES ' + parseFloat(data.amount).toFixed(2) : 'N/A'}</div>
                    <div><span class="font-semibold text-slate-500">IP</span> · ${escapeHtml(data.ip_address || 'N/A')}</div>
                    ${data.error_message ? '<div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200"><strong>Error</strong><br>' + escapeHtml(data.error_message) + '</div>' : ''}
                    <div>
                        <p class="mb-1 font-semibold text-slate-500">Request</p>
                        <pre class="max-h-40 overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs dark:border-slate-700 dark:bg-slate-950">${escapeHtml(JSON.stringify(data.request_data || {}, null, 2))}</pre>
                    </div>
                    ${data.response_data ? `<div><p class="mb-1 font-semibold text-slate-500">Response</p><pre class="max-h-40 overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs dark:border-slate-700 dark:bg-slate-950">${escapeHtml(JSON.stringify(data.response_data, null, 2))}</pre></div>` : ''}
                `;
                document.getElementById('logModal').classList.remove('hidden');
            })
            .catch(() => alert('Could not load log details.'));
    }

    function closeLogModal() {
        document.getElementById('logModal').classList.add('hidden');
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
</script>
@endsection
