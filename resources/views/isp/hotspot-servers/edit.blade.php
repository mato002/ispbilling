@extends('layouts.admin')

@section('title', 'Edit '.$hotspotServer->name)

@section('header')
    <x-isp-page-header
        :title="'Edit '.$hotspotServer->name"
        module="Network"
        description="Update login and timeout settings stored in billing. Re-sync from router to refresh RouterOS values."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.hotspot-servers.show', $hotspotServer) }}" class="pb-btn">View server</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('isp.hotspot-servers.update', $hotspotServer) }}" class="pb-card space-y-5 p-6">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-dashed border-[var(--pb-border)] bg-slate-50/80 p-4 text-sm dark:bg-slate-800/40">
                <p class="font-semibold text-slate-900 dark:text-white">{{ $hotspotServer->name }}</p>
                <p class="mt-1 text-slate-600 dark:text-slate-300">
                    Interface <span class="font-mono">{{ $hotspotServer->interface }}</span>
                    · {{ $hotspotServer->router?->name }}
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="default_profile">Default profile</label>
                    <input id="default_profile" type="text" name="default_profile" value="{{ old('default_profile', $hotspotServer->default_profile) }}" class="isp-form-input">
                    @error('default_profile')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="login_by">Login by</label>
                    <select id="login_by" name="login_by" class="isp-form-select" required>
                        @foreach (['cookie' => 'Cookie', 'http-chap' => 'HTTP CHAP', 'mac' => 'MAC'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('login_by', $hotspotServer->login_by) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('login_by')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="use_radius" value="1" class="rounded border-slate-300" @checked(old('use_radius', $hotspotServer->use_radius))>
                    Use RADIUS
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="radius_accounting" value="1" class="rounded border-slate-300" @checked(old('radius_accounting', $hotspotServer->radius_accounting))>
                    RADIUS accounting
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="status_autologout" value="1" class="rounded border-slate-300" @checked(old('status_autologout', $hotspotServer->status_autologout))>
                    Status autologout
                </label>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label class="isp-form-label" for="mac_cookie_timeout">MAC cookie (s)</label>
                    <input id="mac_cookie_timeout" type="number" name="mac_cookie_timeout" min="0" value="{{ old('mac_cookie_timeout', $hotspotServer->mac_cookie_timeout) }}" class="isp-form-input">
                </div>
                <div>
                    <label class="isp-form-label" for="idle_timeout">Idle timeout (s)</label>
                    <input id="idle_timeout" type="number" name="idle_timeout" min="0" value="{{ old('idle_timeout', $hotspotServer->idle_timeout) }}" class="isp-form-input">
                </div>
                <div>
                    <label class="isp-form-label" for="keepalive_timeout">Keepalive (s)</label>
                    <input id="keepalive_timeout" type="number" name="keepalive_timeout" min="0" value="{{ old('keepalive_timeout', $hotspotServer->keepalive_timeout) }}" class="isp-form-input">
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="allowed_users">Allowed users</label>
                    <input id="allowed_users" type="number" name="allowed_users" min="0" value="{{ old('allowed_users', $hotspotServer->allowed_users) }}" class="isp-form-input">
                </div>
                <div>
                    <label class="isp-form-label" for="shared_users">Shared users</label>
                    <input id="shared_users" type="number" name="shared_users" min="0" value="{{ old('shared_users', $hotspotServer->shared_users) }}" class="isp-form-input">
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="trial_profile">Trial profile</label>
                    <input id="trial_profile" type="text" name="trial_profile" value="{{ old('trial_profile', $hotspotServer->trial_profile) }}" class="isp-form-input">
                </div>
                <div>
                    <label class="isp-form-label" for="trial_users">Trial users</label>
                    <input id="trial_users" type="number" name="trial_users" min="0" value="{{ old('trial_users', $hotspotServer->trial_users) }}" class="isp-form-input">
                </div>
                <div>
                    <label class="isp-form-label" for="trial_uptime">Trial uptime (s)</label>
                    <input id="trial_uptime" type="number" name="trial_uptime" min="0" value="{{ old('trial_uptime', $hotspotServer->trial_uptime) }}" class="isp-form-input">
                </div>
                <div>
                    <label class="isp-form-label" for="trial_uptime_reset">Trial reset (s)</label>
                    <input id="trial_uptime_reset" type="number" name="trial_uptime_reset" min="0" value="{{ old('trial_uptime_reset', $hotspotServer->trial_uptime_reset) }}" class="isp-form-input">
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.hotspot-servers.show', $hotspotServer) }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Save changes</button>
            </div>
        </form>
    </div>
@endsection
