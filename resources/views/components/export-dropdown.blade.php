@props([
    'formats' => [],
    'label' => 'Export',
])

@if (count($formats) > 0)
    <div
        class="relative"
        x-data="{ open: false }"
        @click.outside="open = false"
    >
        <button
            type="button"
            class="pb-btn inline-flex items-center gap-1.5"
            @click="open = !open"
            aria-haspopup="menu"
            :aria-expanded="open"
        >
            <svg class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            {{ $label }}
            <svg class="h-3.5 w-3.5 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>
        <div
            x-show="open"
            x-cloak
            x-transition
            class="absolute right-0 z-20 mt-1 min-w-[11rem] origin-top-right rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900"
            role="menu"
        >
            @foreach ($formats as $format)
                <a
                    href="{{ $format['url'] }}"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                    role="menuitem"
                    data-turbo="false"
                    @if (! empty($format['download'])) download @endif
                    title="{{ $format['title'] ?? $format['label'] }}"
                >
                    {{ $format['label'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif
