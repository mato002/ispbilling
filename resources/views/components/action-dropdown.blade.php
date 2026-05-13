@props(['label' => 'Actions'])

<div x-data="{ open: false }" class="relative" @click.outside="open = false">
    <button type="button" @click="open = !open" class="pb-btn text-xs">
        {{ $label }}
        <svg class="h-3.5 w-3.5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m6 9 6 6 6-6"/>
        </svg>
    </button>
    <div x-show="open" x-cloak x-transition class="absolute right-0 z-40 mt-2 w-48 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
        {{ $slot }}
    </div>
</div>
