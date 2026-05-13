@extends('layouts.admin')

@section('title', 'Notification preferences · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $n = $isp->settings['notifications'] ?? [];
        $S = $ispSettingsShell;
    @endphp

    <x-isp-settings-workspace
        meta-title="Notification preferences"
        meta-description="Route operational notifications through email and SMS, and tune invoice reminder automation."
    >
        @if ($S && $S['smtp']['tone'] !== 'ok')
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                <span class="font-semibold">SMTP credentials may be missing.</span>
                <span class="text-amber-900/85 dark:text-amber-100/85"> {{ $S['smtp']['detail'] }}</span>
            </div>
        @endif

        @if ($S && ($S['sms']['tone'] ?? '') === 'warn' && ! empty($n['sms_notifications']))
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 dark:border-slate-600 dark:bg-slate-800/50 dark:text-slate-100">
                <span class="font-semibold">SMS provider incomplete.</span>
                <span class="text-slate-600 dark:text-slate-300"> Select Africa's Talking, Twilio, or custom integration to finish setup.</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('isp.settings.notifications.update') }}"
            class="space-y-4"
            x-data="{ dirty: false, saving: false }"
            @input="dirty = true"
            @change="dirty = true"
            @submit="saving = true"
        >
            @csrf

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Email</h3>
                    <p class="isp-settings-hint mt-1">Workspace-level alerts and copies of critical billing events.</p>
                    @if ($S)
                        <p class="mt-2 inline-flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                            <span class="pb-dot {{ $S['smtp']['tone'] === 'ok' ? 'pb-dot-ok' : 'pb-dot-warn' }}"></span>
                            {{ $S['smtp']['label'] }}
                        </p>
                    @endif
                </div>
                <div class="space-y-4">
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Email notifications</span>
                            <span class="isp-settings-hint block">Deliver alerts to the operations mailbox.</span>
                        </span>
                        <input
                            type="checkbox"
                            name="email_notifications"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                            {{ old('email_notifications', ! empty($n['email_notifications'])) ? 'checked' : '' }}
                        />
                    </label>
                    <div>
                        <label class="isp-form-label" for="notification_email">Notification email</label>
                        <input
                            id="notification_email"
                            class="isp-form-input"
                            type="email"
                            name="notification_email"
                            value="{{ old('notification_email', $n['notification_email'] ?? $isp->email) }}"
                            placeholder="ops@yourisp.com"
                        />
                    </div>
                </div>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">SMS</h3>
                    <p class="isp-settings-hint mt-1">High-priority SMS for field teams and automated subscriber notices.</p>
                    @if ($S)
                        <p class="mt-2 inline-flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                            <span class="pb-dot {{ ($S['sms']['tone'] ?? '') === 'ok' ? 'pb-dot-ok' : 'pb-dot-warn' }}"></span>
                            {{ $S['sms']['label'] }}
                        </p>
                    @endif
                </div>
                <div class="space-y-4">
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">SMS notifications</span>
                            <span class="isp-settings-hint block">Requires a configured provider in your deployment.</span>
                        </span>
                        <input
                            type="checkbox"
                            name="sms_notifications"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                            {{ old('sms_notifications', ! empty($n['sms_notifications'])) ? 'checked' : '' }}
                        />
                    </label>
                    <div>
                        <label class="isp-form-label" for="sms_provider">SMS provider</label>
                        <select id="sms_provider" class="isp-form-select" name="sms_provider">
                            @php $sp = old('sms_provider', $n['sms_provider'] ?? ''); @endphp
                            <option value="">Select provider</option>
                            <option value="africas_talking" @selected($sp === 'africas_talking')>Africa's Talking</option>
                            <option value="twilio" @selected($sp === 'twilio')>Twilio</option>
                            <option value="custom" @selected($sp === 'custom')>Custom API</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="pb-card isp-settings-panel p-4 sm:p-6">
                <div class="mb-5 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <h3 class="isp-settings-section-title">Invoice reminders</h3>
                    <p class="isp-settings-hint mt-1">Automatic nudges before invoices cross the due window.</p>
                </div>
                <div class="space-y-4">
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-3 py-3 dark:bg-slate-900/30">
                        <span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Send invoice reminders</span>
                            <span class="isp-settings-hint block">Uses the channels enabled above.</span>
                        </span>
                        <input
                            type="checkbox"
                            name="invoice_reminders"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                            {{ old('invoice_reminders', ! empty($n['invoice_reminders'])) ? 'checked' : '' }}
                        />
                    </label>
                    <div>
                        <label class="isp-form-label" for="reminder_days">Days before due date</label>
                        <input
                            id="reminder_days"
                            class="isp-form-input max-w-xs"
                            type="number"
                            name="reminder_days"
                            min="0"
                            value="{{ old('reminder_days', $n['reminder_days'] ?? 3) }}"
                        />
                    </div>
                </div>
            </div>

            <div class="isp-settings-sticky-save">
                <p class="mr-auto text-xs text-slate-500 dark:text-slate-400" x-show="dirty" x-cloak>
                    <span class="font-semibold text-amber-700 dark:text-amber-300">Unsaved changes</span>
                </p>
                <button type="button" class="pb-btn" @click="window.location.reload()">Cancel</button>
                <button type="submit" class="pb-btn pb-btn-primary" :disabled="!dirty || saving" :class="(!dirty || saving) ? 'opacity-50 pointer-events-none' : ''">
                    <span x-show="!saving">Save communications</span>
                    <span x-show="saving" x-cloak>Saving…</span>
                </button>
            </div>
        </form>
    </x-isp-settings-workspace>
@endsection
