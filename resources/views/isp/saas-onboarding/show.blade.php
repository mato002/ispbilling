@extends('layouts.admin')

@section('title', 'PrimeBilling subscription setup')

@section('header')
    <x-isp-page-header
        title="Set up your PrimeBilling workspace"
        module="SaaS onboarding"
        description="Plan, business profile, MikroTik connection, customer M-Pesa, and billing plans — step by step until your console is live."
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
            @include('isp.saas-onboarding.partials.onboarding-form-body', [
                'isp' => $isp,
                'plans' => $plans,
                'verifyAmount' => $verifyAmount ?? 1,
            ])
        </form>
    @endif
</div>
@endsection
