@extends('layouts.admin')

@section('title', 'Payment methods · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    <x-isp-settings-workspace
        meta-title="Payment methods catalog"
>
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('isp.payment-settings.create') }}" class="pb-btn pb-btn-primary">Add payment method</a>
        </div>

        <div class="pb-card isp-settings-panel overflow-hidden p-4 sm:p-6">
            <div class="mb-4 border-b border-dashed border-[var(--pb-border)] pb-4">
                <h3 class="isp-settings-section-title">Configured methods</h3>
</div>

            @if ($groupedMethods->isEmpty())
                <div class="isp-empty-state py-10">
                    <div class="icon-wrap text-slate-500">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">No payment methods yet</p>
<a href="{{ route('isp.payment-settings.create') }}" class="pb-btn pb-btn-primary mt-4">Add your first method</a>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($groupedMethods as $provider => $methods)
                        @foreach ($methods as $method)
                            <div class="rounded-xl border border-[var(--pb-border)] bg-slate-50/50 p-4 dark:bg-slate-900/30">
                                <div class="mb-3 flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ ucfirst($provider) }}</p>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Gateway profile</p>
                                    </div>
                                    @if ($method->active)
                                        <span class="pb-pill pb-pill-ok text-[10px]">Active</span>
                                    @else
                                        <span class="pb-pill pb-pill-warn text-[10px]">Inactive</span>
                                    @endif
                                </div>
                                <div class="isp-settings-hint mb-4 space-y-1 text-xs">
                                    <p>Environment: {{ $method->credentials['environment'] ?? '—' }}</p>
                                    @if ($method->callback_url)
                                        <p>Callback: <span class="font-mono">{{ \Illuminate\Support\Str::limit($method->callback_url, 42) }}</span></p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-dashed border-[var(--pb-border)] pt-3">
                                    <x-table-actions>
                                        <x-table-actions.link :href="route('isp.payment-settings.edit', $method)" label="Edit" />
                                        <x-table-actions.form
                                            :action="route('isp.payment-settings.destroy', $method)"
                                            method="DELETE"
                                            label="Delete"
                                            onsubmit="return confirm('Delete this payment method?');"
                                        />
                                        <x-table-actions.form
                                            :action="route('isp.payment-settings.toggle', $method)"
                                            :label="$method->active ? 'Deactivate' : 'Activate'"
                                            :danger="false"
                                        />
                                    </x-table-actions>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>

        <div class="pb-card isp-settings-panel p-4 sm:p-6">
            <h3 class="isp-settings-section-title">Integration reference</h3>
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-[var(--pb-border)] p-4 text-sm text-slate-600 dark:text-slate-300">
                    <p class="font-semibold text-slate-900 dark:text-white">M-Pesa</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                        <li>Consumer key &amp; secret</li>
                        <li>Passkey &amp; shortcode</li>
                        <li>Sandbox vs production</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-[var(--pb-border)] p-4 text-sm text-slate-600 dark:text-slate-300">
                    <p class="font-semibold text-slate-900 dark:text-white">Cards (Stripe / Flutterwave)</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                        <li>Publishable &amp; secret keys</li>
                        <li>Webhooks for settlement</li>
                    </ul>
                </div>
            </div>
        </div>
    </x-isp-settings-workspace>
@endsection
