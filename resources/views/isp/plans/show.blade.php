@extends('layouts.admin')

@section('title', 'Plan Details')

@section('header')
    <x-isp-page-header
        :title="$plan->name"
        module="Catalog"
        :description="$plan->description ?: 'Service plan details and availability.'"
        primaryLabel="Edit"
        :primaryHref="route('isp.plans.edit', $plan->id)"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.plans.index') }}" class="pb-btn">Back to list</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-form-section title="Plan">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    @if($plan->is_active)
                        <x-status-badge status="active" />
                    @else
                        <x-status-badge status="inactive" />
                    @endif
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt class="isp-form-label mb-1">Price</dt>
                        <dd class="text-2xl font-semibold text-slate-900 dark:text-white">KES {{ number_format($plan->price, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Duration</dt>
                        <dd class="text-sm font-medium text-slate-900 dark:text-white">{{ $plan->duration_days }} days</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Access type</dt>
                        <dd>
                            <span class="pb-pill pb-pill-info">{{ strtoupper($plan->access_type) }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Billing type</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ ucfirst($plan->billing_type) }}</dd>
                    </div>
                    @if($plan->download_speed_kbps)
                        <div>
                            <dt class="isp-form-label mb-1">Speed</dt>
                            <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $plan->formatted_speed }}</dd>
                        </div>
                    @endif
                    @if($plan->data_limit_mb)
                        <div>
                            <dt class="isp-form-label mb-1">Data limit</dt>
                            <dd class="text-sm text-slate-800 dark:text-slate-100">{{ number_format($plan->data_limit_mb) }} MB</dd>
                        </div>
                    @endif
                    @if($plan->time_limit_hours)
                        <div>
                            <dt class="isp-form-label mb-1">Time limit</dt>
                            <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $plan->time_limit_hours }} hours</dd>
                        </div>
                    @endif
                    @if($plan->speed_profile)
                        <div>
                            <dt class="isp-form-label mb-1">Speed profile name</dt>
                            <dd class="font-mono text-sm text-slate-800 dark:text-slate-100">{{ $plan->speed_profile }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="isp-form-label mb-1">Active subscriptions</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $plan->subscriptions_count }}</dd>
                    </div>
                </dl>
            </x-form-section>
        </div>

        <div class="space-y-6">
            <x-form-section title="Quick actions">
                <div class="flex flex-col gap-2">
                    <a href="{{ route('isp.plans.edit', $plan->id) }}" class="pb-btn pb-btn-primary w-full justify-center">Edit plan</a>
                    @if($plan->is_active)
                        <form method="POST" action="{{ route('isp.plans.deactivate', $plan->id) }}" class="w-full" onsubmit="confirmFormSubmit(event, {title: 'Deactivate Plan', text: 'Are you sure you want to deactivate {{ $plan->name }}? Existing subscriptions will not be affected.', icon: 'warning', confirmButtonText: 'Yes, deactivate'}); return false;">
                            @csrf
                            <button type="submit" class="pb-btn w-full justify-center border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">Deactivate</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('isp.plans.activate', $plan->id) }}" class="w-full" onsubmit="confirmFormSubmit(event, {title: 'Activate Plan', text: 'Are you sure you want to activate {{ $plan->name }}?', icon: 'question', confirmButtonText: 'Yes, activate'}); return false;">
                            @csrf
                            <button type="submit" class="pb-btn w-full justify-center border-emerald-200 bg-emerald-50 text-emerald-900 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">Activate</button>
                        </form>
                    @endif
                </div>
            </x-form-section>
        </div>
    </div>
@endsection
