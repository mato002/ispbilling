@extends('layouts.admin')

@section('title', 'Plans')

@section('header')
    <x-isp-page-header
        title="Plans &amp; packages"
        module="Catalog"
        description="Sellable packages with access type, speeds, and pricing for hotspot and PPPoE."
        primaryLabel="Create plan"
        :primaryHref="route('isp.plans.create')"
    />
@endsection

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <x-filter-bar :action="route('isp.plans.index')">
        <div class="min-w-[140px]">
            <label class="isp-form-label">Access type</label>
            <select name="access_type" class="isp-form-select">
                <option value="">All</option>
                <option value="pppoe" @selected(request('access_type') === 'pppoe')>PPPoE</option>
                <option value="hotspot" @selected(request('access_type') === 'hotspot')>Hotspot</option>
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="isp-form-label">Status</label>
            <select name="is_active" class="isp-form-select">
                <option value="">All</option>
                <option value="1" @selected(request('is_active') === '1')>Active</option>
                <option value="0" @selected(request('is_active') === '0')>Inactive</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.plans.index') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($plans->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state
                title="No plans found"
                message="Create a plan to attach subscriptions and vouchers."
                ctaLabel="Create plan"
                :ctaHref="route('isp.plans.create')"
            />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Speed</th>
                    <th>Subs</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                    <tr>
                        <td class="max-w-[220px]">
                            <div class="font-semibold text-slate-900 dark:text-white">{{ $plan->name }}</div>
                            <div class="truncate text-slate-500 dark:text-slate-400 text-sm">{{ $plan->description ?: '—' }}</div>
                        </td>
                        <td>
                            @if($plan->access_type === 'hotspot')
                                <span class="pb-pill pb-pill-cyan">Hotspot</span>
                            @else
                                <span class="pb-pill pb-pill-info">PPPoE</span>
                            @endif
                        </td>
                        <td class="font-mono whitespace-nowrap">KES {{ number_format($plan->price, 2) }}</td>
                        <td>{{ $plan->duration_days }} days</td>
                        <td class="max-w-[160px] truncate text-sm">{{ $plan->speed_profile ?? ($plan->download_speed_kbps ? $plan->formatted_speed : '—') }}</td>
                        <td class="tabular-nums">{{ $plan->subscriptions_count }}</td>
                        <td>
                            @if($plan->is_active)
                                <x-status-badge status="active" />
                            @else
                                <x-status-badge status="inactive" />
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('isp.plans.edit', $plan) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Edit</a>
                            <a href="{{ route('isp.plans.show', $plan) }}" class="ml-3 text-sm font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-300">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $plans->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
