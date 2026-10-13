@props([
    /** @var list<array{label: string, href: string, modal: bool}> $items */
    'items' => [],
])

@if (count($items) > 0)
    <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false">
        <button
            type="button"
            @click="open = !open"
            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-br from-indigo-500 to-cyan-500 px-3 py-2 text-xs font-semibold text-white shadow-[0_8px_18px_-10px_rgba(99,102,241,0.6)] hover:from-indigo-600 hover:to-cyan-600"
            aria-haspopup="true"
            :aria-expanded="open"
        >
            <x-font-awesome-icon name="add" class="h-4 w-4" />
            <span class="hidden sm:inline">{{ __('Create') }}</span>
        </button>
        <div
            x-show="open"
            x-cloak
            x-transition
            class="absolute right-0 z-50 mt-2 w-52 origin-top-right rounded-xl border border-slate-200 bg-white py-1 shadow-xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900"
        >
            @foreach ($items as $item)
                @if ($item['modal'])
                    <a
                        href="{{ $item['href'] }}"
                        data-pb-modal-open
                        class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        {{ $item['label'] }}
                    </a>
                @else
                    <a
                        href="{{ $item['href'] }}"
                        data-turbo-frame="pb-main"
                        class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif
