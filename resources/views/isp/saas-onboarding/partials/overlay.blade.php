@php
    /** @var \App\Models\ISP $isp */
    $isp = $pbSaasOnboardingIsp;
    $plans = $pbSaasOnboardingPlans;
    $verifyAmount = $pbSaasOnboardingVerifyAmount;
    $onboardingStep = min(4, max(1, (int) ($isp->saas_onboarding_step ?? 1)));
    $trialDeferred = $isp->pendingPlanDefersOnboardingPayment();
    $mpesaAsyncOnboarding = $onboardingStep === 2
        && ($isp->saas_billing_method ?? 'mpesa') === 'mpesa'
        && ! $isp->saas_payment_verified_at
        && ! $trialDeferred;
@endphp

<div
    id="pb-saas-onboarding-gate"
    class="pb-saas-onboarding-gate"
    role="dialog"
    aria-modal="true"
    aria-labelledby="pb-saas-onboarding-title"
>
    <div class="pb-saas-onboarding-gate__backdrop" aria-hidden="true"></div>

    <div class="pb-saas-onboarding-gate__scroll">
        <div class="pb-saas-onboarding-gate__panel">
            <header class="pb-saas-onboarding-gate__header">
                <p class="isp-page-kicker">PrimeBilling subscription</p>
                <h1 id="pb-saas-onboarding-title" class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                    Set up your workspace
                </h1>
                <p class="mt-2 max-w-xl text-sm text-slate-500 dark:text-slate-400">
                    Complete these steps to unlock your dashboard.
                </p>
            </header>

            @if (session('success'))
                <div class="mt-4 rounded-xl border border-emerald-200/80 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-100">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('info'))
                <div class="mt-4 rounded-xl border border-indigo-200/80 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-500/30 dark:bg-indigo-950/40 dark:text-indigo-100">
                    {{ session('info') }}
                </div>
            @endif
            @if (session('warning'))
                <div class="mt-4 rounded-xl border border-amber-200/80 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100">
                    {{ session('warning') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mt-4 rounded-xl border border-rose-200/80 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-500/30 dark:bg-rose-950/40 dark:text-rose-100">
                    {{ session('error') }}
                </div>
            @endif

            <form
                id="pb-saas-onboarding-form"
                method="POST"
                action="{{ route('isp.saas-onboarding.store') }}"
                class="mt-6 space-y-6"
                data-turbo="false"
                data-draft-isp="{{ $isp->id }}"
                data-draft-step="{{ $onboardingStep }}"
                data-onboarding-step="{{ $onboardingStep }}"
                data-mpesa-async="{{ $mpesaAsyncOnboarding ? '1' : '0' }}"
                data-payment-status-url="{{ url('/api/onboarding/payment-status') }}"
                data-draft-skip-restore="{{ $errors->any() ? '1' : '0' }}"
            >
                @csrf
                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 shadow-sm dark:border-rose-500/30 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                        <p class="font-semibold">Please fix the following:</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @include('isp.saas-onboarding.partials.onboarding-form-body', [
                    'isp' => $isp,
                    'plans' => $plans,
                    'verifyAmount' => $verifyAmount,
                ])
            </form>
        </div>
    </div>
</div>
