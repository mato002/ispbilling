@extends('layouts.admin')

@section('title', 'Router Migration Wizard')

@section('header')
    <x-isp-page-header
        title="Fleet Router Migration Wizard"
        module="Network Operations"
        description="Cut over subscribers from one MikroTik router to another — all provisioning runs as background jobs."
    />
@endsection

@section('content')
<div class="mx-auto max-w-4xl space-y-6" x-data="routerMigrationWizard()">
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
        Migration orchestrates session disconnect, user recreation, queue rebuild, and MAC binding on the new router. Billing and subscription dates are never modified.
    </div>

    <form method="POST" action="{{ route('isp.router-migration.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 pb-card p-5">
            <div>
                <label class="isp-form-label">Source router (old)</label>
                <select name="old_router_id" x-model="oldRouterId" @change="loadPreview()" required class="isp-form-select w-full">
                    <option value="">Select router…</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="isp-form-label">Target router (new)</label>
                <select name="new_router_id" x-model="newRouterId" @change="loadPreview()" required class="isp-form-select w-full">
                    <option value="">Select router…</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input type="checkbox" x-model="includeAll" @change="loadPreview()" class="mt-1 rounded border-slate-300">
                    <span>
                        Include suspended / expired subscribers still assigned to the source router
                        <span class="block text-xs text-slate-500">Default is active subscriptions only. Inactive accounts stay disabled on the target router.</span>
                    </span>
                </label>
                <input type="hidden" name="eligibility" :value="includeAll ? 'all_on_router' : 'active_only'">
            </div>
        </div>

        <div class="pb-card p-5" x-show="previewLoading || previewTotal !== null">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold">Affected subscribers</h3>
                <span class="text-xs text-slate-500" x-show="previewTotal !== null">
                    <span x-text="previewTotal"></span> with active subscriptions
                </span>
            </div>
            <p x-show="previewError" class="mt-2 text-sm text-rose-600" x-text="previewError"></p>
            <p x-show="previewLoading" class="mt-2 text-sm text-slate-500">Loading preview…</p>
            <div class="mt-3 max-h-64 overflow-y-auto rounded-lg border border-[var(--pb-border)]" x-show="preview.length">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50 text-left font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Customer</th>
                            <th class="px-3 py-2">Username</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Plan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--pb-border)]">
                        <template x-for="row in preview" :key="row.id">
                            <tr>
                                <td class="px-3 py-2" x-text="row.name"></td>
                                <td class="px-3 py-2 font-mono" x-text="row.username"></td>
                                <td class="px-3 py-2" x-text="row.status"></td>
                                <td class="px-3 py-2" x-text="row.connection_type"></td>
                                <td class="px-3 py-2" x-text="row.plan || '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <label class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="confirm" value="1" required class="mt-1 rounded border-slate-300">
            <span>I confirm this migration will queue background jobs to reprovision all listed subscribers on the target router.</span>
        </label>

        <div class="flex justify-end gap-3">
            <a href="{{ route('isp.router-migration.index') }}" class="pb-btn pb-btn-secondary">Cancel</a>
            <button type="submit" class="pb-btn" :disabled="!oldRouterId || !newRouterId || oldRouterId === newRouterId">
                Start migration batch
            </button>
        </div>
    </form>
</div>

<script>
function routerMigrationWizard() {
    return {
        oldRouterId: '',
        newRouterId: '',
        includeAll: false,
        preview: [],
        previewTotal: null,
        previewLoading: false,
        previewError: null,
        async loadPreview() {
            this.previewError = null;
            if (!this.oldRouterId || !this.newRouterId || this.oldRouterId === this.newRouterId) {
                this.preview = [];
                this.previewTotal = null;
                return;
            }
            this.previewLoading = true;
            try {
                const eligibility = this.includeAll ? 'all_on_router' : 'active_only';
                const res = await fetch(`{{ route('isp.router-migration.preview') }}?old_router_id=${this.oldRouterId}&new_router_id=${this.newRouterId}&eligibility=${eligibility}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Preview failed');
                this.preview = data.preview || [];
                this.previewTotal = data.total ?? 0;
            } catch (e) {
                this.previewError = e.message;
                this.preview = [];
                this.previewTotal = null;
            } finally {
                this.previewLoading = false;
            }
        },
    };
}
</script>
@endsection
