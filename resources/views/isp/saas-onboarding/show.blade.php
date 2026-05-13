@extends('layouts.admin')

@section('title', 'PrimeBilling subscription setup')

@section('header')
    <x-isp-page-header
        title="Choose your PrimeBilling plan"
        module="SaaS onboarding"
        description="Select how you use the platform, confirm billing for your organisation, and optionally add white-label setup."
    />
@endsection

@section('content')
<div class="mx-auto max-w-3xl space-y-8">
    @if (! auth()->user()->isIspAdmin())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100">
            Your organisation’s administrator must complete PrimeBilling subscription setup before the console unlocks for your team.
        </div>
    @else
        <form method="POST" action="{{ route('isp.saas-onboarding.store') }}" class="space-y-8">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">1. Access plan</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Includes a free trial before your first platform invoice.</p>
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
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Used on PrimeBilling invoices and for automated M-Pesa STK on billing day.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="isp-form-label">Company / trading name</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $isp->company_name) }}" required class="isp-form-input">
                        @error('company_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="isp-form-label">KRA PIN (optional)</label>
                        <input type="text" name="saas_company_pin" value="{{ old('saas_company_pin') }}" maxlength="32" class="isp-form-input" placeholder="P051234567X">
                        @error('saas_company_pin')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="isp-form-label">M-Pesa billing number</label>
                        <input type="text" name="saas_billing_phone" value="{{ old('saas_billing_phone', $isp->phone) }}" required class="isp-form-input" placeholder="2547XXXXXXXX">
                        @error('saas_billing_phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">3. Optional white-label setup</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">If enabled, the plan’s one-time setup fee is added to your first platform invoice after the trial.</p>
                <label class="mt-4 flex items-start gap-3 text-sm text-slate-700 dark:text-slate-200">
                    <input type="checkbox" name="saas_white_label_opt_in" value="1" class="mt-1 rounded border-slate-300" {{ old('saas_white_label_opt_in') ? 'checked' : '' }}>
                    <span>Include white-label setup (logo & workspace styling) — billed once on first invoice.</span>
                </label>
            </div>

            <div class="flex justify-end gap-3">
                <button type="submit" class="pb-btn pb-btn-primary px-8">Continue to dashboard</button>
            </div>
        </form>
    @endif
</div>
@endsection
