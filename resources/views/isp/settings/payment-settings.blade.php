@extends('layouts.admin')

@section('title', 'Payment methods · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    <x-isp-settings-workspace
        meta-title="Payment methods catalog"
        meta-description="Provision multiple processors alongside Daraja. Each method stores encrypted credentials per ISP."
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
                <p class="isp-settings-hint mt-1">Toggle activation without deleting credentials — ideal for staged rollouts.</p>
            </div>

            @if ($groupedMethods->isEmpty())
                <div class="isp-empty-state py-10">
                    <div class="icon-wrap text-slate-500">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">No payment methods yet</p>
                    <p class="isp-settings-hint mt-1 px-4">Provision M-Pesa, cards, or wallets to start automated collections.</p>
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
                                    <div class="flex gap-2 text-sm">
                                        <a href="{{ route('isp.payment-settings.edit', $method) }}" class="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-200">Edit</a>
                                        <form action="{{ route('isp.payment-settings.destroy', $method) }}" method="POST" class="inline" onsubmit="return confirm('Delete this payment method?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-semibold text-red-600 hover:text-red-700 dark:text-red-400">Delete</button>
                                        </form>
                                    </div>
                                    <form action="{{ route('isp.payment-settings.toggle', $method) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="pb-btn !py-1.5 !text-xs">
                                            {{ $method->active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>

        <div class="pb-card isp-settings-panel p-4 sm:p-6">
            <h3 class="isp-settings-section-title">Integration reference</h3>
            <p class="isp-settings-hint mt-1 mb-4">Credential checklist per processor — consult vendor docs for exact field mapping.</p>
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
