@extends('layouts.admin')

@section('title', $hotspotServer->name)

@section('header')
    <x-isp-page-header
        :title="$hotspotServer->name"
        module="Network"
        :description="'Hotspot on '.$hotspotServer->router?->name.' · '.$hotspotServer->interface"
        primaryLabel="Edit server"
        :primaryHref="route('isp.hotspot-servers.edit', $hotspotServer)"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.hotspot-servers.index') }}" class="pb-btn">All servers</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="pb-card p-6">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="isp-form-label mb-1">Status</dt>
                    <dd>
                        @if ($hotspotServer->is_active)
                            <x-status-badge status="active" />
                        @else
                            <x-status-badge status="inactive" />
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Router</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">
                        {{ $hotspotServer->router?->name ?? '—' }}
                        @if ($hotspotServer->router)
                            <span class="ml-1 font-mono text-xs text-slate-500">{{ $hotspotServer->router->ip_address }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Interface</dt>
                    <dd class="font-mono text-sm text-slate-800 dark:text-slate-100">{{ $hotspotServer->interface }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Login by</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ ucfirst($hotspotServer->login_by) }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Default profile</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $hotspotServer->default_profile ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">RADIUS</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">
                        {{ $hotspotServer->use_radius ? 'Enabled' : 'Disabled' }}
                        @if ($hotspotServer->radius_accounting)
                            <span class="ml-1 pb-pill pb-pill-info text-[10px]">Accounting</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Idle timeout</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $hotspotServer->idle_timeout }}s</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Keepalive</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $hotspotServer->keepalive_timeout }}s</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">MAC cookie timeout</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $hotspotServer->mac_cookie_timeout }}s</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Allowed users</dt>
                    <dd class="tabular-nums text-sm text-slate-800 dark:text-slate-100">{{ $hotspotServer->allowed_users ?: 'Unlimited' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Shared users</dt>
                    <dd class="tabular-nums text-sm text-slate-800 dark:text-slate-100">{{ $hotspotServer->shared_users ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Trial profile</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $hotspotServer->trial_profile ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($hotspotServer->is_active)
                <form method="POST" action="{{ route('isp.hotspot-servers.deactivate', $hotspotServer) }}" onsubmit="return confirm('Deactivate this server?');">
                    @csrf
                    <button type="submit" class="pb-btn">Deactivate</button>
                </form>
            @else
                <form method="POST" action="{{ route('isp.hotspot-servers.activate', $hotspotServer) }}">
                    @csrf
                    <button type="submit" class="pb-btn pb-btn-primary">Activate</button>
                </form>
            @endif
            <form method="POST" action="{{ route('isp.hotspot-servers.destroy', $hotspotServer) }}" onsubmit="return confirm('Delete this hotspot server record?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="pb-btn text-rose-600">Delete</button>
            </form>
        </div>
    </div>
@endsection
