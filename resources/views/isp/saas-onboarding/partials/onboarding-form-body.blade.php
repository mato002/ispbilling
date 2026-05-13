@php
    $methodOld = old('saas_billing_method', $isp->saas_billing_method ?? 'mpesa');
    $current = min(4, max(1, (int) ($isp->saas_onboarding_step ?? 1)));
    $pending = $isp->pendingSaasPlan;
    $verifyAmount = $verifyAmount ?? max(1, (int) ceil((float) config('services.mpesa.saas_onboarding_verification_amount', 1)));
@endphp

<input type="hidden" name="wizard_step" value="{{ $current }}">

<nav class="mb-8" aria-label="Onboarding progress">
    <ol class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 sm:gap-0 sm:text-sm">
        @foreach ([1 => 'Plan', 2 => 'Business', 3 => 'Network', 4 => 'M-Pesa'] as $n => $label)
            <li class="flex items-center gap-2">
                @if ($n > 1)
                    <span class="hidden text-slate-300 sm:inline dark:text-slate-600" aria-hidden="true">→</span>
                @endif
                <span @class([
                    'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-full px-2',
                    'bg-indigo-600 text-white' => $current === $n,
                    'bg-emerald-600 text-white' => $current > $n,
                    'border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-800' => $current < $n,
                ])>{{ $n }}</span>
                <span @class(['hidden sm:inline', 'text-slate-900 dark:text-white' => $current === $n])>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</nav>

@if ($current === 1)
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Step 1 — Plan</h2>
        <div class="mt-4 space-y-3">
            @foreach ($plans as $plan)
                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 hover:border-indigo-300 dark:border-slate-700 dark:hover:border-indigo-500/50 has-[:checked]:border-indigo-500 has-[:checked]:ring-2 has-[:checked]:ring-indigo-500/30">
                    <input type="radio" name="platform_saas_plan_id" value="{{ $plan->id }}" class="mt-1" {{ (int) old('platform_saas_plan_id', $isp->saas_pending_platform_plan_id) === $plan->id ? 'checked' : ($loop->first && ! old('platform_saas_plan_id') && ! $isp->saas_pending_platform_plan_id ? 'checked' : '') }} required>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-semibold text-slate-900 dark:text-white">{{ $plan->name }}</span>
                            <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">KES {{ number_format($plan->monthly_price, 0) }} / mo</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ $plan->max_customers ? 'Up to '.number_format($plan->max_customers).' subscribers' : 'Unlimited subscribers' }}
                            ·
                            @if ((int) $plan->trial_days <= 0)
                                No trial
                            @else
                                {{ (int) $plan->trial_days }}d trial
                            @endif
                        </p>
                    </div>
                </label>
            @endforeach
        </div>
        @error('platform_saas_plan_id')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
        <div class="mt-6 flex justify-end">
            <button type="submit" class="pb-btn pb-btn-primary px-8">Next</button>
        </div>
    </div>
@endif

@if ($current === 2)
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Step 2 — Business &amp; billing</h2>

        @if ($pending)
            <p class="text-sm text-indigo-700 dark:text-indigo-300"><strong>{{ $pending->name }}</strong> · KES {{ number_format($pending->monthly_price, 0) }}/mo</p>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="isp-form-label">Company / trading name</label>
                <input type="text" name="company_name" value="{{ old('company_name', $isp->company_name) }}" required class="isp-form-input">
                @error('company_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="isp-form-label">Business address</label>
                <textarea name="address" rows="2" class="isp-form-input" placeholder="Street, town, county">{{ old('address', $isp->address) }}</textarea>
                @error('address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">Customer support phone</label>
                <input type="text" name="support_phone" value="{{ old('support_phone', $isp->support_phone) }}" class="isp-form-input" placeholder="2547XXXXXXXX or 07XXXXXXXX">
                @error('support_phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">KRA PIN (optional)</label>
                <input type="text" name="saas_company_pin" value="{{ old('saas_company_pin', $isp->saas_company_pin) }}" maxlength="32" class="isp-form-input" placeholder="P051234567X">
                @error('saas_company_pin')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">How you pay us</span>
            <div class="mt-2 flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="radio" name="saas_billing_method" value="mpesa" class="rounded border-slate-300" {{ $methodOld === 'mpesa' ? 'checked' : '' }}>
                    M-Pesa
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="radio" name="saas_billing_method" value="bank" class="rounded border-slate-300" {{ $methodOld === 'bank' ? 'checked' : '' }}>
                    Bank transfer
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="radio" name="saas_billing_method" value="card" class="rounded border-slate-300" {{ $methodOld === 'card' ? 'checked' : '' }}>
                    Card / invoice
                </label>
            </div>
            @error('saas_billing_method')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-4" id="pb-onboarding-pay-mpesa" data-billing-panel="mpesa">
            <label class="isp-form-label">M-Pesa number</label>
            <input type="text" name="saas_billing_phone" value="{{ old('saas_billing_phone', $isp->saas_billing_phone ?? $isp->phone) }}" class="isp-form-input" placeholder="2547XXXXXXXX" autocomplete="tel">
            @error('saas_billing_phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            <p class="text-xs text-slate-500 dark:text-slate-400">One-time KES {{ $verifyAmount }} STK to verify this number.</p>

            @if ($isp->saas_billing_method === 'mpesa' && ! $isp->saas_payment_verified_at)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100">
                    <p class="font-medium">After you approve the KES {{ $verifyAmount }} STK</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="submit" formnovalidate name="wizard_action" value="continue_after_verify" class="pb-btn pb-btn-primary text-sm">Continue</button>
                        @if (app()->environment('local'))
                            <button type="submit" formnovalidate name="wizard_action" value="local_dev_skip_mpesa_verify" class="rounded-lg border border-amber-400 bg-white px-3 py-2 text-sm font-medium text-amber-950 shadow-sm hover:bg-amber-100 dark:border-amber-500 dark:bg-slate-900 dark:text-amber-100 dark:hover:bg-slate-800">
                                Skip callback (local)
                            </button>
                        @endif
                    </div>
                    @if (app()->environment('local'))
                        <p class="mt-2 text-xs text-amber-900/90 dark:text-amber-100/90">Local: needs reachable <code class="rounded bg-white/60 px-0.5 dark:bg-slate-900/60">MPESA_CALLBACK_URL</code> (e.g. ngrok) or use Skip.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="hidden space-y-4" id="pb-onboarding-pay-bank" data-billing-panel="bank">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="isp-form-label">Bank name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', data_get($isp->settings, 'platform_billing.bank_name')) }}" class="isp-form-input">
                    @error('bank_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label">Account name</label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name', data_get($isp->settings, 'platform_billing.bank_account_name')) }}" class="isp-form-input">
                    @error('bank_account_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label">Account number</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', data_get($isp->settings, 'platform_billing.bank_account_number')) }}" class="isp-form-input">
                    @error('bank_account_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="isp-form-label">Branch / reference (optional)</label>
                    <input type="text" name="bank_branch" value="{{ old('bank_branch', data_get($isp->settings, 'platform_billing.bank_branch')) }}" class="isp-form-input">
                    @error('bank_branch')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="hidden space-y-4" id="pb-onboarding-pay-card" data-billing-panel="card">
            <div>
                <label class="isp-form-label">Billing email</label>
                <input type="email" name="billing_email" value="{{ old('billing_email', data_get($isp->settings, 'platform_billing.billing_email', $isp->email)) }}" class="isp-form-input">
                @error('billing_email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">Cardholder name (optional)</label>
                <input type="text" name="cardholder_name" value="{{ old('cardholder_name', data_get($isp->settings, 'platform_billing.cardholder_name')) }}" class="isp-form-input">
                @error('cardholder_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">Payment notes (optional)</label>
                <textarea name="payment_reference_note" rows="2" class="isp-form-input">{{ old('payment_reference_note', data_get($isp->settings, 'platform_billing.payment_reference_note')) }}</textarea>
                @error('payment_reference_note')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <label class="flex items-start gap-3 text-sm text-slate-700 dark:text-slate-200">
            <input type="checkbox" name="saas_white_label_opt_in" value="1" class="mt-1 rounded border-slate-300" {{ old('saas_white_label_opt_in', $isp->saas_white_label_opt_in) ? 'checked' : '' }}>
            <span>White-label (one-time on first invoice)</span>
        </label>

        <div class="flex justify-end gap-3">
            <button type="submit" class="pb-btn pb-btn-primary px-8">Save &amp; continue</button>
        </div>
    </div>
@endif

@if ($current === 3)
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Step 3 — Router</h2>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="isp-form-label">Router display name</label>
                <input type="text" name="router_name" value="{{ old('router_name', 'Primary router') }}" required class="isp-form-input">
                @error('router_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">Router IP or hostname</label>
                <input type="text" name="router_ip_address" value="{{ old('router_ip_address') }}" required class="isp-form-input" placeholder="192.168.88.1">
                @error('router_ip_address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">API port</label>
                <input type="number" name="router_port" value="{{ old('router_port', 8728) }}" required class="isp-form-input" min="1" max="65535">
                @error('router_port')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">API username</label>
                <input type="text" name="router_username" value="{{ old('router_username') }}" required class="isp-form-input" autocomplete="username" placeholder="RouterOS API user (e.g. admin)">
                @error('router_username')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">API password</label>
                <input type="password" name="router_password" value="{{ old('router_password') }}" required class="isp-form-input" autocomplete="new-password" placeholder="API user password">
                @error('router_password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="isp-form-label">Hotspot profiles to import (optional)</label>
            <textarea name="import_profile_names_csv" rows="2" class="isp-form-input font-mono text-sm" placeholder="Comma or newline, MikroTik names">{{ old('import_profile_names_csv') }}</textarea>
            @error('import_profile_names_csv')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
            <p class="text-sm font-medium text-slate-800 dark:text-slate-200">Add a plan (optional)</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="isp-form-label text-xs">Plan name</label>
                    <input type="text" name="new_plan_name" value="{{ old('new_plan_name') }}" class="isp-form-input" placeholder="5Mbps Unlimited">
                </div>
                <div>
                    <label class="isp-form-label text-xs">MikroTik profile name</label>
                    <input type="text" name="new_plan_speed_profile" value="{{ old('new_plan_speed_profile') }}" class="isp-form-input" placeholder="5Mbps-profile">
                </div>
                <div>
                    <label class="isp-form-label text-xs">Retail price (KES)</label>
                    <input type="number" step="0.01" name="new_plan_price" value="{{ old('new_plan_price', '0') }}" class="isp-form-input" min="0">
                </div>
            </div>
            @error('new_plan_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            @error('new_plan_speed_profile')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="pb-btn pb-btn-primary px-8">Next</button>
        </div>
    </div>
@endif

@if ($current === 4)
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 space-y-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Step 4 — Customer M-Pesa</h2>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="isp-form-label">Shortcode</label>
                <input type="text" name="mpesa_shortcode" value="{{ old('mpesa_shortcode') }}" required class="isp-form-input" autocomplete="off">
                @error('mpesa_shortcode')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">Passkey</label>
                <input type="text" name="mpesa_passkey" value="{{ old('mpesa_passkey') }}" required class="isp-form-input" autocomplete="off">
                @error('mpesa_passkey')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="isp-form-label">Consumer key</label>
                <input type="text" name="mpesa_consumer_key" value="{{ old('mpesa_consumer_key') }}" required class="isp-form-input" autocomplete="off">
                @error('mpesa_consumer_key')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="isp-form-label">Consumer secret</label>
                <input type="password" name="mpesa_consumer_secret" value="{{ old('mpesa_consumer_secret') }}" required class="isp-form-input" autocomplete="new-password">
                @error('mpesa_consumer_secret')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">Environment</label>
                <select name="mpesa_environment" class="isp-form-input">
                    <option value="sandbox" {{ old('mpesa_environment', config('services.mpesa.environment', 'sandbox')) === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                    <option value="production" {{ old('mpesa_environment') === 'production' ? 'selected' : '' }}>Production</option>
                </select>
                @error('mpesa_environment')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="checkbox" name="mpesa_active" value="1" class="rounded border-slate-300" {{ old('mpesa_active', true) ? 'checked' : '' }}>
                    Active
                </label>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="pb-btn pb-btn-primary px-8">Finish setup</button>
        </div>
    </div>
@endif

<script>
(function () {
    function syncPanels() {
        var checked = document.querySelector('input[name="saas_billing_method"]:checked');
        var v = checked ? checked.value : 'mpesa';
        document.querySelectorAll('[data-billing-panel]').forEach(function (el) {
            var on = el.getAttribute('data-billing-panel') === v;
            el.classList.toggle('hidden', !on);
        });
    }
    document.querySelectorAll('input[name="saas_billing_method"]').forEach(function (r) {
        r.addEventListener('change', syncPanels);
    });
    syncPanels();

    var form = document.getElementById('pb-saas-onboarding-form');
    if (!form || !form.dataset.draftIsp) {
        return;
    }
    var draftKey = 'pb-saas-onboarding/v1/' + form.dataset.draftIsp + '/' + form.dataset.draftStep;

    function setControlValue(name, value) {
        var ctrl = form.elements.namedItem(name);
        if (!ctrl) {
            return;
        }
        if (window.RadioNodeList && ctrl instanceof RadioNodeList) {
            for (var i = 0; i < ctrl.length; i++) {
                ctrl[i].checked = String(ctrl[i].value) === String(value);
            }
            return;
        }
        if (ctrl.type === 'checkbox') {
            ctrl.checked = value === true || value === 1 || value === '1' || value === 'on';
            return;
        }
        ctrl.value = value == null ? '' : String(value);
    }

    function collectDraft() {
        var data = {};
        var list = form.querySelectorAll('input, select, textarea');
        for (var i = 0; i < list.length; i++) {
            var el = list[i];
            if (!el.name || el.name === '_token') {
                continue;
            }
            if (el.type === 'checkbox') {
                data[el.name] = el.checked ? (el.value || '1') : '';
                continue;
            }
            if (el.type === 'radio') {
                if (el.checked) {
                    data[el.name] = el.value;
                }
                continue;
            }
            data[el.name] = el.value;
        }
        return data;
    }

    function saveDraft() {
        try {
            localStorage.setItem(draftKey, JSON.stringify(collectDraft()));
        } catch (e) {}
    }

    var saveTimer;
    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveDraft, 350);
    }
    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);

    if (form.dataset.draftSkipRestore !== '1') {
        try {
            var raw = localStorage.getItem(draftKey);
            if (raw) {
                var parsed = JSON.parse(raw);
                if (parsed && typeof parsed === 'object') {
                    Object.keys(parsed).forEach(function (name) {
                        setControlValue(name, parsed[name]);
                    });
                    syncPanels();
                }
            }
        } catch (e) {}
    }

    form.addEventListener('submit', function () {
        try {
            localStorage.setItem(draftKey, JSON.stringify(collectDraft()));
        } catch (e) {}
    });
})();
</script>
