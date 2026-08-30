@extends('layouts.admin')

@section('title', 'Roles & permissions · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    <x-isp-settings-workspace
        meta-title="Roles & permissions"
>
        <div class="rounded-xl border border-indigo-200/80 bg-indigo-50/80 px-4 py-3 text-sm text-indigo-950 dark:border-indigo-500/30 dark:bg-indigo-950/30 dark:text-indigo-100">
            <span class="font-semibold">ISP administrators</span>
            <span class="text-indigo-900/85 dark:text-indigo-100/85"> always receive every permission. Configure staff access below — changes apply immediately to active sessions on next request.</span>
        </div>

        <form
            method="POST"
            action="{{ route('isp.settings.roles.update') }}"
            class="pb-card isp-settings-panel p-4 sm:p-6"
            x-data="{ dirty: false, saving: false }"
            @change="dirty = true"
            @submit="saving = true"
        >
            @csrf

            <div class="mb-6 border-b border-dashed border-[var(--pb-border)] pb-5">
                <h3 class="isp-settings-section-title">Staff operator permissions</h3>
</div>

            <div class="space-y-8">
                @foreach ($permissionGroups as $groupKey => $group)
                    <section>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $group['label'] ?? $groupKey }}</h4>
                        <div class="mt-3 divide-y divide-[var(--pb-border)] rounded-xl border border-[var(--pb-border)] bg-slate-50/40 dark:bg-slate-900/20">
                            @foreach ($group['permissions'] ?? [] as $permissionKey => $permissionLabel)
                                <label class="flex cursor-pointer items-start gap-3 px-4 py-3 sm:items-center">
                                    <input
                                        type="checkbox"
                                        name="permissions[{{ $permissionKey }}]"
                                        value="1"
                                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600"
                                        {{ old("permissions.{$permissionKey}", $staffMatrix[$permissionKey] ?? false) ? 'checked' : '' }}
                                    />
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-slate-800 dark:text-slate-100">{{ $permissionLabel }}</span>
                                        <span class="font-mono text-[10px] text-slate-400">{{ $permissionKey }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="isp-settings-sticky-save mt-6 border-t border-slate-100 pt-6 dark:border-slate-800">
                <p class="mr-auto text-xs text-slate-500 dark:text-slate-400" x-show="dirty" x-cloak>
                    <span class="font-semibold text-amber-700 dark:text-amber-300">Unsaved changes</span>
                </p>
                <a href="{{ route('isp.settings.users') }}" class="pb-btn">Manage users</a>
                <button type="submit" class="pb-btn pb-btn-primary" :disabled="!dirty || saving" :class="(!dirty || saving) ? 'opacity-50 pointer-events-none' : ''">
                    <span x-show="!saving">Save permissions</span>
                    <span x-show="saving" x-cloak>Saving…</span>
                </button>
            </div>
        </form>
    </x-isp-settings-workspace>
@endsection
