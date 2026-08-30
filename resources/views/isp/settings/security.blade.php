@extends('layouts.admin')

@section('title', 'Security preferences · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php $s = $isp->settings['security'] ?? []; @endphp

    <x-isp-settings-workspace
        meta-title="Security preferences"
>
        <form method="POST" action="{{ route('isp.settings.security.update') }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Access policy</h3>
</div>
                <div class="space-y-4">
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Require strong passwords</span>
</span>
                        <input type="checkbox" name="require_strong_passwords" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('require_strong_passwords', ! empty($s['require_strong_passwords'])) ? 'checked' : '' }} />
                    </label>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Force logout on password change</span>
</span>
                        <input type="checkbox" name="force_logout_on_password_change" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('force_logout_on_password_change', ! empty($s['force_logout_on_password_change'])) ? 'checked' : '' }} />
                    </label>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Login alerts</span>
</span>
                        <input type="checkbox" name="login_alerts" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('login_alerts', ! empty($s['login_alerts'])) ? 'checked' : '' }} />
                    </label>
                    <div>
                        <label class="isp-form-label" for="session_timeout_minutes">Session timeout (minutes)</label>
                        <input id="session_timeout_minutes" class="isp-form-input max-w-xs" type="number" name="session_timeout_minutes" min="15" max="1440" value="{{ old('session_timeout_minutes', $s['session_timeout_minutes'] ?? 120) }}" />
                    </div>
                </div>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">IP allowlist</h3>
</div>
                <textarea id="ip_allowlist" name="ip_allowlist" rows="5" class="isp-form-input font-mono text-sm" placeholder="203.0.113.10&#10;198.51.100.4">{{ old('ip_allowlist', $s['ip_allowlist'] ?? '') }}</textarea>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                <span class="font-semibold text-slate-800 dark:text-slate-100">Multi-factor authentication</span> is on the roadmap. Staff permissions are configured under Roles &amp; permissions.
            </div>

            @include('isp.settings.partials.save-footer', ['cancelRoute' => 'isp.settings.security', 'saveLabel' => 'Save security'])
        </form>
    </x-isp-settings-workspace>
@endsection
