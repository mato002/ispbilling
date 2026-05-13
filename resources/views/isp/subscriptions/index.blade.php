@extends('layouts.admin')

@section('title', 'Subscriptions')

@section('header')
    <x-isp-page-header
        title="Subscriptions"
        module="Billing &amp; plans"
        description="Active and historical subscriber plans, renewal windows, and billing alignment."
        primaryLabel="Create subscription"
        :primaryHref="route('isp.subscriptions.create')"
    />
@endsection

@section('content')
<div class="space-y-6">
    <x-filter-bar :action="route('isp.subscriptions.index')">
        <div class="min-w-[200px] flex-1">
            <label class="isp-form-label">Search</label>
            <input type="search" name="search" value="{{ request('search') }}" class="isp-form-input" placeholder="Customer, phone, username, plan…">
        </div>
        <div class="min-w-[140px]">
            <label class="isp-form-label">Status</label>
            <select name="status" class="isp-form-select">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
            </select>
        </div>
        <div class="min-w-[180px] flex-1">
            <label class="isp-form-label">Plan</label>
            <select name="plan_id" class="isp-form-select">
                <option value="">All plans</option>
                @foreach($plans as $p)
                    <option value="{{ $p->id }}" @selected((string) request('plan_id') === (string) $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.subscriptions.index') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($subscriptions->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state
                title="No subscriptions match"
                message="Adjust filters or create a subscription for a customer."
                ctaLabel="Create subscription"
                :ctaHref="route('isp.subscriptions.create')"
            />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Plan</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Auto renew</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subscriptions as $subscription)
                    <tr>
                        <td>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ $subscription->customer->full_name }}</div>
                            <div class="text-slate-500 dark:text-slate-400">{{ $subscription->customer->phone_number }}</div>
                        </td>
                        <td>{{ $subscription->plan->name }}</td>
                        <td class="whitespace-nowrap">{{ $subscription->start_date->format('M j, Y') }}</td>
                        <td class="whitespace-nowrap">
                            {{ $subscription->end_date->format('M j, Y') }}
                            @if($subscription->end_date < now() && $subscription->status === 'active')
                                <span class="ml-1 text-xs font-semibold text-rose-600 dark:text-rose-400">(Expired)</span>
                            @endif
                        </td>
                        <td>
                            @if($subscription->auto_renew)
                                <span class="text-emerald-600 dark:text-emerald-400 font-medium">Yes</span>
                            @else
                                <span class="text-slate-400">No</span>
                            @endif
                        </td>
                        <td>
                            @if($subscription->status === 'active')
                                <x-status-badge status="active" />
                            @elseif($subscription->status === 'expired')
                                <x-status-badge status="expired" />
                            @else
                                <x-status-badge :status="ucfirst($subscription->status)" />
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('isp.subscriptions.show', $subscription) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View</a>
                            @if($subscription->status === 'active' || $subscription->status === 'expired')
                                <form method="POST" action="{{ route('isp.subscriptions.renew', $subscription) }}" class="inline ml-3" onsubmit="confirmFormSubmit(event, {title: 'Renew subscription', text: 'Renew this subscription for {{ $subscription->customer->full_name }}?', icon: 'question', confirmButtonText: 'Yes, renew'}); return false;">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Renew</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $subscriptions->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
