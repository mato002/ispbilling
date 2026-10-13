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
            <x-font-awesome-icon name="download" class="h-4 w-4 opacity-70" />
            {{ $label }}
            <x-font-awesome-icon name="chevron-down" class="h-3.5 w-3.5 opacity-60" />
        </button>
        <div
            x-show="open"
            x-cloak
            x-transition
            class="absolute bottom-full right-0 z-50 mb-1 min-w-[11rem] origin-bottom-right rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900"
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
