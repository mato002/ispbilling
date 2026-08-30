@props([
    'title' => 'No records yet',
    'message' => 'No data available.',
    'ctaLabel' => null,
    'ctaHref' => null,
])

<div class="isp-empty-state">
    <div class="icon-wrap">
        {{ $icon ?? '' }}
    </div>
    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">{{ $message }}</p>
    @if($ctaLabel && $ctaHref)
        @php
            $ctaModal = preg_match('/\/(create|edit)(\/|$)/', $ctaHref);
        @endphp
        @if ($ctaModal)
            <x-form-modal-link :href="$ctaHref" class="mt-5">{{ $ctaLabel }}</x-form-modal-link>
        @else
            <a href="{{ $ctaHref }}" class="pb-btn pb-btn-primary mt-5">{{ $ctaLabel }}</a>
        @endif
    @endif
</div>
