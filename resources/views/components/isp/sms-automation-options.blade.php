@props([
    'automation' => [],
])

@php
    $a = is_array($automation) ? $automation : [];

    $toggles = [
        [
            'name' => 'sms_automation_hotspot_vouchers',
            'label' => 'Send Hotspot Vouchers via SMS',
            'checked' => ! empty($a['hotspot_vouchers']),
        ],
        [
            'name' => 'sms_automation_pppoe_invoice_reminders',
            'label' => 'Send PPPoE Invoice Reminders',
            'checked' => ! empty($a['pppoe_invoice_reminders']),
        ],
        [
            'name' => 'sms_automation_disconnect_alerts',
            'label' => 'Send Disconnect Alerts',
            'checked' => ! empty($a['disconnect_alerts']),
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'mt-8 border-t border-dashed border-[var(--pb-border)] pt-6']) }}>
    <div class="mb-5">
        <h3 class="isp-settings-section-title">Automated Messaging Options</h3>
    </div>

    <div class="space-y-3">
        @foreach ($toggles as $toggle)
            @php
                $isChecked = (bool) old($toggle['name'], $toggle['checked']);
            @endphp
            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-[var(--pb-border)] bg-slate-50/60 px-4 py-3.5 transition-colors hover:border-emerald-200/80 dark:bg-slate-900/30 dark:hover:border-emerald-500/25">
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $toggle['label'] }}</span>
                <span class="relative inline-flex shrink-0">
                    <input type="hidden" name="{{ $toggle['name'] }}" value="0">
                    <input
                        type="checkbox"
                        name="{{ $toggle['name'] }}"
                        value="1"
                        class="peer sr-only"
                        role="switch"
                        aria-label="{{ $toggle['label'] }}"
                        @checked($isChecked)
                    >
                    <span
                        class="h-7 w-12 rounded-full border border-slate-200 bg-slate-200 transition-all duration-200 peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/25 peer-checked:border-emerald-500 peer-checked:bg-emerald-500 dark:border-slate-600 dark:bg-slate-700 dark:peer-checked:border-emerald-500 dark:peer-checked:bg-emerald-500"
                        aria-hidden="true"
                    ></span>
                    <span
                        class="pointer-events-none absolute left-0.5 top-0.5 h-6 w-6 rounded-full bg-white shadow-sm transition-transform duration-200 peer-checked:translate-x-5 dark:bg-slate-100"
                        aria-hidden="true"
                    ></span>
                </span>
            </label>
        @endforeach
    </div>

    <div class="mt-5 flex gap-3 rounded-xl border border-sky-200/80 bg-sky-50/90 px-4 py-3.5 dark:border-sky-500/25 dark:bg-sky-950/30">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
        </svg>
        <p class="text-xs leading-relaxed text-sky-900/90 dark:text-sky-100/90">
            <span class="font-semibold">Note:</span>
            If your SMS Wallet Balance drops to KSh 0.00, high-priority automated access tokens will fail to dispatch, and clients will only receive standard email alerts.
        </p>
    </div>
</div>
