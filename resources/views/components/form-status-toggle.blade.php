@props([
    'name' => 'is_active',
    'label' => 'Plan status',
    'description' => 'Active tiers appear during ISP onboarding and plan selection.',
    'checked' => true,
])

@php
    $isChecked = (bool) old($name, $checked);
@endphp

<label class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3.5 transition-colors dark:border-slate-700 dark:bg-slate-800/40">
    <span class="min-w-0">
        <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $label }}</span>
        @if ($description)
            <span class="mt-0.5 block text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $description }}</span>
        @endif
    </span>
    <span class="relative inline-flex shrink-0">
        <input type="hidden" name="{{ $name }}" value="0">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="1"
            class="peer sr-only"
            role="switch"
            @checked($isChecked)
        >
        <span
            class="h-7 w-12 rounded-full border border-slate-200 bg-slate-200 transition-all duration-200 peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/20 peer-checked:border-emerald-500 peer-checked:bg-emerald-500 dark:border-slate-600 dark:bg-slate-700 dark:peer-checked:border-emerald-500 dark:peer-checked:bg-emerald-500"
            aria-hidden="true"
        ></span>
        <span
            class="pointer-events-none absolute left-0.5 top-0.5 h-6 w-6 rounded-full bg-white shadow-sm transition-transform duration-200 peer-checked:translate-x-5 dark:bg-slate-100"
            aria-hidden="true"
        ></span>
    </span>
</label>
