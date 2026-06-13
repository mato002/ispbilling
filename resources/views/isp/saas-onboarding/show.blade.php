@extends('layouts.admin')

@section('title', 'Workspace setup')

@section('header')
    <div class="text-center sm:text-left">
        @if (auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.isps.show', $isp) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                ← Back to {{ $isp->name }} in admin
            </a>
        @endif
        <span class="isp-page-kicker {{ auth()->user()->isSuperAdmin() ? 'mt-2' : '' }}">PrimeBilling subscription</span>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Set up your workspace</h1>
        <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500 dark:text-slate-400 sm:mx-0">
            Four quick steps to activate billing, connect your network, and start collecting payments.
        </p>
    </div>
@endsection

@section('content')
    @if (! auth()->user()->isIspAdmin() && ! auth()->user()->isSuperAdmin())
        <div class="rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50 to-orange-50 p-6 text-sm text-amber-950 shadow-sm dark:border-amber-500/30 dark:from-amber-950/40 dark:to-orange-950/30 dark:text-amber-100">
            <p class="font-semibold">Waiting on your ISP admin</p>
            <p class="mt-1 text-amber-800/90 dark:text-amber-100/90">Ask your ISP admin to finish workspace setup before you can access the dashboard.</p>
        </div>
    @else
        @php
            $onboardingStep = min(4, max(1, (int) ($isp->saas_onboarding_step ?? 1)));
            $mpesaAsyncOnboarding = $onboardingStep === 2
                && ($isp->saas_billing_method ?? 'mpesa') === 'mpesa'
                && ! $isp->saas_payment_verified_at;
        @endphp
        <form
            id="pb-saas-onboarding-form"
            method="POST"
            action="{{ route('isp.saas-onboarding.store') }}"
            class="space-y-6"
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
                'verifyAmount' => $verifyAmount ?? 1,
            ])
        </form>
    @endif
@endsection
