@props([
    'metaTitle' => null,
    'metaDescription' => null,
    'hideMeta' => false,
    'contentClass' => '',
])

<div class="isp-settings-layout grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,260px)_minmax(0,1fr)_minmax(0,280px)]">
    @include('isp.settings.partials.nav')

    <div @class(['min-w-0 space-y-5 xl:col-span-1', $contentClass])>
        @if (! $hideMeta && $metaTitle)
            <div class="pb-card isp-settings-panel isp-settings-meta-panel px-4 py-3 sm:px-5 sm:py-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Current section</p>
                <h2 class="mt-1 text-lg font-bold tracking-tight text-slate-900 dark:text-white">{{ $metaTitle }}</h2>
                @if ($metaDescription)
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $metaDescription }}</p>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>

    @include('isp.settings.partials.health-sidebar')
</div>
