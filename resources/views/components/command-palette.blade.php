<div
    x-show="searchOpen"
    x-cloak
    class="fixed inset-0 z-[80] flex items-start justify-center px-4 pt-[12vh] sm:px-6"
    @keydown.escape.window="closePalette()"
    role="presentation"
>
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closePalette()" aria-hidden="true"></div>

    <div
        class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="pb-command-palette-title"
        @click.stop
    >
        <h2 id="pb-command-palette-title" class="sr-only">{{ __('Feature finder') }}</h2>

        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
            <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input
                type="search"
                x-ref="searchInput"
                x-model="paletteQuery"
                @keydown.arrow-down.prevent="movePaletteSelection(1)"
                @keydown.arrow-up.prevent="movePaletteSelection(-1)"
                @keydown.enter.prevent="openPaletteSelection()"
                class="w-full border-0 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100"
                placeholder="{{ __('Search pages, settings, reports, network modules…') }}"
                autocomplete="off"
                aria-label="{{ __('Search features') }}"
            >
            <kbd class="hidden shrink-0 rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-500 dark:border-slate-700 dark:bg-slate-800 sm:inline">ESC</kbd>
        </div>

        <div class="max-h-[min(60vh,28rem)] overflow-y-auto p-2">
            <template x-if="paletteQuery.trim() === '' && discoveryRecent.length > 0">
                <section class="pb-2">
                    <p class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Recent') }}</p>
                    <template x-for="(item, index) in discoveryRecent" :key="'recent-' + item.id">
                        <div
                            class="group flex items-start gap-2 rounded-lg px-2 py-2"
                            :class="paletteHighlightIndex === index ? 'bg-indigo-50 dark:bg-indigo-500/10' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                            @mouseenter="paletteHighlightIndex = index"
                        >
                            <button type="button" class="min-w-0 flex-1 text-left" @click="navigatePaletteItem(item)">
                                <span class="block text-sm font-medium text-slate-800 dark:text-slate-100" x-text="item.label"></span>
                                <span class="mt-0.5 block text-xs text-slate-500" x-text="item.path"></span>
                            </button>
                        </div>
                    </template>
                </section>
            </template>

            <template x-if="paletteQuery.trim() !== '' && paletteLoading">
                <p class="px-4 py-8 text-center text-sm text-slate-500">{{ __('Searching…') }}</p>
            </template>

            <template x-for="section in groupedPaletteResults()" :key="section.key">
                <section class="pb-2">
                    <p class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400" x-text="section.label"></p>
                    <template x-for="(item, index) in section.items" :key="item.id">
                        <div
                            class="group flex items-start gap-2 rounded-lg px-2 py-2"
                            :class="paletteHighlightIndex === paletteItemIndex(section.key, index) ? 'bg-indigo-50 dark:bg-indigo-500/10' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                            @mouseenter="paletteHighlightIndex = paletteItemIndex(section.key, index)"
                        >
                            <button type="button" class="min-w-0 flex-1 text-left" @click="navigatePaletteItem(item)">
                                <span class="block text-sm font-medium text-slate-800 dark:text-slate-100" x-text="item.label"></span>
                                <span class="mt-0.5 block text-xs text-slate-500" x-text="item.path"></span>
                            </button>
                            <button
                                type="button"
                                class="shrink-0 rounded-lg px-2 py-1 text-xs text-slate-400 opacity-0 transition group-hover:opacity-100 hover:bg-slate-100 dark:hover:bg-slate-800"
                                @click.stop="toggleDiscoveryFavorite(item.id)"
                                x-text="isDiscoveryFavorite(item.id) ? '★' : '☆'"
                                :title="isDiscoveryFavorite(item.id) ? '{{ __('Remove favorite') }}' : '{{ __('Add favorite') }}'"
                            ></button>
                        </div>
                    </template>
                </section>
            </template>

            <p
                x-show="paletteQuery.trim() !== '' && ! paletteLoading && paletteResults.length === 0"
                class="px-4 py-8 text-center text-sm text-slate-500"
            >
                {{ __('No features found. Try a different keyword.') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-4 py-2 text-[11px] text-slate-400 dark:border-slate-800">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1"><kbd class="rounded border border-slate-200 px-1 dark:border-slate-700">↑</kbd><kbd class="rounded border border-slate-200 px-1 dark:border-slate-700">↓</kbd> {{ __('Navigate') }}</span>
                <span class="inline-flex items-center gap-1"><kbd class="rounded border border-slate-200 px-1 dark:border-slate-700">↵</kbd> {{ __('Open') }}</span>
            </div>
            <span>{{ __('Feature search — not record search') }}</span>
        </div>
    </div>
</div>
