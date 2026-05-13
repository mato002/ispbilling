@extends('layouts.admin')

@section('title', 'Subscription Details')

@section('header')
    <x-isp-page-header
        title="Subscription #{{ $subscription->id }}"
        module="Billing"
        :description="$subscription->plan->name . ' · ' . $subscription->customer->full_name"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.subscriptions.index') }}" class="pb-btn">Back to list</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-form-section title="Subscription">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $subscription->plan->name }}</p>
                    </div>
                    <div>
                        @if($subscription->status === 'active')
                            <x-status-badge status="active" />
                        @elseif($subscription->status === 'expired')
                            <x-status-badge status="expired" />
                        @else
                            <x-status-badge :status="ucfirst($subscription->status)" />
                        @endif
                    </div>
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt class="isp-form-label mb-1">Customer</dt>
                        <dd class="text-lg font-semibold text-slate-900 dark:text-white">{{ $subscription->customer->full_name }}</dd>
                        <dd class="text-sm text-slate-500 dark:text-slate-400">{{ $subscription->customer->phone_number }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Plan</dt>
                        <dd class="text-sm font-medium text-slate-900 dark:text-white">{{ $subscription->plan->name }}</dd>
                        <dd class="text-sm text-slate-500 dark:text-slate-400">KES {{ number_format($subscription->plan->price, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Start date</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $subscription->start_date->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">End date</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">
                            {{ $subscription->end_date->format('M d, Y') }}
                            @if($subscription->end_date < now() && $subscription->status === 'active')
                                <span class="text-xs text-red-600 dark:text-red-400">(Expired)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Renewal date</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">
                            {{ $subscription->renewal_date ? $subscription->renewal_date->format('M d, Y') : 'N/A' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Auto renew</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $subscription->auto_renew ? 'Yes' : 'No' }}</dd>
                    </div>
                </dl>
            </x-form-section>
        </div>

        <div class="space-y-6">
            <x-form-section title="Quick actions">
                <div class="flex flex-col gap-2">
                    @if($subscription->status === 'active' || $subscription->status === 'expired')
                        <form method="POST" action="{{ route('isp.subscriptions.renew', $subscription->id) }}" class="w-full" onsubmit="confirmFormSubmit(event, {title: 'Renew Subscription', text: 'Are you sure you want to renew this subscription?', icon: 'question', confirmButtonText: 'Yes, renew'}); return false;">
                            @csrf
                            <button type="submit" class="pb-btn w-full justify-center border-emerald-200 bg-emerald-50 text-emerald-900 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">Renew subscription</button>
                        </form>
                    @endif
                    @if($subscription->status === 'active')
                        <form method="POST" action="{{ route('isp.subscriptions.cancel', $subscription->id) }}" class="w-full" onsubmit="confirmFormSubmit(event, {title: 'Cancel Subscription', text: 'Are you sure you want to cancel this subscription? This action cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, cancel', confirmButtonColor: '#ef4444'}); return false;">
                            @csrf
                            <button type="submit" class="pb-btn w-full justify-center border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">Cancel subscription</button>
                        </form>
                    @endif
                    <a href="{{ route('isp.customers.show', $subscription->customer_id) }}" class="pb-btn pb-btn-primary w-full justify-center">View customer</a>
                    <a href="{{ route('isp.plans.show', $subscription->plan_id) }}" class="pb-btn w-full justify-center">View plan</a>
                </div>
            </x-form-section>
        </div>
    </div>
@endsection
