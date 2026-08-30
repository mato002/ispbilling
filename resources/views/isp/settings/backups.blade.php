@extends('layouts.admin')

@section('title', 'Backups · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $b = $isp->settings['backups'] ?? [];
        $lastBackup = $b['last_backup_at'] ?? null;
    @endphp

    <x-isp-settings-workspace
        meta-title="Backups"
>
        <form method="POST" action="{{ route('isp.settings.backups.update') }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Automated backups</h3>
</div>
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Enable scheduled backups</span>
                    <input type="checkbox" name="auto_backup_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('auto_backup_enabled', ! empty($b['auto_backup_enabled'])) ? 'checked' : '' }} />
                </label>
                <div class="mt-4 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="backup_frequency">Frequency</label>
                        <select id="backup_frequency" name="backup_frequency" class="isp-form-input">
                            @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('backup_frequency', $b['backup_frequency'] ?? 'weekly') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="isp-form-label" for="retention_days">Retention (days)</label>
                        <input id="retention_days" class="isp-form-input" type="number" name="retention_days" min="1" max="365" value="{{ old('retention_days', $b['retention_days'] ?? 30) }}" />
                    </div>
                </div>
                <div class="mt-4 space-y-3">
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Include subscriber export metadata</span>
                        <input type="checkbox" name="include_customer_exports" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('include_customer_exports', ! empty($b['include_customer_exports'])) ? 'checked' : '' }} />
                    </label>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Email when backup completes</span>
                        <input type="checkbox" name="notify_on_backup" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('notify_on_backup', ! empty($b['notify_on_backup'])) ? 'checked' : '' }} />
                    </label>
                </div>
            </div>
            @include('isp.settings.partials.save-footer', ['cancelRoute' => 'isp.settings.backups', 'saveLabel' => 'Save backup policy'])
        </form>

        <div class="pb-card isp-settings-panel p-4 sm:p-6">
            <h3 class="isp-settings-section-title">Manual export</h3>
@if ($lastBackup)
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">Last export: <span class="font-mono text-xs">{{ $lastBackup }}</span></p>
            @endif
            <a href="{{ route('isp.settings.backups.export') }}" class="pb-btn pb-btn-primary mt-4 inline-flex" data-turbo="false">Download workspace backup</a>
        </div>
    </x-isp-settings-workspace>
@endsection
