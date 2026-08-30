@extends('layouts.admin')

@section('title', 'API integrations · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php $api = $isp->settings['api'] ?? []; @endphp

    <x-isp-settings-workspace
        meta-title="API integrations"
>
        <form method="POST" action="{{ route('isp.settings.api-integrations.update') }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Workspace API</h3>
</div>
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">API access enabled</span>
                    <input type="checkbox" name="api_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('api_enabled', ! empty($api['api_enabled'])) ? 'checked' : '' }} />
                </label>
                <label class="mt-3 flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                    <span>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Expose subscriber read API</span>
</span>
                    <input type="checkbox" name="expose_customer_api" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" {{ old('expose_customer_api', ! empty($api['expose_customer_api'])) ? 'checked' : '' }} />
                </label>
                <div class="mt-4">
                    <label class="isp-form-label" for="rate_limit_per_minute">Rate limit (requests / minute)</label>
                    <input id="rate_limit_per_minute" class="isp-form-input max-w-xs" type="number" name="rate_limit_per_minute" min="10" max="1000" value="{{ old('rate_limit_per_minute', $api['rate_limit_per_minute'] ?? 120) }}" />
                </div>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Webhooks</h3>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="isp-form-label" for="webhook_url">Webhook URL</label>
                        <input id="webhook_url" class="isp-form-input font-mono text-sm" type="url" name="webhook_url" value="{{ old('webhook_url', $api['webhook_url'] ?? '') }}" placeholder="https://hooks.example.com/primebilling" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="webhook_secret">Webhook secret</label>
                        <input id="webhook_secret" class="isp-form-input font-mono text-sm" type="password" name="webhook_secret" value="" placeholder="{{ filled($api['webhook_secret'] ?? null) ? '•••••••• (leave blank to keep)' : 'Optional signing secret' }}" autocomplete="new-password" />
                    </div>
                    <div>
                        <label class="isp-form-label" for="allowed_origins">Allowed origins</label>
                        <textarea id="allowed_origins" name="allowed_origins" rows="4" class="isp-form-input font-mono text-sm" placeholder="https://crm.example.com&#10;https://app.example.com">{{ old('allowed_origins', $api['allowed_origins'] ?? '') }}</textarea>
</div>
                </div>
            </div>
            @include('isp.settings.partials.save-footer', ['cancelRoute' => 'isp.settings.api-integrations', 'saveLabel' => 'Save API settings'])
        </form>
    </x-isp-settings-workspace>
@endsection
