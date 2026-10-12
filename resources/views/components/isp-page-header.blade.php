@props([
    'title',
    'description' => null,
    'module' => 'ISP Operations',
    'primaryLabel' => null,
    'primaryHref' => null,
    'primaryEvent' => null,
    /** When null, opens create/edit URLs in the modal form shell (jana-prints pattern). */
    'primaryModal' => null,
])

<div class="isp-page-header">
    <div class="min-w-0">
        <span class="isp-page-kicker">{{ $module }}</span>
        <h1 class="mt-2 text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">{{ $title }}</h1>
        {{ $header ?? '' }}
    </div>
    <div class="flex flex-wrap items-center justify-end gap-2">
        @isset($actions)
            {{ $actions }}
        @endisset
        @if($primaryLabel && $primaryEvent)
            <button type="button" class="pb-btn pb-btn-primary" @click="$dispatch(@js($primaryEvent))">{{ $primaryLabel }}</button>
        @elseif($primaryLabel && $primaryHref)
            @php
                $openInModal = $primaryModal ?? preg_match('/\/(create|edit)(\/|$)/', $primaryHref);
            @endphp
            @if ($openInModal)
                <x-form-modal-link :href="$primaryHref">{{ $primaryLabel }}</x-form-modal-link>
            @else
                <a href="{{ $primaryHref }}" class="pb-btn pb-btn-primary">{{ $primaryLabel }}</a>
            @endif
        @endif
    </div>
</div>
