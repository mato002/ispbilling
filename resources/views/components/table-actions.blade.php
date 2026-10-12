@props([
    'align' => 'right',
    'width' => 'w-48',
])

<div
    class="relative inline-block text-left"
    data-no-row-nav
    data-align="{{ $align }}"
    x-data="{
        open: false,
        menuStyle: 'position: fixed; left: 12px; top: 12px; z-index: 10000;',
        updatePosition() {
            this.$nextTick(() => {
                const trigger = this.$refs.trigger;
                const menu = this.$refs.menu;
                if (!trigger || !menu) return;
                const triggerRect = trigger.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const padding = 12;
                const gap = 8;
                const above = triggerRect.bottom + menuRect.height + gap > window.innerHeight - padding
                    && triggerRect.top > menuRect.height + gap + padding;
                const top = above
                    ? triggerRect.top - menuRect.height - gap
                    : Math.min(triggerRect.bottom + gap, window.innerHeight - menuRect.height - padding);
                const alignRight = $el.dataset.align === 'right';
                const desiredLeft = alignRight ? triggerRect.right - menuRect.width : triggerRect.left;
                const left = Math.max(padding, Math.min(desiredLeft, window.innerWidth - menuRect.width - padding));
                this.menuStyle = `position: fixed; left: ${left}px; top: ${Math.max(padding, top)}px; z-index: 10000;`;
            });
        }
    }"
    @click.outside="if (!$event.target.closest('[data-table-actions-menu]')) open = false"
    @keydown.escape.window="open = false"
    @scroll.window.capture="if (open) open = false"
    {{ $attributes }}
>
    <button
        type="button"
        x-ref="trigger"
        @click="open = !open; if (open) updatePosition()"
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
    <template x-teleport="body">
        <div
            x-ref="menu"
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            :style="menuStyle"
            data-table-actions-menu
            class="{{ $width }} max-h-[min(70vh,28rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 text-left shadow-2xl ring-1 ring-slate-950/10 dark:border-slate-700 dark:bg-slate-900"
        >
            {{ $slot }}
        </div>
    </template>
</div>
