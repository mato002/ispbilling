@extends('layouts.admin')

@section('title', 'Payment gateways · ISP System Settings')

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
        $ps = $pg['paystack'] ?? [];
        $paystackMode = old('paystack_mode', $ps['mode'] ?? 'test');
        $paystackWebhookUrl = url('/api/payments/paystack/webhook/'.$isp->id);
        $sel = $pg['selcom'] ?? [];
        $man = $pg['manual'] ?? [];
    @endphp

    <x-isp-settings-workspace
        meta-title="Third-party payment gateways"
>
        <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <span class="font-semibold">Security notice.</span>
            <span class="text-amber-900/85 dark:text-amber-100/85"> Prefer encrypted <a href="{{ route('isp.settings.payment') }}" class="font-semibold text-indigo-700 underline decoration-indigo-400/60 underline-offset-2 dark:text-indigo-300">Daraja profile</a> and <a href="{{ route('isp.payment-settings.index') }}" class="font-semibold text-indigo-700 underline decoration-indigo-400/60 underline-offset-2 dark:text-indigo-300">payment methods</a> for new setups. This legacy JSON store remains for backward compatibility.</span>
        </div>

        <form
            method="POST"
            action="{{ route('isp.settings.payment-gateways.update') }}"
            class="pb-card isp-settings-panel p-4 sm:p-6"
            x-data="{ dirty: false, saving: false }"
            @input="dirty = true"
            @change="dirty = true"
            @submit="saving = true"
        >
            @csrf

            <div class="mb-6 border-b border-dashed border-[var(--pb-border)] pb-5">
                <h3 class="isp-settings-section-title">Payment gateway configuration</h3>
</div>

            {{-- Paystack --}}
            <section>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Paystack</h4>
<label class="mt-4 flex cursor-pointer items-center gap-3">
                    <input
                        type="checkbox"
                        name="paystack_enabled"
                        value="1"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                        {{ old('paystack_enabled', ! empty($ps['enabled'])) ? 'checked' : '' }}
                    />
                    <span class="text-sm font-medium text-slate-800 dark:text-slate-100">Enable Paystack collections</span>
                </label>

                <div class="mt-4 max-w-md">
                    <label class="isp-form-label" for="paystack_mode">Choose mode</label>
                    <select
                        id="paystack_mode"
                        name="paystack_mode"
                        class="isp-form-select w-full rounded-xl border-slate-200 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/25 dark:border-slate-700"
                    >
                        <option value="test" {{ $paystackMode === 'test' ? 'selected' : '' }}>Test mode (sandbox keys)</option>
                        <option value="live" {{ $paystackMode === 'live' ? 'selected' : '' }}>Live mode (production keys)</option>
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Use test keys while validating checkout flows; switch to live only after Paystack activation.</p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="paystack_public_key">Paystack Public Key</label>
                        <div class="relative">
                            <input
                                id="paystack_public_key"
                                class="isp-form-input pr-10 font-mono text-sm"
                                type="password"
                                name="paystack_public_key"
                                autocomplete="off"
                                value="{{ old('paystack_public_key', $ps['public_key'] ?? '') }}"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                aria-label="Show Paystack public key"
                                onclick="pbToggleGatewaySecret('paystack_public_key', this)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Public keys are safe for checkout widgets but should still not be pasted into public tickets.</p>
                    </div>
                    <div>
                        <label class="isp-form-label" for="paystack_secret_key">Paystack Secret Key</label>
                        <div class="relative">
                            <input
                                id="paystack_secret_key"
                                class="isp-form-input pr-10 font-mono text-sm"
                                type="password"
                                name="paystack_secret_key"
                                autocomplete="off"
                                value="{{ old('paystack_secret_key', $ps['secret_key'] ?? '') }}"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                aria-label="Show Paystack secret key"
                                onclick="pbToggleGatewaySecret('paystack_secret_key', this)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Keep secret keys strictly confidential. Never share live production hashes.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="paystack_webhook_url">Webhook URL</label>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                            <input
                                id="paystack_webhook_url"
                                type="text"
                                readonly
                                tabindex="-1"
                                value="{{ $paystackWebhookUrl }}"
                                class="isp-form-input min-w-0 flex-1 cursor-default border-slate-200 bg-slate-50 font-mono text-xs text-slate-500 shadow-sm select-all dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400"
                                aria-label="Paystack webhook URL (read only)"
                            />
                            <button
                                type="button"
                                class="pb-btn inline-flex shrink-0 items-center justify-center gap-2 self-start sm:self-auto"
                                data-copy-label="Copy link"
                                onclick="pbCopyFieldToClipboard('paystack_webhook_url', this)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                Copy link
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Paste this immutable endpoint into your Paystack merchant dashboard — it is computed for this workspace and cannot be edited here.</p>
                    </div>
                </div>
            </section>

            {{-- M-Pesa --}}
            <section class="border-t border-slate-100 pt-6 mt-6 dark:border-slate-800">
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">M-Pesa (Safaricom Daraja)</h4>
<label class="mt-4 flex cursor-pointer items-center gap-3">
                    <input
                        type="checkbox"
                        name="mpesa_enabled"
                        value="1"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                        {{ old('mpesa_enabled', ! empty($mp['enabled'])) ? 'checked' : '' }}
                    />
                    <span class="text-sm font-medium text-slate-800 dark:text-slate-100">Enable M-Pesa</span>
                </label>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="mpesa_consumer_key">Consumer key</label>
                        <div class="relative">
                            <input
                                id="mpesa_consumer_key"
                                class="isp-form-input pr-10 font-mono text-sm"
                                type="password"
                                name="mpesa_consumer_key"
                                autocomplete="off"
                                value="{{ old('mpesa_consumer_key', $mp['consumer_key'] ?? '') }}"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                aria-label="Show M-Pesa consumer key"
                                onclick="pbToggleGatewaySecret('mpesa_consumer_key', this)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Issued in the Safaricom Daraja developer portal for this shortcode.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="mpesa_consumer_secret">Consumer secret</label>
                        <div class="relative">
                            <input
                                id="mpesa_consumer_secret"
                                class="isp-form-input pr-10 font-mono text-sm"
                                type="password"
                                name="mpesa_consumer_secret"
                                autocomplete="off"
                                value="{{ old('mpesa_consumer_secret', $mp['consumer_secret'] ?? '') }}"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                aria-label="Show M-Pesa consumer secret"
                                onclick="pbToggleGatewaySecret('mpesa_consumer_secret', this)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Keep secret keys strictly confidential. Never share live production hashes.</p>
                    </div>
                    <div>
                        <label class="isp-form-label" for="mpesa_shortcode">Shortcode</label>
                        <input id="mpesa_shortcode" class="isp-form-input font-mono text-sm" type="text" name="mpesa_shortcode" value="{{ old('mpesa_shortcode', $mp['shortcode'] ?? '') }}" />
                        <p class="text-xs text-slate-400 mt-1">Paybill or till number registered with Safaricom.</p>
                    </div>
                    <div>
                        <label class="isp-form-label" for="mpesa_passkey">Passkey</label>
                        <div class="relative">
                            <input
                                id="mpesa_passkey"
                                class="isp-form-input pr-10 font-mono text-sm"
                                type="password"
                                name="mpesa_passkey"
                                autocomplete="off"
                                value="{{ old('mpesa_passkey', $mp['passkey'] ?? '') }}"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                aria-label="Show M-Pesa passkey"
                                onclick="pbToggleGatewaySecret('mpesa_passkey', this)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Required for STK Push; rotate immediately if exposed.</p>
                    </div>
                </div>
            </section>

            {{-- Selcom --}}
            <section class="border-t border-slate-100 pt-6 mt-6 dark:border-slate-800">
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Selcom</h4>
<label class="mt-4 flex cursor-pointer items-center gap-3">
                    <input
                        type="checkbox"
                        name="selcom_enabled"
                        value="1"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                        {{ old('selcom_enabled', ! empty($sel['enabled'])) ? 'checked' : '' }}
                    />
                    <span class="text-sm font-medium text-slate-800 dark:text-slate-100">Enable Selcom</span>
                </label>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="selcom_api_key">API key</label>
                        <div class="relative">
                            <input
                                id="selcom_api_key"
                                class="isp-form-input pr-10 font-mono text-sm"
                                type="password"
                                name="selcom_api_key"
                                autocomplete="off"
                                value="{{ old('selcom_api_key', $sel['api_key'] ?? '') }}"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                aria-label="Show Selcom API key"
                                onclick="pbToggleGatewaySecret('selcom_api_key', this)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">From the Selcom developer portal.</p>
                    </div>
                    <div>
                        <label class="isp-form-label" for="selcom_api_secret">API secret</label>
                        <div class="relative">
                            <input
                                id="selcom_api_secret"
                                class="isp-form-input pr-10 font-mono text-sm"
                                type="password"
                                name="selcom_api_secret"
                                autocomplete="off"
                                value="{{ old('selcom_api_secret', $sel['api_secret'] ?? '') }}"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                aria-label="Show Selcom API secret"
                                onclick="pbToggleGatewaySecret('selcom_api_secret', this)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Keep secret keys strictly confidential. Never share live production hashes.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="selcom_vendor_id">Vendor ID</label>
                        <input id="selcom_vendor_id" class="isp-form-input font-mono text-sm" type="text" name="selcom_vendor_id" value="{{ old('selcom_vendor_id', $sel['vendor_id'] ?? '') }}" />
                        <p class="text-xs text-slate-400 mt-1">Vendor identifier assigned by Selcom.</p>
                    </div>
                </div>
            </section>

            {{-- System flags --}}
            <section class="border-t border-slate-100 pt-6 mt-6 dark:border-slate-800">
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">System flags</h4>
<label class="mt-4 flex cursor-pointer items-center gap-3 rounded-lg border border-[var(--pb-border)] bg-slate-50/50 px-3 py-3 dark:bg-slate-900/30">
                    <input
                        type="checkbox"
                        name="manual_enabled"
                        value="1"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                        {{ old('manual_enabled', ! empty($man['enabled'] ?? true)) ? 'checked' : '' }}
                    />
                    <span>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Enable manual payment recording</span>
</span>
                </label>
            </section>

            <div class="isp-settings-sticky-save mt-6 border-t border-slate-100 pt-6 dark:border-slate-800">
                <p class="mr-auto text-xs text-slate-500 dark:text-slate-400" x-show="dirty" x-cloak>
                    <span class="font-semibold text-amber-700 dark:text-amber-300">Unsaved changes</span>
                </p>
                <button type="button" class="pb-btn" @click="window.location.reload()">Cancel</button>
                <button type="submit" class="pb-btn pb-btn-primary" :disabled="!dirty || saving" :class="(!dirty || saving) ? 'opacity-50 pointer-events-none' : ''">
                    <span x-show="!saving">Save gateway configuration</span>
                    <span x-show="saving" x-cloak>Saving…</span>
                </button>
            </div>
        </form>
    </x-isp-settings-workspace>
@endsection
