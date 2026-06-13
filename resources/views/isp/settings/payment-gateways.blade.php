@extends('layouts.admin')

@section('title', 'Legacy gateways · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $pg = $isp->settings['payment_gateways'] ?? [];
        $mp = array_merge(
            $pg['daraja'] ?? [],
            $pg['mpesa'] ?? []
        );
        $mp = \App\Support\MpesaGatewayConfig::normalizeCredentials($mp);
        $mp['enabled'] = ($pg['mpesa']['enabled'] ?? $pg['daraja']['enabled'] ?? false) === true;
        $sel = $pg['selcom'] ?? [];
        $man = $pg['manual'] ?? [];
    @endphp

    <x-isp-settings-workspace
        meta-title="Legacy payment gateways"
        meta-description="Older JSON-backed gateway flags kept for backward compatibility. Prefer the Daraja profile and payment methods catalog for new setups."
    >
        <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <span class="font-semibold">Heads up.</span>
            <span class="text-amber-900/85 dark:text-amber-100/85"> This path stores credentials in ISP JSON settings. Use <a href="{{ route('isp.settings.payment') }}" class="font-semibold text-indigo-700 underline decoration-indigo-400/60 underline-offset-2 dark:text-indigo-300">Daraja profile</a> for encrypted storage.</span>
        </div>

        <form
            method="POST"
            action="{{ route('isp.settings.payment-gateways.update') }}"
            class="space-y-4"
            x-data="{ dirty: false, saving: false }"
            @input="dirty = true"
            @change="dirty = true"
            @submit="saving = true"
        >
            @csrf

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">M-Pesa (legacy JSON)</h3>
                    <p class="isp-settings-hint mt-1">Safaricom keys stored directly on the ISP record — migrate when possible.</p>
                </div>
                <div class="space-y-4">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            name="mpesa_enabled"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                            {{ old('mpesa_enabled', ! empty($mp['enabled'])) ? 'checked' : '' }}
                        />
                        <span class="text-sm font-medium text-slate-800 dark:text-slate-100">Enable M-Pesa</span>
                    </label>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="isp-form-label" for="mpesa_consumer_key">Consumer key</label>
                            <input id="mpesa_consumer_key" class="isp-form-input" type="text" name="mpesa_consumer_key" value="{{ old('mpesa_consumer_key', $mp['consumer_key'] ?? '') }}" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="isp-form-label" for="mpesa_consumer_secret">Consumer secret</label>
                            <input id="mpesa_consumer_secret" class="isp-form-input" type="password" name="mpesa_consumer_secret" value="{{ old('mpesa_consumer_secret', $mp['consumer_secret'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="mpesa_shortcode">Shortcode</label>
                            <input id="mpesa_shortcode" class="isp-form-input" type="text" name="mpesa_shortcode" value="{{ old('mpesa_shortcode', $mp['shortcode'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="mpesa_passkey">Passkey</label>
                            <input id="mpesa_passkey" class="isp-form-input" type="password" name="mpesa_passkey" value="{{ old('mpesa_passkey', $mp['passkey'] ?? '') }}" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Selcom</h3>
                    <p class="isp-settings-hint mt-1">Tanzania collections through Selcom when enabled.</p>
                </div>
                <div class="space-y-4">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            name="selcom_enabled"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                            {{ old('selcom_enabled', ! empty($sel['enabled'])) ? 'checked' : '' }}
                        />
                        <span class="text-sm font-medium text-slate-800 dark:text-slate-100">Enable Selcom</span>
                    </label>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="isp-form-label" for="selcom_api_key">API key</label>
                            <input id="selcom_api_key" class="isp-form-input" type="text" name="selcom_api_key" value="{{ old('selcom_api_key', $sel['api_key'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="selcom_api_secret">API secret</label>
                            <input id="selcom_api_secret" class="isp-form-input" type="password" name="selcom_api_secret" value="{{ old('selcom_api_secret', $sel['api_secret'] ?? '') }}" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="isp-form-label" for="selcom_vendor_id">Vendor ID</label>
                            <input id="selcom_vendor_id" class="isp-form-input" type="text" name="selcom_vendor_id" value="{{ old('selcom_vendor_id', $sel['vendor_id'] ?? '') }}" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Manual payment</h3>
                    <p class="isp-settings-hint mt-1">Allow staff to record bank or cash payments without an automated gateway.</p>
                </div>
                <label class="flex cursor-pointer items-center gap-3">
                    <input
                        type="checkbox"
                        name="manual_enabled"
                        value="1"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                        {{ old('manual_enabled', ! empty($man['enabled'] ?? true)) ? 'checked' : '' }}
                    />
                    <span class="text-sm font-medium text-slate-800 dark:text-slate-100">Enable manual payment</span>
                </label>
            </div>

            <div class="isp-settings-sticky-save">
                <p class="mr-auto text-xs text-slate-500 dark:text-slate-400" x-show="dirty" x-cloak>
                    <span class="font-semibold text-amber-700 dark:text-amber-300">Unsaved changes</span>
                </p>
                <button type="button" class="pb-btn" @click="window.location.reload()">Cancel</button>
                <button type="submit" class="pb-btn pb-btn-primary" :disabled="!dirty || saving" :class="(!dirty || saving) ? 'opacity-50 pointer-events-none' : ''">
                    <span x-show="!saving">Save legacy gateways</span>
                    <span x-show="saving" x-cloak>Saving…</span>
                </button>
            </div>
        </form>
    </x-isp-settings-workspace>
@endsection
