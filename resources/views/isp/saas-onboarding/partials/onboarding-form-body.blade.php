@php
    $methodOld = old('saas_billing_method', $isp->saas_billing_method ?? 'mpesa');
    $current = min(4, max(1, (int) ($isp->saas_onboarding_step ?? 1)));
    $pending = $isp->pendingSaasPlan;
    $verifyAmount = $verifyAmount ?? max(1, (int) ceil((float) config('services.mpesa.saas_onboarding_verification_amount', 1)));
    $trialDeferred = $pending && $pending->defersOnboardingPaymentVerification((bool) old('saas_white_label_opt_in', $isp->saas_white_label_opt_in));
    $onboardingRouter = $isp->relationLoaded('routers') ? $isp->routers->sortBy('id')->first() : $isp->routers()->orderBy('id')->first();
    $billingPhoneRaw = old('saas_billing_phone', $isp->saas_billing_phone ?? $isp->phone);
    $billingPhoneDigits = preg_replace('/[^0-9]/', '', (string) $billingPhoneRaw);
    if (preg_match('/^254([0-9]{9})$/', $billingPhoneDigits, $billingMatch)) {
        $billingPhoneDisplay = $billingMatch[1];
    } elseif (preg_match('/^0([0-9]{9})$/', $billingPhoneDigits, $billingMatch)) {
        $billingPhoneDisplay = $billingMatch[1];
    } else {
        $billingPhoneDisplay = $billingPhoneDigits;
    }
@endphp

<input type="hidden" name="wizard_step" value="{{ $current }}">

@include('isp.saas-onboarding.partials.onboarding-stepper', ['current' => $current, 'isp' => $isp])

@if ($current === 1)
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-8">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Choose your plan</h2>
        </div>
        <div class="space-y-3">
            @foreach ($plans as $plan)
                <label class="group flex cursor-pointer gap-4 rounded-xl border-2 border-slate-200 bg-white p-4 transition-all hover:border-indigo-300 hover:shadow-md has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/40 has-[:checked]:shadow-md has-[:checked]:ring-2 has-[:checked]:ring-indigo-600/15 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-500/50 dark:has-[:checked]:border-indigo-500 dark:has-[:checked]:bg-indigo-500/10">
                    <input type="radio" name="platform_saas_plan_id" value="{{ $plan->id }}" class="mt-1 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-600" {{ (int) old('platform_saas_plan_id', $isp->saas_pending_platform_plan_id) === $plan->id ? 'checked' : ($loop->first && ! old('platform_saas_plan_id') && ! $isp->saas_pending_platform_plan_id ? 'checked' : '') }} required>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-semibold text-slate-900 dark:text-white">{{ $plan->name }}</span>
                            <span class="inline-flex rounded-full bg-indigo-600 px-2.5 py-0.5 text-xs font-bold text-white dark:bg-indigo-500">KES {{ number_format($plan->monthly_price, 0) }}/mo</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ $plan->max_customers ? 'Up to '.number_format($plan->max_customers).' subscribers' : 'Unlimited subscribers' }}
                            ·
                            @if ((int) $plan->trial_days <= 0)
                                No trial
                            @else
                                {{ (int) $plan->trial_days }}-day trial
                            @endif
                        </p>
                    </div>
                </label>
            @endforeach
        </div>
        @error('platform_saas_plan_id')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
        <div class="mt-8 flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
            <button type="submit" class="pb-btn pb-btn-primary px-8">Continue</button>
        </div>
    </div>
@endif

@if ($current === 2)
    <div
        id="pb-mpesa-stk-overlay"
        class="fixed inset-0 z-[200] hidden items-center justify-center bg-slate-900/50 px-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-live="polite"
    >
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div id="pb-mpesa-stk-overlay-spinner" class="mx-auto mb-5 h-11 w-11 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" aria-hidden="true"></div>
            <p id="pb-mpesa-stk-overlay-text" class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                Enter your M-Pesa PIN on your phone.
            </p>
            <button
                type="button"
                id="pb-mpesa-stk-overlay-cancel"
                class="mt-6 text-sm font-medium text-slate-500 underline-offset-2 hover:text-slate-700 hover:underline dark:text-slate-400 dark:hover:text-slate-200"
            >
                Cancel
            </button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-8 space-y-8">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Business &amp; billing</h2>
            @if ($pending)
                <div class="mt-4">
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-white shadow-lg shadow-slate-900/20 dark:bg-white dark:text-slate-900 dark:shadow-white/10">
                        <svg class="h-3.5 w-3.5 text-emerald-400 dark:text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        {{ $pending->name }} · KES {{ number_format($pending->monthly_price, 0) }}/mo
                    </span>
                </div>
            @endif
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="relative sm:col-span-2">
                <input
                    type="text"
                    name="company_name"
                    id="company_name"
                    value="{{ old('company_name', $isp->company_name) }}"
                    required
                    placeholder=" "
                    class="pb-onboarding-input peer"
                >
                <label for="company_name" class="pb-onboarding-label">Company / trading name</label>
                @error('company_name')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative sm:col-span-2">
                <textarea
                    name="address"
                    id="address"
                    rows="2"
                    placeholder=" "
                    class="pb-onboarding-input peer min-h-[4.5rem] resize-y"
                >{{ old('address', $isp->address) }}</textarea>
                <label for="address" class="pb-onboarding-label">Business address</label>
                @error('address')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <span class="pointer-events-none absolute left-3.5 top-[1.125rem] z-10 text-sm font-medium text-slate-400 peer-focus:text-indigo-600 dark:text-slate-500 dark:peer-focus:text-indigo-400">+254</span>
                <input
                    type="tel"
                    name="support_phone"
                    id="support_phone"
                    value="{{ old('support_phone', $isp->support_phone) }}"
                    placeholder=" "
                    inputmode="tel"
                    autocomplete="tel"
                    class="pb-onboarding-input peer !pl-[3.75rem]"
                >
                <label for="support_phone" class="pb-onboarding-label !left-[3.75rem] peer-focus:!left-3.5 peer-[:not(:placeholder-shown)]:!left-3.5">Customer support phone</label>
                @error('support_phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input
                    type="text"
                    name="saas_company_pin"
                    id="saas_company_pin"
                    value="{{ old('saas_company_pin', $isp->saas_company_pin) }}"
                    maxlength="32"
                    placeholder=" "
                    class="pb-onboarding-input peer"
                >
                <label for="saas_company_pin" class="pb-onboarding-label">KRA PIN <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                @error('saas_company_pin')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Payment method</span>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Payment method">
                @foreach ([
                    'mpesa' => ['label' => 'M-Pesa', 'icon' => 'mpesa'],
                    'bank' => ['label' => 'Bank transfer', 'icon' => 'bank'],
                    'card' => ['label' => 'Card / invoice', 'icon' => 'card'],
                ] as $value => $meta)
                    <label class="group relative flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-slate-200 bg-white p-4 text-center transition-all hover:border-indigo-300 hover:shadow-sm has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/50 has-[:checked]:shadow-md has-[:checked]:ring-2 has-[:checked]:ring-indigo-600/20 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-500/40 dark:has-[:checked]:border-indigo-500 dark:has-[:checked]:bg-indigo-500/10">
                        <input type="radio" name="saas_billing_method" value="{{ $value }}" class="sr-only" {{ $methodOld === $value ? 'checked' : '' }}>
                        <span @class([
                            'flex h-11 w-11 items-center justify-center rounded-xl transition-colors',
                            'bg-emerald-50 text-emerald-600 group-has-[:checked]:bg-emerald-600 group-has-[:checked]:text-white dark:bg-emerald-500/10 dark:text-emerald-400' => $meta['icon'] === 'mpesa',
                            'bg-slate-100 text-slate-600 group-has-[:checked]:bg-indigo-600 group-has-[:checked]:text-white dark:bg-slate-800 dark:text-slate-300' => $meta['icon'] === 'bank',
                            'bg-violet-50 text-violet-600 group-has-[:checked]:bg-indigo-600 group-has-[:checked]:text-white dark:bg-violet-500/10 dark:text-violet-400' => $meta['icon'] === 'card',
                        ])>
                            @if ($meta['icon'] === 'mpesa')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="5" y="2" width="14" height="20" rx="2"/>
                                    <path d="M12 18h.01"/>
                                    <path d="M8 6h8"/>
                                    <path d="M8 10h5"/>
                                </svg>
                            @elseif ($meta['icon'] === 'bank')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 21h18"/>
                                    <path d="M3 10h18"/>
                                    <path d="M5 6l7-3 7 3"/>
                                    <path d="M4 10v11"/>
                                    <path d="M20 10v11"/>
                                    <path d="M8 14v3"/>
                                    <path d="M12 14v3"/>
                                    <path d="M16 14v3"/>
                                </svg>
                            @else
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                                    <line x1="2" y1="10" x2="22" y2="10"/>
                                    <line x1="6" y1="15" x2="10" y2="15"/>
                                </svg>
                            @endif
                        </span>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $meta['label'] }}</span>
                    </label>
                @endforeach
            </div>
            @error('saas_billing_method')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-3" id="pb-onboarding-pay-mpesa" data-billing-panel="mpesa">
            <div class="relative">
                <span class="pointer-events-none absolute left-3.5 top-[1.125rem] z-10 text-sm font-medium text-slate-400 peer-focus:text-indigo-600 dark:text-slate-500">+254</span>
                <input
                    type="tel"
                    name="saas_billing_phone"
                    id="saas_billing_phone"
                    value="{{ $billingPhoneDisplay }}"
                    placeholder=" "
                    autocomplete="tel"
                    inputmode="tel"
                    class="pb-onboarding-input peer !pl-[3.75rem]"
                >
                <label for="saas_billing_phone" class="pb-onboarding-label !left-[3.75rem] peer-focus:!left-3.5 peer-[:not(:placeholder-shown)]:!left-3.5">M-Pesa number</label>
            </div>
            @error('saas_billing_phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            @if ($trialDeferred)
                <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-100">
                    Your {{ (int) $pending->trial_days }}-day free trial is active — we save your M-Pesa number for billing after the trial. No payment is required during setup.
                </p>
            @else
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    We send a one-time KES {{ $verifyAmount }} STK push to confirm this number before your first platform invoice.
                </p>
            @endif
        </div>

        <div class="hidden space-y-4" id="pb-onboarding-pay-bank" data-billing-panel="bank">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="relative sm:col-span-2">
                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', data_get($isp->settings, 'platform_billing.bank_name')) }}" placeholder=" " class="pb-onboarding-input peer">
                    <label for="bank_name" class="pb-onboarding-label">Bank name</label>
                    @error('bank_name')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="relative">
                    <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', data_get($isp->settings, 'platform_billing.bank_account_name')) }}" placeholder=" " class="pb-onboarding-input peer">
                    <label for="bank_account_name" class="pb-onboarding-label">Account name</label>
                    @error('bank_account_name')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="relative">
                    <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', data_get($isp->settings, 'platform_billing.bank_account_number')) }}" placeholder=" " class="pb-onboarding-input peer">
                    <label for="bank_account_number" class="pb-onboarding-label">Account number</label>
                    @error('bank_account_number')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="relative sm:col-span-2">
                    <input type="text" name="bank_branch" id="bank_branch" value="{{ old('bank_branch', data_get($isp->settings, 'platform_billing.bank_branch')) }}" placeholder=" " class="pb-onboarding-input peer">
                    <label for="bank_branch" class="pb-onboarding-label">Branch / reference <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                    @error('bank_branch')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="hidden space-y-4" id="pb-onboarding-pay-card" data-billing-panel="card">
            <div class="relative">
                <input type="email" name="billing_email" id="billing_email" value="{{ old('billing_email', data_get($isp->settings, 'platform_billing.billing_email', $isp->email)) }}" placeholder=" " class="pb-onboarding-input peer">
                <label for="billing_email" class="pb-onboarding-label">Billing email</label>
                @error('billing_email')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="cardholder_name" id="cardholder_name" value="{{ old('cardholder_name', data_get($isp->settings, 'platform_billing.cardholder_name')) }}" placeholder=" " class="pb-onboarding-input peer">
                <label for="cardholder_name" class="pb-onboarding-label">Cardholder name <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                @error('cardholder_name')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <textarea name="payment_reference_note" id="payment_reference_note" rows="2" placeholder=" " class="pb-onboarding-input peer min-h-[4.5rem] resize-y">{{ old('payment_reference_note', data_get($isp->settings, 'platform_billing.payment_reference_note')) }}</textarea>
                <label for="payment_reference_note" class="pb-onboarding-label">Payment notes <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                @error('payment_reference_note')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/40">
            <input type="checkbox" name="saas_white_label_opt_in" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-600" {{ old('saas_white_label_opt_in', $isp->saas_white_label_opt_in) ? 'checked' : '' }}>
            <span class="text-sm font-medium text-slate-900 dark:text-white">White-label branding</span>
        </label>

        @if (app()->environment('local') && ! $isp->saas_payment_verified_at)
            <button
                type="submit"
                formnovalidate
                name="wizard_action"
                value="local_dev_skip_mpesa_verify"
                class="text-xs text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300"
            >
                Dev: skip M-Pesa verify
            </button>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
            <button type="submit" formnovalidate name="wizard_action" value="go_back" class="pb-btn px-6">Back</button>
            <button
                type="submit"
                id="pb-onboarding-step2-submit"
                class="pb-btn pb-btn-primary px-8"
                data-label-mpesa="{{ $trialDeferred ? 'Continue' : 'Verify M-Pesa' }}"
                data-label-default="Continue"
            >
                @if (($isp->saas_billing_method ?? 'mpesa') === 'mpesa' && ! $isp->saas_payment_verified_at && ! $trialDeferred)
                    Verify M-Pesa
                @else
                    Continue
                @endif
            </button>
        </div>
    </div>
@endif

@if ($current === 3)
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-8 space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Connect your router</h2>
            <p class="mt-3 rounded-lg border border-amber-200/80 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-100">
                Use an IP or hostname that <strong>this server can reach</strong>. Private addresses like <code class="font-mono">192.168.x.x</code> only work when PrimeBilling runs on the same network as the router.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="relative sm:col-span-2">
                <input type="text" name="router_name" id="router_name" value="{{ old('router_name', $onboardingRouter?->name ?? 'Primary router') }}" required placeholder=" " class="pb-onboarding-input peer">
                <label for="router_name" class="pb-onboarding-label">Router display name</label>
                @error('router_name')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="router_ip_address" id="router_ip_address" value="{{ old('router_ip_address', $onboardingRouter?->ip_address) }}" required placeholder=" " class="pb-onboarding-input peer">
                <label for="router_ip_address" class="pb-onboarding-label">Router IP or hostname</label>
                @error('router_ip_address')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="router_port" id="router_port" value="{{ old('router_port', $onboardingRouter?->port ?? 8728) }}" required min="1" max="65535" placeholder=" " class="pb-onboarding-input peer">
                <label for="router_port" class="pb-onboarding-label">API port</label>
                @error('router_port')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="router_username" id="router_username" value="{{ old('router_username', $onboardingRouter?->username) }}" required autocomplete="username" placeholder=" " class="pb-onboarding-input peer">
                <label for="router_username" class="pb-onboarding-label">API username</label>
                @error('router_username')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="password" name="router_password" id="router_password" value="{{ old('router_password') }}" required autocomplete="new-password" placeholder=" " class="pb-onboarding-input peer">
                <label for="router_password" class="pb-onboarding-label">API password</label>
                @error('router_password')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="relative">
            <textarea name="import_profile_names_csv" id="import_profile_names_csv" rows="2" placeholder=" " class="pb-onboarding-input peer min-h-[4.5rem] resize-y font-mono text-sm">{{ old('import_profile_names_csv') }}</textarea>
            <label for="import_profile_names_csv" class="pb-onboarding-label">Hotspot profiles to import <span class="font-normal normal-case text-slate-400">(optional)</span></label>
            @error('import_profile_names_csv')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-800/60">
            <p class="text-sm font-medium text-slate-800 dark:text-slate-200">Add a plan (optional)</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div class="relative">
                    <input type="text" name="new_plan_name" id="new_plan_name" value="{{ old('new_plan_name') }}" placeholder=" " class="pb-onboarding-input peer text-sm">
                    <label for="new_plan_name" class="pb-onboarding-label text-[10px]">Plan name</label>
                </div>
                <div class="relative">
                    <input type="text" name="new_plan_speed_profile" id="new_plan_speed_profile" value="{{ old('new_plan_speed_profile') }}" placeholder=" " class="pb-onboarding-input peer text-sm">
                    <label for="new_plan_speed_profile" class="pb-onboarding-label text-[10px]">MikroTik profile</label>
                </div>
                <div class="relative">
                    <input type="number" step="0.01" name="new_plan_price" id="new_plan_price" value="{{ old('new_plan_price', '0') }}" min="0" placeholder=" " class="pb-onboarding-input peer text-sm">
                    <label for="new_plan_price" class="pb-onboarding-label text-[10px]">Retail price (KES)</label>
                </div>
            </div>
            @error('new_plan_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            @error('new_plan_speed_profile')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" formnovalidate name="wizard_action" value="go_back" class="pb-btn px-6">Back</button>
                <button type="submit" formnovalidate name="wizard_action" value="skip_router" class="text-sm font-medium text-slate-500 underline-offset-2 hover:text-slate-700 hover:underline dark:text-slate-400 dark:hover:text-slate-200">
                    Skip for now
                </button>
            </div>
            <button type="submit" class="pb-btn pb-btn-primary px-8">Continue</button>
        </div>
    </div>
@endif

@if ($current === 4)
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-8 space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Customer M-Pesa</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="relative">
                <input type="text" name="mpesa_shortcode" id="mpesa_shortcode" value="{{ old('mpesa_shortcode') }}" required autocomplete="off" placeholder=" " class="pb-onboarding-input peer">
                <label for="mpesa_shortcode" class="pb-onboarding-label">Shortcode</label>
                @error('mpesa_shortcode')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="mpesa_passkey" id="mpesa_passkey" value="{{ old('mpesa_passkey') }}" required autocomplete="off" placeholder=" " class="pb-onboarding-input peer">
                <label for="mpesa_passkey" class="pb-onboarding-label">Passkey</label>
                @error('mpesa_passkey')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative sm:col-span-2">
                <input type="text" name="mpesa_consumer_key" id="mpesa_consumer_key" value="{{ old('mpesa_consumer_key') }}" required autocomplete="off" placeholder=" " class="pb-onboarding-input peer">
                <label for="mpesa_consumer_key" class="pb-onboarding-label">Consumer key</label>
                @error('mpesa_consumer_key')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="relative sm:col-span-2">
                <input type="password" name="mpesa_consumer_secret" id="mpesa_consumer_secret" value="{{ old('mpesa_consumer_secret') }}" required autocomplete="new-password" placeholder=" " class="pb-onboarding-input peer">
                <label for="mpesa_consumer_secret" class="pb-onboarding-label">Consumer secret</label>
                @error('mpesa_consumer_secret')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="mpesa_environment" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Environment</label>
                <select name="mpesa_environment" id="mpesa_environment" class="pb-onboarding-input !py-2.5">
                    <option value="sandbox" {{ old('mpesa_environment', config('services.mpesa.environment', 'sandbox')) === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                    <option value="production" {{ old('mpesa_environment') === 'production' ? 'selected' : '' }}>Production</option>
                </select>
                @error('mpesa_environment')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-200">
                    <input type="checkbox" name="mpesa_active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-600" {{ old('mpesa_active', true) ? 'checked' : '' }}>
                    Active
                </label>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" formnovalidate name="wizard_action" value="go_back" class="pb-btn px-6">Back</button>
                <button type="submit" formnovalidate name="wizard_action" value="skip_mpesa_and_finish" data-turbo-frame="_top" class="text-sm font-medium text-slate-500 underline-offset-2 hover:text-slate-700 hover:underline dark:text-slate-400 dark:hover:text-slate-200">
                    Finish without M-Pesa
                </button>
            </div>
            <button type="submit" data-turbo-frame="_top" class="pb-btn pb-btn-primary px-8">Finish setup</button>
        </div>
    </div>
@endif

@push('scripts')
<script>
function initSaasOnboardingWizardForm() {
    function syncPanels() {
        var checked = document.querySelector('input[name="saas_billing_method"]:checked');
        var v = checked ? checked.value : 'mpesa';
        document.querySelectorAll('[data-billing-panel]').forEach(function (el) {
            var on = el.getAttribute('data-billing-panel') === v;
            el.classList.toggle('hidden', !on);
        });
        syncSubmitLabel();
    }

    function syncSubmitLabel() {
        var btn = document.getElementById('pb-onboarding-step2-submit');
        if (!btn) return;
        var method = document.querySelector('input[name="saas_billing_method"]:checked');
        btn.textContent = method && method.value === 'mpesa'
            ? btn.getAttribute('data-label-mpesa') || 'Verify M-Pesa'
            : btn.getAttribute('data-label-default') || 'Continue';
    }

    function initOnboardingMpesaPoll(form) {
        if (!form || form.dataset.onboardingStep !== '2') return;

        var overlay = document.getElementById('pb-mpesa-stk-overlay');
        var overlayText = document.getElementById('pb-mpesa-stk-overlay-text');
        var overlaySpinner = document.getElementById('pb-mpesa-stk-overlay-spinner');
        var overlayCancel = document.getElementById('pb-mpesa-stk-overlay-cancel');
        var pollTimer = null;
        var pollAborted = false;

        function showOverlay(text) {
            if (!overlay) return;
            pollAborted = false;
            if (overlayText) overlayText.textContent = text;
            if (overlaySpinner) overlaySpinner.classList.remove('hidden');
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        }

        function hideOverlay() {
            pollAborted = true;
            if (!overlay) return;
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        }

        if (overlayCancel) {
            overlayCancel.addEventListener('click', function () {
                hideOverlay();
            });
        }

        function showErrorToast(message) {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Payment not completed',
                    text: message,
                    confirmButtonColor: '#4f46e5',
                    confirmButtonText: 'Try again',
                });
            } else {
                window.alert(message);
            }
        }

        function visitWizardFrame(url) {
            var frame = document.getElementById('pb-saas-onboarding-frame');
            if (frame) {
                frame.src = url;
            } else if (window.Turbo) {
                window.Turbo.visit(url);
            } else {
                window.location.assign(url);
            }
        }

        function redirectSuccess(url) {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Payment verified',
                    text: 'Continuing to network setup…',
                    timer: 1800,
                    showConfirmButton: false,
                    timerProgressBar: true,
                }).then(function () {
                    visitWizardFrame(url);
                });
            } else {
                visitWizardFrame(url);
            }
        }

        function startPolling(paymentId) {
            var statusBase = form.dataset.paymentStatusUrl;
            var attempts = 0;
            var maxAttempts = 60;

            showOverlay('Enter your M-Pesa PIN on your phone.');

            pollTimer = setInterval(function () {
                if (pollAborted) return;
                attempts += 1;
                fetch(statusBase + '/' + paymentId, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then(function (res) {
                        if (!res.ok) return null;
                        return res.json();
                    })
                    .then(function (data) {
                        if (!data || pollAborted) return;
                        if (data.status === 'paid') {
                            hideOverlay();
                            redirectSuccess(data.redirect || window.location.href);
                        } else if (data.status === 'cancelled' || data.status === 'failed') {
                            hideOverlay();
                            showErrorToast('Transaction was cancelled or timed out. Please try again.');
                        }
                    })
                    .catch(function () {});

                if (attempts >= maxAttempts) {
                    hideOverlay();
                    showErrorToast('Transaction was cancelled or timed out. Please try again.');
                }
            }, 2500);
        }

        form.addEventListener('submit', function (e) {
            if (form.dataset.onboardingStep !== '2') return;

            if (form.dataset.mpesaAsync !== '1') return;

            var method = document.querySelector('input[name="saas_billing_method"]:checked');
            if (!method || method.value !== 'mpesa') return;

            var action = e.submitter && e.submitter.name === 'wizard_action' ? e.submitter.value : null;
            if (action === 'go_back' || action === 'local_dev_skip_mpesa_verify') return;

            e.preventDefault();
            showOverlay('Sending payment request…');

            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, status: res.status, data: data };
                    }).catch(function () {
                        return { ok: res.ok, status: res.status, data: {} };
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        hideOverlay();
                        var msg = result.data.message
                            || (result.data.errors && Object.values(result.data.errors).flat()[0])
                            || 'Could not start M-Pesa verification.';
                        showErrorToast(msg);
                        return;
                    }

                    if (result.data.status === 'paid' || (result.data.success && !result.data.poll)) {
                        hideOverlay();
                        redirectSuccess(result.data.redirect || window.location.href);
                        return;
                    }

                    if (result.data.poll && result.data.payment_id) {
                        startPolling(result.data.payment_id);
                        return;
                    }

                    hideOverlay();
                })
                .catch(function () {
                    hideOverlay();
                    showErrorToast('Network error. Please try again.');
                });
        });
    }

    document.querySelectorAll('input[name="saas_billing_method"]').forEach(function (r) {
        r.addEventListener('change', syncPanels);
    });
    syncPanels();

    var form = document.getElementById('pb-saas-onboarding-form');
    if (!form || !form.dataset.draftIsp || form.dataset.wizardHandlersBound === '1') {
        return;
    }

    form.dataset.wizardHandlersBound = '1';
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
            syncPanels();
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
            if (!el.name || el.name === '_token' || el.name === 'wizard_step') {
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
                        if (name === 'wizard_step' || name === '_token') {
                            return;
                        }
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

    initOnboardingMpesaPoll(form);
}

document.addEventListener('turbo:frame-render', function (event) {
    if (event.target && ['pb-saas-onboarding-frame', 'pb-saas-onboarding-page-frame'].includes(event.target.id)) {
        initSaasOnboardingWizardForm();
    }
});

document.addEventListener('turbo:load', initSaasOnboardingWizardForm);
initSaasOnboardingWizardForm();
</script>
@endpush
