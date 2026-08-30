@props(['title' => null, 'description' => null])

<section class="pb-card p-4 sm:p-5">
    @if($title)
        <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ $title }}</h2>
    @endif
    <div class="{{ $title ? 'mt-4' : '' }}">
        {{ $slot }}
    </div>
</section>
