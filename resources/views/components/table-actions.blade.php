@props([
    'align' => 'right',
    'width' => 'w-48',
])

<div
    class="relative inline-block text-left"
    data-no-row-nav
    x-data="{ open: false, dropUp: false }"
    @click.outside="open = false"
    {{ $attributes }}
>
    <button
        type="button"
        x-ref="trigger"
        @click="
            open = !open;
            if (open) {
                const rect = $refs.trigger.getBoundingClientRect();
                dropUp = rect.bottom + 168 > window.innerHeight;
            }
        "
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40"
        title="Actions"
        aria-label="Row actions"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="12" cy="5" r="1.5"/>
            <circle cx="12" cy="12" r="1.5"/>
            <circle cx="12" cy="19" r="1.5"/>
        </svg>
    </button>
    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute z-[60] {{ $width }} rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-slate-700 dark:bg-slate-900 {{ $align === 'right' ? 'right-0' : 'left-0' }}"
        :class="dropUp
            ? ($align === 'right' ? 'bottom-full mb-1 origin-bottom-right' : 'bottom-full mb-1 origin-bottom-left')
            : ($align === 'right' ? 'top-full mt-1 origin-top-right' : 'top-full mt-1 origin-top-left')"
    >
        {{ $slot }}
    </div>
</div>
