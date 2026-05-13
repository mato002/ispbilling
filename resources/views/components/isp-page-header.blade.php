@props([
    'title',
    'description' => null,
    'module' => 'ISP Operations',
    'primaryLabel' => null,
    'primaryHref' => null,
])

<div class="isp-page-header">
    <div class="min-w-0">
        <span class="isp-page-kicker">{{ $module }}</span>
        <h1 class="mt-2 text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">{{ $title }}</h1>
        @if($description)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
        @endif
    </div>
    <div class="flex flex-wrap items-center justify-end gap-2">
        @isset($actions)
            {{ $actions }}
        @endisset
        @if($primaryLabel && $primaryHref)
            <a href="{{ $primaryHref }}" class="pb-btn pb-btn-primary">{{ $primaryLabel }}</a>
        @endif
    </div>
</div>
