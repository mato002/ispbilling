@extends('layouts.admin')

@section('title', 'Router defaults · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $r = $isp->settings['network']['router_defaults'] ?? [];
        $smsAutomation = $isp->settings['sms']['automation'] ?? [];
    @endphp

    <x-isp-settings-workspace
        meta-title="Router defaults"
>
        <form method="POST" action="{{ route('isp.settings.router-defaults.update') }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">API &amp; polling</h3>
                </div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="default_api_port">Default API port</label>
                        <input id="default_api_port" class="isp-form-input" type="number" name="default_api_port" value="{{ old('default_api_port', $r['default_api_port'] ?? 8728) }}" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="default_connection_timeout">Connection timeout (sec)</label>
                        <input id="default_connection_timeout" class="isp-form-input" type="number" name="default_connection_timeout" value="{{ old('default_connection_timeout', $r['default_connection_timeout'] ?? 10) }}" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="default_poll_interval_seconds">Poll interval (seconds)</label>
                        <input id="default_poll_interval_seconds" class="isp-form-input" type="number" name="default_poll_interval_seconds" value="{{ old('default_poll_interval_seconds', $r['default_poll_interval_seconds'] ?? 60) }}" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="lifecycle_default">Default lifecycle</label>
                        <select id="lifecycle_default" name="lifecycle_default" class="isp-form-input">
                            @foreach (['live', 'stock', 'maintenance', 'retired'] as $opt)
                                <option value="{{ $opt }}" @selected(old('lifecycle_default', $r['lifecycle_default'] ?? 'live') === $opt)>{{ ucfirst($opt) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-5 space-y-3">
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Use SSL API by default</span>
                        <input type="checkbox" name="use_ssl_by_default" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('use_ssl_by_default', ! empty($r['use_ssl_by_default'])) ? 'checked' : '' }} />
                    </label>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Auto-sync on provision</span>
                        <input type="checkbox" name="auto_sync_on_provision" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('auto_sync_on_provision', ! empty($r['auto_sync_on_provision'])) ? 'checked' : '' }} />
                    </label>
                </div>

                <x-isp.sms-automation-options :automation="$smsAutomation" />
            </div>
            @include('isp.settings.partials.save-footer', ['cancelRoute' => 'isp.settings.router-defaults', 'saveLabel' => 'Save router & messaging defaults'])
        </form>
    </x-isp-settings-workspace>
@endsection
