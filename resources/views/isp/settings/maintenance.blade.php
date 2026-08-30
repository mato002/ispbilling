@extends('layouts.admin')

@section('title', 'Maintenance · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php $m = $isp->settings['maintenance'] ?? []; @endphp

    <x-isp-settings-workspace
        meta-title="Maintenance"
>
        <form method="POST" action="{{ route('isp.settings.maintenance.update') }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Maintenance mode</h3>
</div>
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50/80 px-3 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                    <span>
                        <span class="text-sm font-semibold text-amber-950 dark:text-amber-100">Maintenance mode enabled</span>
                        <span class="isp-settings-hint block text-amber-900/80 dark:text-amber-100/80">Subscribers see the banner below on hotspot portal pages.</span>
                    </span>
                    <input type="checkbox" name="maintenance_mode_enabled" value="1" class="h-4 w-4 rounded border-amber-300 text-amber-600" {{ old('maintenance_mode_enabled', ! empty($m['maintenance_mode_enabled'])) ? 'checked' : '' }} />
                </label>
                <div class="mt-4">
                    <label class="isp-form-label" for="maintenance_message">Public message</label>
                    <textarea id="maintenance_message" name="maintenance_message" rows="4" class="isp-form-input" placeholder="We are upgrading the network. Service returns at 22:00 EAT.">{{ old('maintenance_message', $m['maintenance_message'] ?? '') }}</textarea>
                </div>
                <div class="mt-4">
                    <label class="isp-form-label" for="maintenance_allow_ip">Bypass IP (optional)</label>
                    <input id="maintenance_allow_ip" class="isp-form-input font-mono text-sm" type="text" name="maintenance_allow_ip" value="{{ old('maintenance_allow_ip', $m['maintenance_allow_ip'] ?? '') }}" placeholder="203.0.113.10" />
                </div>
                <label class="mt-4 flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Pause new signups during maintenance</span>
                    <input type="checkbox" name="pause_new_signups" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('pause_new_signups', ! empty($m['pause_new_signups'])) ? 'checked' : '' }} />
                </label>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Scheduled window</h3>
</div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="scheduled_maintenance_start">Starts</label>
                        <input id="scheduled_maintenance_start" class="isp-form-input" type="datetime-local" name="scheduled_maintenance_start" value="{{ old('scheduled_maintenance_start', $m['scheduled_maintenance_start'] ?? '') }}" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="scheduled_maintenance_end">Ends</label>
                        <input id="scheduled_maintenance_end" class="isp-form-input" type="datetime-local" name="scheduled_maintenance_end" value="{{ old('scheduled_maintenance_end', $m['scheduled_maintenance_end'] ?? '') }}" />
                    </div>
                </div>
            </div>
            @include('isp.settings.partials.save-footer', ['cancelRoute' => 'isp.settings.maintenance', 'saveLabel' => 'Save maintenance'])
        </form>
    </x-isp-settings-workspace>
@endsection
