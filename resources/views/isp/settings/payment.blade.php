@extends('layouts.admin')

@section('title', 'Payments & M-Pesa · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $S = $ispSettingsShell;
        $mpesa = $S['mpesa'] ?? null;
        $env = old('environment', $credentials['environment'] ?? 'sandbox');
        $txnType = old('transaction_type', $credentials['transaction_type'] ?? 'CustomerPayBillOnline');
        $exp = $paymentExperience;
    @endphp

    <x-isp-settings-workspace meta-title="Payments & M-Pesa">
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

        <div class="pb-card isp-settings-panel p-4 sm:p-5" x-data="{ tab: 'daraja', dirty: {{ $paymentMethod ? 'false' : 'true' }}, saving: false }">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-dashed border-[var(--pb-border)] pb-4">
                <div class="flex flex-wrap gap-1">
                    @foreach ([
                        'daraja' => 'Daraja',
                        'callbacks' => 'Callbacks',
                        'b2c' => 'B2C / Payouts',
                        'workflows' => 'Workflows',
                        'offline' => 'Offline & backup',
                    ] as $key => $label)
                        <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                            :class="tab === '{{ $key }}' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'"
                            @click="tab = '{{ $key }}'">{{ $label }}</button>
                    @endforeach
                </div>
                @if ($paymentMethod && $paymentMethod->active)
                    <span class="pb-pill pb-pill-ok text-[10px]">Live · {{ $env === 'production' ? 'Production' : 'Sandbox' }}</span>
                @elseif ($paymentMethod)
                    <span class="pb-pill pb-pill-warn text-[10px]">Stored — inactive</span>
                @else
                    <span class="pb-pill pb-pill-bad text-[10px]">Not provisioned</span>
                @endif
            </div>

            <form method="POST" action="{{ route('isp.settings.payment.store') }}" class="space-y-4"
                @input="dirty = true" @change="dirty = true" @submit="saving = true">
                @csrf

                {{-- Daraja credentials --}}
                <div x-show="tab === 'daraja'" x-cloak class="space-y-4">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div>
                            <label class="isp-form-label" for="environment">Environment</label>
                            <select id="environment" name="environment" class="isp-form-input">
                                <option value="sandbox" @selected($env === 'sandbox')>Sandbox</option>
                                <option value="production" @selected($env === 'production')>Live production</option>
                            </select>
                        </div>
                        <div>
                            <label class="isp-form-label" for="transaction_type">Transaction type</label>
                            <select id="transaction_type" name="transaction_type" class="isp-form-input">
                                <option value="CustomerPayBillOnline" @selected($txnType === 'CustomerPayBillOnline')>Paybill</option>
                                <option value="CustomerBuyGoodsOnline" @selected($txnType === 'CustomerBuyGoodsOnline')>Till (Buy Goods)</option>
                            </select>
                        </div>
                        <div>
                            <label class="isp-form-label" for="shortcode">Shortcode</label>
                            <input id="shortcode" class="isp-form-input" type="text" name="shortcode" required
                                value="{{ old('shortcode', $credentials['shortcode'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="party_b">STK PartyB (optional)</label>
                            <input id="party_b" class="isp-form-input" type="text" name="party_b"
                                value="{{ old('party_b', $credentials['party_b'] ?? '') }}" placeholder="Defaults to shortcode" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="isp-form-label" for="consumer_key">Consumer key</label>
                            <input id="consumer_key" class="isp-form-input" type="text" name="consumer_key" required
                                value="{{ old('consumer_key', $credentials['consumer_key'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="consumer_secret">Consumer secret</label>
                            <input id="consumer_secret" class="isp-form-input" type="password" name="consumer_secret" required
                                value="{{ old('consumer_secret', $credentials['consumer_secret'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="passkey">Passkey (LNM)</label>
                            <input id="passkey" class="isp-form-input" type="password" name="passkey" required
                                value="{{ old('passkey', $credentials['passkey'] ?? '') }}" />
                        </div>
                    </div>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-[var(--pb-border)] px-3 py-2">
                        <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600"
                            {{ old('active', $paymentMethod && $paymentMethod->active) ? 'checked' : '' }} />
                        <span class="text-sm font-semibold">Activate Daraja for this ISP</span>
                    </label>
                </div>

                {{-- Callback URLs --}}
                <div x-show="tab === 'callbacks'" x-cloak class="space-y-3">
                    @foreach ([
                        'STK callback' => $stkCallback,
                        'STK timeout' => $stkTimeout,
                        'C2B validation' => $c2bValidation,
                        'C2B confirmation' => $c2bConfirmation,
                    ] as $label => $url)
                        <div class="rounded-lg border border-[var(--pb-border)] bg-slate-50/50 px-3 py-2 dark:bg-slate-900/20">
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $label }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <code class="max-w-full flex-1 break-all text-[11px]">{{ $url }}</code>
                                <button type="button" class="pb-btn shrink-0 text-xs" onclick="navigator.clipboard.writeText(@js($url))">Copy</button>
                            </div>
                        </div>
                    @endforeach
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="isp-form-label" for="callback_url">STK callback override</label>
                            <input id="callback_url" class="isp-form-input" type="url" name="callback_url"
                                value="{{ old('callback_url', $paymentMethod?->callback_url ?? $stkCallback) }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="timeout_url">STK timeout URL</label>
                            <input id="timeout_url" class="isp-form-input" type="url" name="timeout_url"
                                value="{{ old('timeout_url', $credentials['timeout_url'] ?? $stkTimeout) }}" />
                        </div>
                    </div>
                    @if ($paymentMethod)
                        <button type="button" class="pb-btn text-xs" onclick="document.getElementById('register-c2b-form').submit()">
                            Register C2B URLs with Safaricom
                        </button>
                    @endif
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="isp-form-label" for="stk_account_reference">STK account reference</label>
                            <select id="stk_account_reference" name="stk_account_reference" class="isp-form-input">
                                @php $stk = old('stk_account_reference', $exp['stk_account_reference'] ?? 'payment_ref'); @endphp
                                <option value="payment_ref" @selected($stk === 'payment_ref')>Payment reference</option>
                                <option value="phone" @selected($stk === 'phone')>Customer phone</option>
                                <option value="full_name" @selected($stk === 'full_name')>Customer name</option>
                                <option value="customer_id" @selected($stk === 'customer_id')>System ID</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- B2C --}}
                <div x-show="tab === 'b2c'" x-cloak class="space-y-3">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="isp-form-label" for="b2c_shortcode">B2C shortcode</label>
                            <input id="b2c_shortcode" class="isp-form-input" type="text" name="b2c_shortcode"
                                value="{{ old('b2c_shortcode', $credentials['b2c_shortcode'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="b2c_initiator_name">Initiator name</label>
                            <input id="b2c_initiator_name" class="isp-form-input" type="text" name="b2c_initiator_name"
                                value="{{ old('b2c_initiator_name', $credentials['b2c_initiator_name'] ?? '') }}" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="isp-form-label" for="b2c_security_credential">Security credential</label>
                            <input id="b2c_security_credential" class="isp-form-input" type="password" name="b2c_security_credential"
                                placeholder="{{ ! empty($credentials['b2c_security_credential'] ?? null) ? 'Leave blank to keep saved credential' : 'Encrypted initiator password from Daraja portal' }}" />
                        </div>
                    </div>
                </div>

                {{-- Workflows --}}
                <div x-show="tab === 'workflows'" x-cloak class="space-y-3">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div>
                            <label class="isp-form-label" for="grace_period_days">Grace period (days)</label>
                            <input id="grace_period_days" class="isp-form-input" type="number" min="0" max="30" name="grace_period_days"
                                value="{{ old('grace_period_days', $exp['grace_period_days'] ?? 0) }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="partial_payment_policy">Partial payment policy</label>
                            <select id="partial_payment_policy" name="partial_payment_policy" class="isp-form-input">
                                @php $ppp = old('partial_payment_policy', $exp['partial_payment_policy'] ?? 'proportional'); @endphp
                                <option value="proportional" @selected($ppp === 'proportional')>Extend expiry proportionally</option>
                                <option value="balance" @selected($ppp === 'balance')>Hold as account credit</option>
                                <option value="reject" @selected($ppp === 'reject')>Reject / leave unposted</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ([
                            ['auto_activate_on_payment', 'Auto-activate subscription on payment', $exp['auto_activate_on_payment'] ?? true],
                            ['auto_reconnect_on_payment', 'Auto-reconnect PPPoE / Hotspot on payment', $exp['auto_reconnect_on_payment'] ?? true],
                            ['auto_receipt_email', 'Email receipt on payment', $exp['auto_receipt_email'] ?? true],
                            ['auto_receipt_sms', 'SMS receipt on payment', $exp['auto_receipt_sms'] ?? false],
                            ['credit_apply_next_cycle', 'Apply credit to next billing cycle', $exp['credit_apply_next_cycle'] ?? true],
                        ] as [$name, $label, $checked])
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--pb-border)] px-3 py-2">
                                <input type="hidden" name="{{ $name }}" value="0" />
                                <input type="checkbox" name="{{ $name }}" value="1" class="h-4 w-4 rounded"
                                    {{ old($name, $checked ? '1' : '0') === '1' ? 'checked' : '' }} />
                                <span class="text-sm">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div>
                        <label class="isp-form-label" for="customer_instructions">Customer payment instructions</label>
                        <textarea id="customer_instructions" name="customer_instructions" rows="2" class="isp-form-input">{{ old('customer_instructions', $exp['customer_instructions'] ?? '') }}</textarea>
                    </div>
                </div>

                {{-- Offline & backup --}}
                <div x-show="tab === 'offline'" x-cloak class="space-y-3">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        @foreach ([
                            ['manual_payments_enabled', 'Allow manual payment recording', $exp['manual_payments_enabled'] ?? true],
                            ['offline_cash_enabled', 'Cash / OTC payments', $exp['offline_cash_enabled'] ?? true],
                            ['offline_bank_enabled', 'Bank deposit payments', $exp['offline_bank_enabled'] ?? true],
                        ] as [$name, $label, $checked])
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--pb-border)] px-3 py-2">
                                <input type="hidden" name="{{ $name }}" value="0" />
                                <input type="checkbox" name="{{ $name }}" value="1" class="h-4 w-4 rounded"
                                    {{ old($name, $checked ? '1' : '0') === '1' ? 'checked' : '' }} />
                                <span class="text-sm">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <label class="isp-form-label" for="sms_sender_id">SMS sender ID</label>
                            <input id="sms_sender_id" class="isp-form-input" type="text" name="sms_sender_id" maxlength="11"
                                value="{{ old('sms_sender_id', $exp['sms_sender_id'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="sms_api_key">SMS API key</label>
                            <input id="sms_api_key" class="isp-form-input" type="password" name="sms_api_key" autocomplete="new-password"
                                placeholder="{{ ! empty($exp['sms_api_key_enc'] ?? null) ? 'Leave blank to keep' : 'Optional' }}" />
                        </div>
                        <div>
                            <label class="isp-form-label" for="sms_api_secret">SMS API secret</label>
                            <input id="sms_api_secret" class="isp-form-input" type="password" name="sms_api_secret" autocomplete="new-password"
                                placeholder="{{ ! empty($exp['sms_api_secret_enc'] ?? null) ? 'Leave blank to keep' : 'Optional' }}" />
                        </div>
                    </div>
                    <div class="rounded-lg border border-indigo-200/80 bg-indigo-50/60 px-3 py-2 text-sm dark:border-indigo-500/30 dark:bg-indigo-950/30">
                        <span class="font-semibold">Backup gateways:</span>
                        <a href="{{ route('isp.payment-settings.index') }}" class="text-indigo-700 underline dark:text-indigo-300">Paystack, Stripe, PayPal &amp; more</a>
                        ·
                        <a href="{{ route('isp.payments.unposted') }}" class="text-indigo-700 underline dark:text-indigo-300">Unassigned payments</a>
                        ·
                        <a href="{{ route('isp.payments.transaction-logs') }}" class="text-indigo-700 underline dark:text-indigo-300">Webhook debugger</a>
                    </div>
                </div>

                <div class="isp-settings-sticky-save">
                    <p class="mr-auto text-xs text-slate-500" x-show="dirty" x-cloak>
                        <span class="font-semibold text-amber-700 dark:text-amber-300">Unsaved changes</span>
                    </p>
                    <button type="button" class="pb-btn" @click="window.location.reload()">Cancel</button>
                    <button type="submit" class="pb-btn pb-btn-primary" :disabled="!dirty || saving">
                        <span x-show="!saving">Save payment configuration</span>
                        <span x-show="saving" x-cloak>Saving…</span>
                    </button>
                </div>
            </form>

            @if ($paymentMethod)
                <form id="register-c2b-form" method="POST" action="{{ route('isp.settings.payment.register-c2b') }}" class="hidden">@csrf</form>
                <div class="mt-4 border-t border-dashed border-[var(--pb-border)] pt-4">
                    <form method="POST" action="{{ route('isp.settings.payment.destroy', $paymentMethod) }}"
                        onsubmit="confirmFormSubmit(event, {title: 'Delete payment method', text: 'Remove Daraja credentials?', icon: 'warning', confirmButtonText: 'Delete', confirmButtonColor: '#ef4444'}); return false;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="pb-btn border-red-200 text-red-700 text-xs">Delete Daraja profile</button>
                    </form>
                </div>
            @endif
        </div>
    </x-isp-settings-workspace>
@endsection
