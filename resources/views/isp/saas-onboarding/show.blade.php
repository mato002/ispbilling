@extends('layouts.admin')

@section('title', 'PrimeBilling subscription setup')

@section('header')
    <x-isp-page-header
        title="Workspace setup"
        module="SaaS onboarding"
        description="Four quick steps."
    />
@endsection

@section('content')
<div class="mx-auto max-w-3xl space-y-8">
    @if (! auth()->user()->isIspAdmin())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100">
            Ask your ISP admin to finish setup first.
        </div>
    @else
        @php
            $onboardingStep = min(4, max(1, (int) ($isp->saas_onboarding_step ?? 1)));
        @endphp
        <form
            id="pb-saas-onboarding-form"
            method="POST"
            action="{{ route('isp.saas-onboarding.store') }}"
            class="space-y-8"
            data-turbo="false"
            data-draft-isp="{{ $isp->id }}"
            data-draft-step="{{ $onboardingStep }}"
            data-draft-skip-restore="{{ $errors->any() ? '1' : '0' }}"
        >
            @csrf
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-500/30 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                    <p class="font-medium">Fix the following, then try again:</p>
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
</div>
@endsection
