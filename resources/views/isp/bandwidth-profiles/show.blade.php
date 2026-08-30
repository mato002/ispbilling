@extends('layouts.admin')

@section('title', $bandwidthProfile->name)

@section('header')
    <x-isp-page-header
        :title="$bandwidthProfile->name"
        module="Network"
        :description="$bandwidthProfile->formatted_download_speed.' ↓ / '.$bandwidthProfile->formatted_upload_speed.' ↑ · '.$bandwidthProfile->queue_type"
        primaryLabel="Edit profile"
        :primaryHref="route('isp.bandwidth-profiles.edit', $bandwidthProfile)"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.bandwidth-profiles.index') }}" class="pb-btn">All profiles</a>
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
                        @if ($bandwidthProfile->is_active)
                            <x-status-badge status="active" />
                        @else
                            <x-status-badge status="inactive" />
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Queue type</dt>
                    <dd><span class="pb-pill pb-pill-info">{{ $bandwidthProfile->queue_type }}</span></dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Priority</dt>
                    <dd class="tabular-nums text-sm text-slate-800 dark:text-slate-100">{{ $bandwidthProfile->priority }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Download</dt>
                    <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ $bandwidthProfile->formatted_download_speed }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Upload</dt>
                    <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ $bandwidthProfile->formatted_upload_speed }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Parent profile</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $bandwidthProfile->parentProfile?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Download burst</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">
                        {{ $bandwidthProfile->download_burst_limit ? number_format($bandwidthProfile->download_burst_limit / 1000, 1).' Mbps' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Upload burst</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">
                        {{ $bandwidthProfile->upload_burst_limit ? number_format($bandwidthProfile->upload_burst_limit / 1000, 1).' Mbps' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Burst time</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">
                        @if ($bandwidthProfile->download_burst_time || $bandwidthProfile->upload_burst_time)
                            {{ $bandwidthProfile->download_burst_time ?? '—' }}s / {{ $bandwidthProfile->upload_burst_time ?? '—' }}s
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="isp-form-label mb-1">Comment</dt>
                    <dd class="text-sm text-slate-700 dark:text-slate-300">{{ $bandwidthProfile->comment ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        @if ($bandwidthProfile->childProfiles->isNotEmpty())
            <div class="pb-card p-6">
                <h3 class="isp-settings-section-title mb-3">Child profiles</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($bandwidthProfile->childProfiles as $child)
                        <li>
                            <a href="{{ route('isp.bandwidth-profiles.show', $child) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">
                                {{ $child->name }}
                            </a>
                            <span class="text-slate-500">· {{ $child->formatted_download_speed }} / {{ $child->formatted_upload_speed }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($bandwidthProfile->plans->isNotEmpty())
            <div class="pb-card p-6">
                <h3 class="isp-settings-section-title mb-3">Used by plans</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($bandwidthProfile->plans as $plan)
                        <li class="text-slate-800 dark:text-slate-100">{{ $plan->name }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="pb-card p-6">
            <h3 class="isp-settings-section-title mb-2">Deploy to router</h3>
<form method="POST" action="{{ route('isp.bandwidth-profiles.deploy', $bandwidthProfile) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-[220px] flex-1">
                    <label class="isp-form-label" for="router_id">Router</label>
                    <select id="router_id" name="router_id" class="isp-form-select" required>
                        <option value="">Select router</option>
                        @foreach ($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->ip_address }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="pb-btn pb-btn-primary">Deploy</button>
            </form>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($bandwidthProfile->is_active)
                <form method="POST" action="{{ route('isp.bandwidth-profiles.deactivate', $bandwidthProfile) }}" onsubmit="return confirm('Deactivate this profile?');">
                    @csrf
                    <button type="submit" class="pb-btn">Deactivate</button>
                </form>
            @else
                <form method="POST" action="{{ route('isp.bandwidth-profiles.activate', $bandwidthProfile) }}">
                    @csrf
                    <button type="submit" class="pb-btn pb-btn-primary">Activate</button>
                </form>
            @endif
            <form method="POST" action="{{ route('isp.bandwidth-profiles.destroy', $bandwidthProfile) }}" onsubmit="return confirm('Delete this bandwidth profile?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="pb-btn text-rose-600">Delete</button>
            </form>
        </div>
    </div>
@endsection
