@extends('layouts.admin')

@section('title', $ipPool->name)

@section('header')
    <x-isp-page-header
        :title="$ipPool->name"
        module="Network"
        :description="'IP pool on '.$ipPool->router?->name"
        primaryLabel="Edit pool"
        :primaryHref="route('isp.ip-pools.edit', $ipPool)"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.ip-pools.index') }}" class="pb-btn">All pools</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="pb-card p-6">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="isp-form-label mb-1">Status</dt>
                    <dd>
                        @if ($ipPool->is_active)
                            <x-status-badge status="active" />
                        @else
                            <x-status-badge status="inactive" />
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Router</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">
                        {{ $ipPool->router?->name ?? '—' }}
                        @if ($ipPool->router)
                            <span class="ml-1 font-mono text-xs text-slate-500">{{ $ipPool->router->ip_address }}</span>
                        @endif
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="isp-form-label mb-1">Ranges</dt>
                    <dd class="font-mono text-sm text-slate-800 dark:text-slate-100">{{ $ipPool->formatted_range }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Total IPs</dt>
                    <dd class="tabular-nums text-sm text-slate-800 dark:text-slate-100">{{ $ipPool->total_ips }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Lease time</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $ipPool->lease_time ? $ipPool->lease_time.'s' : '—' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Gateway</dt>
                    <dd class="font-mono text-sm text-slate-800 dark:text-slate-100">{{ $ipPool->gateway_address ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Netmask</dt>
                    <dd class="font-mono text-sm text-slate-800 dark:text-slate-100">{{ $ipPool->netmask ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="isp-form-label mb-1">Comment</dt>
                    <dd class="text-sm text-slate-700 dark:text-slate-300">{{ $ipPool->comment ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($ipPool->is_active)
                <form method="POST" action="{{ route('isp.ip-pools.deactivate', $ipPool) }}" onsubmit="return confirm('Deactivate this pool?');">
                    @csrf
                    <button type="submit" class="pb-btn">Deactivate</button>
                </form>
            @else
                <form method="POST" action="{{ route('isp.ip-pools.activate', $ipPool) }}">
                    @csrf
                    <button type="submit" class="pb-btn pb-btn-primary">Activate</button>
                </form>
            @endif
            <form method="POST" action="{{ route('isp.ip-pools.destroy', $ipPool) }}" onsubmit="return confirm('Delete this IP pool permanently?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="pb-btn text-rose-600">Delete</button>
            </form>
        </div>
    </div>
@endsection
