@props([
    'chips' => [],
])

@if (count($chips) > 0)
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
        @foreach ($chips as $chip)
            <a
                href="{{ $chip['url'] }}"
                data-turbo-frame="pb-main"
                class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-800 transition hover:border-indigo-300 hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200"
            >
                <span>{{ $chip['label'] }}</span>
                <span class="text-indigo-400" aria-hidden="true">×</span>
            </a>
        @endforeach
    </div>
@endif
