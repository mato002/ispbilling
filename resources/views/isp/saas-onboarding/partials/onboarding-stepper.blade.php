@php
    $current = $current ?? min(4, max(1, (int) ($isp->saas_onboarding_step ?? 1)));
    $steps = [1 => 'Plan', 2 => 'Business', 3 => 'Network', 4 => 'M-Pesa'];
@endphp

<nav class="mb-8" aria-label="Onboarding progress">
    <ol class="flex items-center justify-between gap-1 sm:gap-2">
        @foreach ($steps as $n => $label)
            @php
                $isComplete = $current > $n;
                $isActive = $current === $n;
                $isUpcoming = $current < $n;
            @endphp
            <li class="flex min-w-0 flex-1 items-center">
                <div class="flex min-w-0 flex-col items-center gap-2 text-center sm:flex-row sm:gap-3 sm:text-left">
                    <span
                        @class([
                            'relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold transition-all',
                            'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 ring-4 ring-indigo-600/20' => $isActive,
                            'bg-emerald-600 text-white shadow-md shadow-emerald-600/25' => $isComplete,
                            'border-2 border-slate-200 bg-white text-slate-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-500' => $isUpcoming,
                        ])
                        @if ($isActive) aria-current="step" @endif
                    >
                        @if ($isActive)
                            <span class="absolute inset-0 animate-ping rounded-full bg-indigo-400 opacity-30"></span>
                        @endif
                        @if ($isComplete)
                            <svg class="relative h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        @else
                            <span class="relative">{{ $n }}</span>
                        @endif
                    </span>
                    <span @class([
                        'hidden truncate text-xs font-semibold sm:block sm:text-sm',
                        'text-indigo-700 dark:text-indigo-300' => $isActive,
                        'text-emerald-700 dark:text-emerald-300' => $isComplete,
                        'text-slate-400 dark:text-slate-500' => $isUpcoming,
                    ])>{{ $label }}</span>
                </div>
                @if (! $loop->last)
                    <div
                        @class([
                            'mx-1 hidden h-0.5 flex-1 rounded-full sm:block sm:mx-2',
                            'bg-emerald-500' => $current > $n + 1,
                            'bg-gradient-to-r from-emerald-500 to-indigo-400' => $current === $n + 1,
                            'bg-slate-200 dark:bg-slate-700' => $current <= $n,
                        ])
                        aria-hidden="true"
                    ></div>
                @endif
            </li>
        @endforeach
    </ol>
    <p class="mt-3 text-center text-xs text-slate-500 dark:text-slate-400 sm:hidden">
        Step {{ $current }} of 4 · {{ $steps[$current] }}
    </p>
</nav>
