@extends('layouts.admin')

@section('title', 'Router defaults & global hotspot · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $h = $isp->settings['network']['hotspot'] ?? [];
        $smsAutomation = $isp->settings['sms']['automation'] ?? [];
    @endphp

    <x-isp-settings-workspace
        meta-title="Router defaults & global hotspot settings"
>
        <form method="POST" action="{{ route('isp.settings.hotspot.update') }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Session &amp; login</h3>
                </div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="default_login_by">Default login by</label>
                        <select id="default_login_by" name="default_login_by" class="isp-form-input">
                            @foreach (['mac' => 'MAC address', 'cookie' => 'Cookie', 'http-chap' => 'HTTP CHAP', 'http-pap' => 'HTTP PAP'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('default_login_by', $h['default_login_by'] ?? 'mac') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="isp-form-label" for="default_session_timeout">Session timeout</label>
                        <input id="default_session_timeout" class="isp-form-input" type="text" name="default_session_timeout" value="{{ old('default_session_timeout', $h['default_session_timeout'] ?? '1h') }}" placeholder="1h, 30m, 1d" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="isp-form-label" for="redirect_url">Post-login redirect URL</label>
                        <input id="redirect_url" class="isp-form-input" type="url" name="redirect_url" value="{{ old('redirect_url', $h['redirect_url'] ?? '') }}" placeholder="https://yourisp.com/welcome" />
                    </div>
                </div>
                <div class="mt-5 space-y-3">
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Use RADIUS</span>
                        <input type="checkbox" name="use_radius" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('use_radius', ! empty($h['use_radius'])) ? 'checked' : '' }} />
                    </label>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Walled garden</span>
                        <input type="checkbox" name="walled_garden_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('walled_garden_enabled', ! empty($h['walled_garden_enabled'])) ? 'checked' : '' }} />
                    </label>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Shared users (one voucher, many devices)</span>
                        <input type="checkbox" name="shared_users" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('shared_users', ! empty($h['shared_users'])) ? 'checked' : '' }} />
                    </label>
                </div>

                <x-isp.sms-automation-options :automation="$smsAutomation" />
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Trial access</h3>
                </div>
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Enable trial sessions</span>
                    <input type="checkbox" name="trial_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('trial_enabled', ! empty($h['trial_enabled'])) ? 'checked' : '' }} />
                </label>
                <div class="mt-4">
                    <label class="isp-form-label" for="trial_duration_minutes">Trial duration (minutes)</label>
                    <input id="trial_duration_minutes" class="isp-form-input max-w-xs" type="number" name="trial_duration_minutes" min="0" value="{{ old('trial_duration_minutes', $h['trial_duration_minutes'] ?? 30) }}" />
                </div>
            </div>
            @include('isp.settings.partials.save-footer', ['cancelRoute' => 'isp.settings.hotspot', 'saveLabel' => 'Save hotspot & messaging settings'])
        </form>
    </x-isp-settings-workspace>
@endsection
