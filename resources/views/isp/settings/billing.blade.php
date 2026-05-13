@extends('layouts.admin')

@section('title', 'Billing · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $b = $isp->settings['billing'] ?? [];
    @endphp

    <x-isp-settings-workspace
        meta-title="Tax &amp; invoicing"
        meta-description="Invoice sequencing, automation, penalties, and default VAT-style rates applied to new invoices."
    >
        <form
            method="POST"
            action="{{ route('isp.settings.billing.update') }}"
            class="space-y-4"
            x-data="{ dirty: false, saving: false }"
            @input="dirty = true"
            @change="dirty = true"
            @submit="saving = true"
        >
            @csrf

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Invoice engine</h3>
                    <p class="isp-settings-hint mt-1">Controls how documents are numbered and whether subscriptions spawn invoices automatically.</p>
                </div>
                <div class="space-y-5">
                    <div>
                        <label class="isp-form-label" for="invoice_prefix">Invoice prefix</label>
                        <input
                            id="invoice_prefix"
                            class="isp-form-input max-w-md"
                            type="text"
                            name="invoice_prefix"
                            value="{{ old('invoice_prefix', $b['invoice_prefix'] ?? 'INV') }}"
                            placeholder="INV"
                        />
                        <p class="isp-settings-hint mt-1">Example: INV-2026-0148</p>
                    </div>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Auto-generate invoices</span>
                            <span class="isp-settings-hint block">Create invoices when subscription cycles renew.</span>
                        </span>
                        <input
                            type="checkbox"
                            name="auto_generate_invoices"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                            {{ old('auto_generate_invoices', ! empty($b['auto_generate_invoices'])) ? 'checked' : '' }}
                        />
                    </label>
                </div>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Collections policy</h3>
                    <p class="isp-settings-hint mt-1">Grace windows and penalties are advisory fields for finance automation.</p>
                </div>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label class="isp-form-label" for="grace_period_days">Grace period (days)</label>
                        <input
                            id="grace_period_days"
                            class="isp-form-input"
                            type="number"
                            name="grace_period_days"
                            min="0"
                            value="{{ old('grace_period_days', $b['grace_period_days'] ?? 3) }}"
                        />
                    </div>
                    <div>
                        <label class="isp-form-label" for="penalty_rate">Penalty rate (%)</label>
                        <input
                            id="penalty_rate"
                            class="isp-form-input"
                            type="number"
                            name="penalty_rate"
                            step="0.01"
                            min="0"
                            max="100"
                            value="{{ old('penalty_rate', $b['penalty_rate'] ?? 5) }}"
                        />
                    </div>
                    <div>
                        <label class="isp-form-label" for="tax_rate">Default tax rate (%)</label>
                        @if (! empty($globalTaxEnforced))
                            <input type="hidden" name="tax_rate" value="{{ $platformTaxRate }}" />
                            <input
                                id="tax_rate"
                                class="isp-form-input bg-slate-100 dark:bg-slate-800/50"
                                type="text"
                                value="{{ number_format($platformTaxRate, 2) }}"
                                disabled
                            />
                            <p class="isp-settings-hint mt-1">
                                The platform administrator is enforcing this rate on all new invoices. ISP overrides are suspended until enforcement is turned off in
                                <span class="font-mono text-[11px]">Super Admin → Integrations</span>.
                            </p>
                        @else
                            <input
                                id="tax_rate"
                                class="isp-form-input"
                                type="number"
                                name="tax_rate"
                                step="0.01"
                                min="0"
                                max="100"
                                value="{{ old('tax_rate', $b['tax_rate'] ?? 0) }}"
                            />
                            <p class="isp-settings-hint mt-1">Applied when generating invoices unless the platform enforces a global VAT policy.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="isp-settings-sticky-save">
                <p class="mr-auto text-xs text-slate-500 dark:text-slate-400" x-show="dirty" x-cloak>
                    <span class="font-semibold text-amber-700 dark:text-amber-300">Unsaved changes</span>
                </p>
                <button type="button" class="pb-btn" @click="window.location.reload()">Cancel</button>
                <button type="submit" class="pb-btn pb-btn-primary" :disabled="!dirty || saving" :class="(!dirty || saving) ? 'opacity-50 pointer-events-none' : ''">
                    <span x-show="!saving">Save billing policy</span>
                    <span x-show="saving" x-cloak>Saving…</span>
                </button>
            </div>
        </form>
    </x-isp-settings-workspace>
@endsection
