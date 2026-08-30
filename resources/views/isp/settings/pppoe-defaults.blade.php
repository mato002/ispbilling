@extends('layouts.admin')

@section('title', 'PPPoE defaults · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php $p = $isp->settings['network']['pppoe_defaults'] ?? []; @endphp

    <x-isp-settings-workspace
        meta-title="PPPoE defaults"
>
        <form method="POST" action="{{ route('isp.settings.pppoe-defaults.update') }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">PPPoE service profile</h3>
</div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="default_profile_name">Default profile name</label>
                        <input id="default_profile_name" class="isp-form-input" type="text" name="default_profile_name" value="{{ old('default_profile_name', $p['default_profile_name'] ?? '') }}" placeholder="default-pppoe" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="service_name">Service name</label>
                        <input id="service_name" class="isp-form-input" type="text" name="service_name" value="{{ old('service_name', $p['service_name'] ?? 'pppoe') }}" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="local_address_pool">Local address pool</label>
                        <input id="local_address_pool" class="isp-form-input font-mono text-sm" type="text" name="local_address_pool" value="{{ old('local_address_pool', $p['local_address_pool'] ?? '') }}" placeholder="10.10.0.1" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="remote_address_pool">Remote address pool</label>
                        <input id="remote_address_pool" class="isp-form-input font-mono text-sm" type="text" name="remote_address_pool" value="{{ old('remote_address_pool', $p['remote_address_pool'] ?? '') }}" placeholder="pppoe-pool" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="max_sessions_per_user">Max sessions per user</label>
                        <input id="max_sessions_per_user" class="isp-form-input" type="number" name="max_sessions_per_user" min="1" max="50" value="{{ old('max_sessions_per_user', $p['max_sessions_per_user'] ?? 1) }}" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="idle_timeout_minutes">Idle timeout (minutes)</label>
                        <input id="idle_timeout_minutes" class="isp-form-input" type="number" name="idle_timeout_minutes" min="0" value="{{ old('idle_timeout_minutes', $p['idle_timeout_minutes'] ?? 0) }}" />
                    </div>
                </div>
                <div class="mt-5 space-y-3">
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Use RADIUS authentication</span>
                        <input type="checkbox" name="use_radius" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('use_radius', ! empty($p['use_radius'])) ? 'checked' : '' }} />
                    </label>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Disconnect on suspend</span>
</span>
                        <input type="checkbox" name="disconnect_on_suspend" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('disconnect_on_suspend', ! empty($p['disconnect_on_suspend'])) ? 'checked' : '' }} />
                    </label>
                </div>
            </div>
            @include('isp.settings.partials.save-footer', ['cancelRoute' => 'isp.settings.pppoe-defaults', 'saveLabel' => 'Save PPPoE defaults'])
        </form>
    </x-isp-settings-workspace>
@endsection
