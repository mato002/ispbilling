@extends('layouts.admin')

@section('title', 'Payments & M-Pesa · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $S = $ispSettingsShell;
        $mpesa = $S['mpesa'] ?? null;
    @endphp

    <x-isp-settings-workspace
        meta-title="Payments & M-Pesa"
        meta-description="Daraja credentials, per-ISP callback URL, customer-facing instructions, and reconciliation behaviour."
    >
        @if ($mpesa && $mpesa['tone'] === 'bad')
            <div class="rounded-xl border border-red-200/90 bg-red-50/90 px-4 py-3 text-sm text-red-950 dark:border-red-500/35 dark:bg-red-500/10 dark:text-red-100">
                <span class="font-semibold">Daraja credentials missing.</span>
                <span class="text-red-800/90 dark:text-red-200/90"> Add API keys before enabling collections in production.</span>
            </div>
        @elseif ($mpesa && $mpesa['tone'] === 'warn')
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                <span class="font-semibold">{{ $mpesa['label'] }}.</span>
                <span class="text-amber-900/85 dark:text-amber-100/85"> {{ $mpesa['detail'] }}</span>
            </div>
        @elseif ($mpesa && $mpesa['tone'] === 'ok')
            <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">
                <span class="font-semibold">Daraja connected.</span>
                <span class="text-emerald-900/85 dark:text-emerald-100/85"> {{ $mpesa['detail'] }}</span>
            </div>
        @endif

        <div class="pb-card isp-settings-panel p-4 sm:p-6">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-3 border-b border-dashed border-[var(--pb-border)] pb-5">
                <div>
                    <h3 class="isp-settings-section-title">Daraja configuration</h3>
                    <p class="isp-settings-hint mt-1 max-w-2xl">Consumer credentials are encrypted at rest. Rotate keys from the Safaricom developer portal if they are exposed.</p>
                </div>
                @if ($paymentMethod && $paymentMethod->active)
                    <span class="pb-pill pb-pill-ok text-[10px]">Live on workspace</span>
                @elseif ($paymentMethod)
                    <span class="pb-pill pb-pill-warn text-[10px]">Stored — inactive</span>
                @else
                    <span class="pb-pill pb-pill-bad text-[10px]">Not provisioned</span>
                @endif
            </div>

            <form
                method="POST"
                action="{{ route('isp.settings.payment.store') }}"
                class="space-y-5"
                x-data="{ dirty: {{ $paymentMethod ? 'false' : 'true' }}, saving: false }"
                @input="dirty = true"
                @change="dirty = true"
                @submit="saving = true"
            >
                @csrf

                @if ($recommendedCallback)
                    <div class="rounded-xl border border-indigo-200/90 bg-indigo-50/80 px-4 py-3 text-sm text-indigo-950 dark:border-indigo-500/35 dark:bg-indigo-950/35 dark:text-indigo-100">
                        <p class="font-semibold">Safaricom callback URL (this workspace)</p>
                        <p class="isp-settings-hint mt-1">Register this exact URL in the Daraja app for STK Push validation and confirmation.</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <code class="max-w-full break-all rounded-lg bg-white/90 px-2 py-1 text-[11px] text-slate-900 dark:bg-slate-900/90 dark:text-slate-100">{{ $recommendedCallback }}</code>
                            <button
                                type="button"
                                class="pb-btn shrink-0 text-xs"
                                onclick="navigator.clipboard.writeText(@js($recommendedCallback))"
                            >
                                Copy
                            </button>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="consumer_key">Consumer key</label>
                        <input
                            id="consumer_key"
                            class="isp-form-input"
                            type="text"
                            name="consumer_key"
                            value="{{ old('consumer_key', $paymentMethod ? $paymentMethod->getCredential('consumer_key') : '') }}"
                            required
                            placeholder="Daraja consumer key"
                        />
                        @error('consumer_key')
                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="consumer_secret">Consumer secret</label>
                        <input
                            id="consumer_secret"
                            class="isp-form-input"
                            type="password"
                            name="consumer_secret"
                            value="{{ old('consumer_secret', $paymentMethod ? $paymentMethod->getCredential('consumer_secret') : '') }}"
                            required
                            placeholder="Daraja consumer secret"
                        />
                        @error('consumer_secret')
                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="isp-form-label" for="shortcode">Shortcode</label>
                        <input
                            id="shortcode"
                            class="isp-form-input"
                            type="text"
                            name="shortcode"
                            value="{{ old('shortcode', $paymentMethod ? $paymentMethod->getCredential('shortcode') : '') }}"
                            required
                        />
                        @error('shortcode')
                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="isp-form-label" for="passkey">Passkey</label>
                        <input
                            id="passkey"
                            class="isp-form-input"
                            type="password"
                            name="passkey"
                            value="{{ old('passkey', $paymentMethod ? $paymentMethod->getCredential('passkey') : '') }}"
                            required
                        />
                        @error('passkey')
                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="callback_url">Callback URL</label>
                        <input
                            id="callback_url"
                            class="isp-form-input"
                            type="url"
                            name="callback_url"
                            value="{{ old('callback_url', $paymentMethod?->callback_url ?? ($recommendedCallback ?? url('/api/payments/mpesa/callback'))) }}"
                            placeholder="{{ $recommendedCallback ?? url('/api/payments/mpesa/callback') }}"
                        />
                        <p class="isp-settings-hint mt-1">
                            Prefer the tokenized URL above so callbacks are tied to this ISP. Legacy endpoint:
                            <span class="font-mono text-[11px]">{{ url('/api/payments/mpesa/callback') }}</span>
                        </p>
                        @error('callback_url')
                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-4 rounded-xl border border-[var(--pb-border)] bg-slate-50/40 p-4 dark:bg-slate-900/20">
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Customer experience</h4>
                    <div>
                        <label class="isp-form-label" for="customer_instructions">Payment instructions (customer portal / checkout)</label>
                        <textarea
                            id="customer_instructions"
                            name="customer_instructions"
                            rows="4"
                            class="isp-form-input"
                            placeholder="e.g. Paybill 123456 — use your phone number as the account reference."
                        >{{ old('customer_instructions', $paymentExperience['customer_instructions'] ?? '') }}</textarea>
                        @error('customer_instructions')
                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="isp-form-label" for="sms_sender_id">SMS sender ID (optional)</label>
                            <input
                                id="sms_sender_id"
                                class="isp-form-input"
                                type="text"
                                name="sms_sender_id"
                                maxlength="11"
                                value="{{ old('sms_sender_id', $paymentExperience['sms_sender_id'] ?? '') }}"
                                placeholder="e.g. TECHCONNECT"
                            />
                            @error('sms_sender_id')
                                <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="isp-form-label" for="sms_api_key">SMS API key (optional)</label>
                            <input
                                id="sms_api_key"
                                class="isp-form-input"
                                type="password"
                                name="sms_api_key"
                                autocomplete="new-password"
                                placeholder="{{ ! empty($paymentExperience['sms_api_key_enc'] ?? null) ? 'Leave blank to keep the saved key' : 'Provider API key' }}"
                            />
                            @error('sms_api_key')
                                <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="isp-form-label" for="sms_api_secret">SMS API secret (optional)</label>
                            <input
                                id="sms_api_secret"
                                class="isp-form-input"
                                type="password"
                                name="sms_api_secret"
                                autocomplete="new-password"
                                placeholder="{{ ! empty($paymentExperience['sms_api_secret_enc'] ?? null) ? 'Leave blank to keep the saved secret' : 'Provider API secret' }}"
                            />
                            @error('sms_api_secret')
                                <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <p class="isp-settings-hint">Branded SMS fields are stored encrypted. Integration with your SMS provider can be wired in notifications.</p>
                </div>

                <div class="space-y-4 rounded-xl border border-[var(--pb-border)] bg-slate-50/40 p-4 dark:bg-slate-900/20">
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">M-Pesa reconciliation</h4>
                    <div>
                        <label class="isp-form-label" for="stk_account_reference">STK Push account reference</label>
                        <select id="stk_account_reference" name="stk_account_reference" class="isp-form-input">
                            @php $stk = old('stk_account_reference', $paymentExperience['stk_account_reference'] ?? 'payment_ref'); @endphp
                            <option value="payment_ref" @selected($stk === 'payment_ref')>Payment reference (default)</option>
                            <option value="phone" @selected($stk === 'phone')>Customer phone number</option>
                            <option value="full_name" @selected($stk === 'full_name')>Customer name</option>
                            <option value="customer_id" @selected($stk === 'customer_id')>System ID (C{id})</option>
                        </select>
                        <p class="isp-settings-hint mt-1">Shown to the customer in the M-Pesa prompt; must help you match payments to accounts.</p>
                        @error('stk_account_reference')
                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-[var(--pb-border)] bg-white/60 px-3 py-3 dark:bg-slate-900/40">
                        <input
                            type="hidden"
                            name="auto_activate_on_payment"
                            value="0"
                        />
                        <input
                            type="checkbox"
                            name="auto_activate_on_payment"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                            {{ old('auto_activate_on_payment', ($paymentExperience['auto_activate_on_payment'] ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}
                        />
                        <span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Automatically activate subscription on successful M-Pesa payment</span>
                            <span class="isp-settings-hint block">When off, the payment is still recorded but router/subscription automation is skipped until you use Record payment or another workflow.</span>
                        </span>
                    </label>
                </div>

                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                    <input
                        type="checkbox"
                        name="active"
                        value="1"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                        {{ old('active', $paymentMethod && $paymentMethod->active) ? 'checked' : '' }}
                    />
                    <span>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Activate Daraja for this ISP</span>
                        <span class="isp-settings-hint block">When off, no STK or C2B callbacks are accepted for this workspace.</span>
                    </span>
                </label>

                <div class="isp-settings-sticky-save">
                    <p class="mr-auto text-xs text-slate-500 dark:text-slate-400" x-show="dirty" x-cloak>
                        <span class="font-semibold text-amber-700 dark:text-amber-300">Unsaved changes</span>
                    </p>
                    <button type="button" class="pb-btn" @click="window.location.reload()">Cancel</button>
                    <button type="submit" class="pb-btn pb-btn-primary" :disabled="!dirty || saving" :class="(!dirty || saving) ? 'opacity-50 pointer-events-none' : ''">
                        <span x-show="!saving">Save payment configuration</span>
                        <span x-show="saving" x-cloak>Saving…</span>
                    </button>
                </div>
            </form>

            @if ($paymentMethod)
                <div class="mt-6 border-t border-dashed border-[var(--pb-border)] pt-5">
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Destructive actions</h4>
                    <p class="isp-settings-hint mt-1">Removing Daraja stops automated reconciliation until you provision a new method.</p>
                    <form
                        method="POST"
                        action="{{ route('isp.settings.payment.destroy', $paymentMethod) }}"
                        class="mt-3"
                        onsubmit="confirmFormSubmit(event, {title: 'Delete payment method', text: 'Remove Daraja credentials for this ISP? This cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, delete', confirmButtonColor: '#ef4444'}); return false;"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="pb-btn border-red-200 text-red-700 hover:border-red-300 hover:bg-red-50 dark:border-red-900/50 dark:text-red-300 dark:hover:bg-red-950/40">
                            Delete Daraja profile
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </x-isp-settings-workspace>
@endsection
