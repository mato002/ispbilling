@php
    $totals = $usageBreakdown['totals'] ?? ['upload' => 0, 'download' => 0, 'total' => 0, 'last_active' => null];
    $currentSession = $usageBreakdown['current_session'] ?? null;
@endphp

<x-form-section title="Data usage" description="Read-only monitoring for the last 30 days. Does not affect billing.">
    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <dt class="isp-form-label mb-1">Upload</dt>
            <dd class="text-sm font-medium text-slate-900 dark:text-white">{{ \App\Models\DataUsage::formatBytes($totals['upload']) }}</dd>
        </div>
        <div>
            <dt class="isp-form-label mb-1">Download</dt>
            <dd class="text-sm font-medium text-slate-900 dark:text-white">{{ \App\Models\DataUsage::formatBytes($totals['download']) }}</dd>
        </div>
        <div>
            <dt class="isp-form-label mb-1">Total</dt>
            <dd class="text-sm font-medium text-slate-900 dark:text-white">{{ \App\Models\DataUsage::formatBytes($totals['total']) }}</dd>
        </div>
        <div>
            <dt class="isp-form-label mb-1">Last active</dt>
            <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $totals['last_active']?->format('M j, Y H:i') ?? '—' }}</dd>
        </div>
    </dl>

    @if ($currentSession)
        <div class="mt-4 rounded-lg border border-indigo-200 bg-indigo-50/50 p-3 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Current session</p>
            <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                {{ $currentSession->formatted_total_bytes }} total
                @if ($currentSession->ip_address)
                    · {{ $currentSession->ip_address }}
                @endif
            </p>
        </div>
    @endif

    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('isp.data-usage.customer', $customer) }}" class="pb-btn text-xs">Full usage report</a>
        <a href="{{ route('isp.sessions.customer-history', $customer) }}" class="pb-btn text-xs">Session history</a>
    </div>
</x-form-section>
