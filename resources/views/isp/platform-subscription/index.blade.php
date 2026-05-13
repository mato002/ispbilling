@extends('layouts.admin')

@section('title', 'PrimeBilling subscription')

@section('header')
    <x-isp-page-header
        title="PrimeBilling subscription"
        module="Platform billing"
        description="Your organisation’s subscription to the PrimeBilling platform (separate from subscriber billing)."
    />
@endsection

@section('content')
<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-100">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-500/30 dark:bg-rose-950/40 dark:text-rose-100">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Current plan</h2>
            @if ($isp->saasPlan)
                <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $isp->saasPlan->name }}</p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $isp->saasPlan->description }}</p>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Monthly (platform)</dt><dd class="font-medium">KES {{ number_format($isp->saasPlan->monthly_price, 2) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Status</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $isp->saas_subscription_status) }}</dd></div>
                    @if ($isp->saas_trial_ends_at)
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Trial ends</dt><dd class="font-medium">{{ $isp->saas_trial_ends_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</dd></div>
                    @endif
                    @if ($isp->saas_next_billing_at)
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Next billing</dt><dd class="font-medium">{{ $isp->saas_next_billing_at->timezone(config('app.timezone'))->format('M j, Y') }}</dd></div>
                    @endif
                    @if ($isp->saas_grace_ends_at)
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Payment grace until</dt><dd class="font-medium">{{ $isp->saas_grace_ends_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</dd></div>
                    @endif
                </dl>
            @else
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">No SaaS plan is linked to this tenant yet. Complete onboarding or ask PrimeBilling support.</p>
            @endif
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">M-Pesa</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Automated STK uses your billing number on file: <span class="font-mono font-semibold">{{ $isp->saas_billing_phone ?? '—' }}</span></p>
            <p class="mt-3 text-xs text-slate-500">Platform collections use Daraja credentials configured in the server environment (<code>.env</code> / <code>config/services.php</code>), not your subscriber M-Pesa till.</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Platform invoices</h2>
            <p class="text-sm text-slate-500">Invoices for PrimeBilling access. Use Pay to trigger an STK push for a pending invoice.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-6 py-3">Invoice</th>
                        <th class="px-6 py-3">Period</th>
                        <th class="px-6 py-3">Amount</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($isp->platformSaasInvoices as $inv)
                        <tr>
                            <td class="px-6 py-3 font-mono text-xs">{{ $inv->invoice_number }}</td>
                            <td class="px-6 py-3 text-slate-600 dark:text-slate-300">
                                {{ $inv->period_start?->format('M j, Y') }} — {{ $inv->period_end?->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-3 font-medium">KES {{ number_format($inv->totalAmount(), 2) }}</td>
                            <td class="px-6 py-3 capitalize">{{ $inv->status }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('isp.platform-subscription.invoice', $inv) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400">View</a>
                                @if ($inv->status === \App\Models\PlatformSaasInvoice::STATUS_PENDING)
                                    <form class="inline ml-3" method="POST" action="{{ route('isp.platform-subscription.pay', $inv) }}">@csrf
                                        <button type="submit" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Pay with M-Pesa</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">No platform invoices yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
