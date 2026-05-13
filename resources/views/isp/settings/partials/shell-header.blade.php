@php
    /** @var array|null $ispSettingsShell */
    $S = $ispSettingsShell ?? null;
@endphp

<div class="isp-settings-shell-header">
    <div class="isp-settings-shell-header__inner">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <nav class="flex flex-wrap items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400" aria-label="Breadcrumb">
                    <a href="{{ route('isp.settings.index') }}" class="rounded-md px-1.5 py-0.5 text-indigo-600 hover:bg-indigo-500/10 hover:text-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-500/15 dark:hover:text-indigo-200">Settings</a>
                    <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">/</span>
                    <span class="text-slate-700 dark:text-slate-200">Control center</span>
                </nav>
                <span class="isp-page-kicker mt-3 inline-flex">Operations</span>
                <h1 class="mt-2 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
                    ISP System Settings
                </h1>
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Configure company operations, payments, notifications, user access, and network preferences.
                </p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0 lg:justify-end">
                @if ($S)
                    @foreach ($S['badges'] as $badge)
                        @php
                            $tone = $badge['tone'] ?? 'info';
                            $pill = match ($tone) {
                                'ok' => 'pb-pill-ok',
                                'warn' => 'pb-pill-warn',
                                'bad' => 'pb-pill-bad',
                                default => 'pb-pill-info',
                            };
                            $dot = match ($tone) {
                                'ok' => 'pb-dot-ok',
                                'warn' => 'pb-dot-warn',
                                'bad' => 'pb-dot-bad',
                                default => 'pb-dot',
                            };
                            $pulse = ! empty($badge['pulse']);
                        @endphp
                        <span class="isp-settings-status-chip pb-pill {{ $pill }}">
                            @if ($pulse)
                                <span class="pb-pulse-dot shrink-0" aria-hidden="true"></span>
                            @else
                                <span class="pb-dot {{ $dot }} shrink-0"></span>
                            @endif
                            {{ $badge['label'] }}
                        </span>
                    @endforeach
                @else
                    <span class="isp-settings-status-chip pb-pill pb-pill-info">
                        <span class="pb-dot shrink-0" aria-hidden="true"></span>
                        Secure Workspace
                    </span>
                    <span class="isp-settings-status-chip pb-pill pb-pill-warn">
                        <span class="pb-dot pb-dot-warn shrink-0" aria-hidden="true"></span>
                        Shell data unavailable
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
