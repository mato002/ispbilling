@props([
    'title' => '',
    'value' => 0,
    'subtext' => '',
    'comparison' => null,
    'trend' => 'neutral',
    'badge' => null,
    'iconBg' => 'bg-blue-500',
    'icon',
])

<article class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
    <div class="flex items-start justify-between gap-4">
        <div class="flex items-start gap-3 min-w-0">
            <div class="h-11 w-11 rounded-lg {{ $iconBg }} text-white flex items-center justify-center shrink-0">
                {{ $icon }}
            </div>
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $title }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $value }}</p>
                @if($subtext)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $subtext }}</p>
                @endif
            </div>
        </div>
        <div class="text-right shrink-0">
            @if($comparison !== null)
                <p class="text-xs font-semibold {{ $trend === 'up' ? 'text-emerald-600 dark:text-emerald-400' : ($trend === 'down' ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400') }}">
                    {{ $comparison }}
                </p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">vs last month</p>
            @endif
            @if($badge)
                <span class="inline-flex mt-2 px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    {{ $badge }}
                </span>
            @endif
        </div>
    </div>
</article>
