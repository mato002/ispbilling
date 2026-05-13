@php
    $methodOld = old('saas_billing_method', 'mpesa');
@endphp

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">1. Access plan</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Includes a free trial before your first platform invoice (M-Pesa STK is used on billing day when that method is selected).</p>
    <div class="mt-4 space-y-3">
        @foreach ($plans as $plan)
            <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 hover:border-indigo-300 dark:border-slate-700 dark:hover:border-indigo-500/50 has-[:checked]:border-indigo-500 has-[:checked]:ring-2 has-[:checked]:ring-indigo-500/30">
                <input type="radio" name="platform_saas_plan_id" value="{{ $plan->id }}" class="mt-1" {{ (int) old('platform_saas_plan_id') === $plan->id ? 'checked' : ($loop->first && ! old('platform_saas_plan_id') ? 'checked' : '') }} required>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <span class="font-semibold text-slate-900 dark:text-white">{{ $plan->name }}</span>
                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">KES {{ number_format($plan->monthly_price, 0) }} / mo</span>
                    </div>
                    @if ($plan->description)
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $plan->description }}</p>
                    @endif
                    <ul class="mt-2 list-inside list-disc text-xs text-slate-500 dark:text-slate-400">
                        <li>{{ $plan->max_customers ? 'Up to '.number_format($plan->max_customers).' subscribers' : 'Unlimited subscribers' }}</li>
                        <li>{{ $plan->trial_days }}-day trial</li>
                        @if ($plan->hasFeature('advanced_analytics'))
                            <li>Advanced analytics</li>
                        @endif
                        @if ($plan->hasFeature('sms_automation'))
                            <li>Automated SMS</li>
                        @endif
                    </ul>
                </div>
            </label>
        @endforeach
    </div>
    @error('platform_saas_plan_id')
        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">2. Billing profile</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Legal / trading identity for PrimeBilling platform invoices.</p>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="isp-form-label">Company / trading name</label>
            <input type="text" name="company_name" value="{{ old('company_name', $isp->company_name) }}" required class="isp-form-input">
            @error('company_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="isp-form-label">KRA PIN (optional)</label>
            <input type="text" name="saas_company_pin" value="{{ old('saas_company_pin', $isp->saas_company_pin) }}" maxlength="32" class="isp-form-input" placeholder="P051234567X">
            @error('saas_company_pin')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">3. Platform payment method &amp; details</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">We store billing instructions for your subscription. We never store full card numbers in this app—use card/bank details for invoicing and manual or gateway-settled payments.</p>

    <div class="mt-4 space-y-3">
        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">How you pay PrimeBilling</span>
        <div class="flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="radio" name="saas_billing_method" value="mpesa" class="rounded border-slate-300" {{ $methodOld === 'mpesa' ? 'checked' : '' }}>
                M-Pesa (STK on billing day)
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="radio" name="saas_billing_method" value="bank" class="rounded border-slate-300" {{ $methodOld === 'bank' ? 'checked' : '' }}>
                Bank transfer
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="radio" name="saas_billing_method" value="card" class="rounded border-slate-300" {{ $methodOld === 'card' ? 'checked' : '' }}>
                Card / invoice link
            </label>
        </div>
        @error('saas_billing_method')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="mt-6 space-y-4" id="pb-onboarding-pay-mpesa" data-billing-panel="mpesa">
        <label class="isp-form-label">M-Pesa billing number</label>
        <input type="text" name="saas_billing_phone" value="{{ old('saas_billing_phone', $isp->saas_billing_phone ?? $isp->phone) }}" class="isp-form-input" placeholder="2547XXXXXXXX" autocomplete="tel">
        @error('saas_billing_phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="mt-6 hidden space-y-4" id="pb-onboarding-pay-bank" data-billing-panel="bank">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="isp-form-label">Bank name</label>
                <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="isp-form-input" placeholder="e.g. Equity Bank">
                @error('bank_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">Account name</label>
                <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}" class="isp-form-input">
                @error('bank_account_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label">Account number</label>
                <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" class="isp-form-input">
                @error('bank_account_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="isp-form-label">Branch / reference (optional)</label>
                <input type="text" name="bank_branch" value="{{ old('bank_branch') }}" class="isp-form-input">
                @error('bank_branch')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="mt-6 hidden space-y-4" id="pb-onboarding-pay-card" data-billing-panel="card">
        <div>
            <label class="isp-form-label">Billing email (invoices &amp; payment links)</label>
            <input type="email" name="billing_email" value="{{ old('billing_email', $isp->email) }}" class="isp-form-input" placeholder="billing@company.com">
            @error('billing_email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="isp-form-label">Cardholder name (as on card)</label>
            <input type="text" name="cardholder_name" value="{{ old('cardholder_name') }}" class="isp-form-input">
            @error('cardholder_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400">Do not enter a full card number here. Use your PSP (e.g. Stripe) separately if you enable it; this field is for the name we attach to billing records.</p>
        <div>
            <label class="isp-form-label">Payment notes / reference (optional)</label>
            <textarea name="payment_reference_note" rows="2" class="isp-form-input" placeholder="PO number, finance contact, etc.">{{ old('payment_reference_note') }}</textarea>
            @error('payment_reference_note')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">4. Optional white-label setup</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">If enabled, the plan’s one-time setup fee is added to your first platform invoice after the trial.</p>
    <label class="mt-4 flex items-start gap-3 text-sm text-slate-700 dark:text-slate-200">
        <input type="checkbox" name="saas_white_label_opt_in" value="1" class="mt-1 rounded border-slate-300" {{ old('saas_white_label_opt_in') ? 'checked' : '' }}>
        <span>Include white-label setup (logo & workspace styling) — billed once on first invoice.</span>
    </label>
</div>

<div class="flex justify-end gap-3">
    <button type="submit" class="pb-btn pb-btn-primary px-8">{{ $submitLabel ?? 'Continue to dashboard' }}</button>
</div>

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
})();
</script>
