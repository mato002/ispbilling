@php

    $S = $ispSettingsShell ?? null;

@endphp



@if ($S)

    <section class="isp-settings-health grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="{{ __('System health summary') }}">

        <div class="pb-card isp-settings-panel overflow-hidden">

            <div class="border-b border-[var(--pb-border)] bg-slate-50/80 px-4 py-3 dark:bg-slate-900/40">

                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">System health</h3>

            </div>

            <ul class="divide-y divide-[var(--pb-border)] px-1 py-1">

                @foreach ($S['health'] as $row)

                    @php

                        $tone = $row['tone'] ?? 'info';

                        $dot = match ($tone) {

                            'ok' => 'pb-dot-ok',

                            'warn' => 'pb-dot-warn',

                            'bad' => 'pb-dot-bad',

                            default => 'pb-dot',

                        };

                    @endphp

                    <li class="flex items-start justify-between gap-3 px-3 py-2.5">

                        <div class="min-w-0">

                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $row['label'] }}</p>

                            <p class="mt-0.5 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $row['value'] }}</p>

                        </div>

                        <span class="pb-dot {{ $dot }} mt-1.5 shrink-0"></span>

                    </li>

                @endforeach

            </ul>

        </div>



        <div class="pb-card isp-settings-panel overflow-hidden">

            <div class="border-b border-[var(--pb-border)] px-4 py-3">

                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Integrations</h3>

            </div>

            <div class="space-y-3 px-4 py-3 text-sm">

                <div class="flex items-start justify-between gap-2">

                    <div>

                        <p class="font-semibold text-slate-800 dark:text-slate-100">M-Pesa Daraja</p>

                    </div>

                    @php

                        $mTone = $S['mpesa']['tone'];

                        $pill = match ($mTone) {

                            'ok' => 'pb-pill-ok',

                            'warn' => 'pb-pill-warn',

                            'bad' => 'pb-pill-bad',

                            default => 'pb-pill-info',

                        };

                    @endphp

                    <span class="pb-pill {{ $pill }} shrink-0 text-[10px]">{{ $S['mpesa']['label'] }}</span>

                </div>

                <div class="flex items-start justify-between gap-2 border-t border-dashed border-[var(--pb-border)] pt-3">

                    <div>

                        <p class="font-semibold text-slate-800 dark:text-slate-100">Email transport</p>

                    </div>

                    @php

                        $eTone = $S['smtp']['tone'];

                        $ep = match ($eTone) {

                            'ok' => 'pb-pill-ok',

                            'warn' => 'pb-pill-warn',

                            default => 'pb-pill-info',

                        };

                    @endphp

                    <span class="pb-pill {{ $ep }} shrink-0 text-[10px]">{{ $S['smtp']['short'] }}</span>

                </div>

                <div class="flex items-start justify-between gap-2 border-t border-dashed border-[var(--pb-border)] pt-3">

                    <div>

                        <p class="font-semibold text-slate-800 dark:text-slate-100">SMS channel</p>

                    </div>

                    @php

                        $sTone = $S['sms']['tone'];

                        $sp = match ($sTone) {

                            'ok' => 'pb-pill-ok',

                            'warn' => 'pb-pill-warn',

                            default => 'pb-pill-info',

                        };

                    @endphp

                    <span class="pb-pill {{ $sp }} shrink-0 text-[10px]">{{ $S['sms']['short'] }}</span>

                </div>

            </div>

        </div>



        <div class="pb-card isp-settings-panel overflow-hidden sm:col-span-2 xl:col-span-1">

            <div class="border-b border-[var(--pb-border)] px-4 py-3">

                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Security &amp; access</h3>

            </div>

            <dl class="space-y-2.5 px-4 py-3 text-sm">

                <div class="flex justify-between gap-2">

                    <dt class="text-slate-500 dark:text-slate-400">Active admins</dt>

                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $S['stats']['admin_users'] }}</dd>

                </div>

                <div class="flex justify-between gap-2">

                    <dt class="text-slate-500 dark:text-slate-400">MFA</dt>

                    <dd><span class="pb-pill pb-pill-warn text-[10px]">Planned</span></dd>

                </div>

                <div class="flex justify-between gap-2">

                    <dt class="text-slate-500 dark:text-slate-400">Last settings change</dt>

                    <dd class="text-right text-xs font-medium text-slate-700 dark:text-slate-200">

                        {{ $S['last_settings_human'] ?? 'Not recorded yet' }}

                    </dd>

                </div>

                <div class="flex justify-between gap-2">

                    <dt class="text-slate-500 dark:text-slate-400">IP allowlist</dt>

                    <dd class="text-right text-xs text-slate-600 dark:text-slate-300">All networks</dd>

                </div>

                <div class="flex justify-between gap-2">

                    <dt class="text-slate-500 dark:text-slate-400">Session policy</dt>

                    <dd class="text-right text-xs text-slate-600 dark:text-slate-300">Platform default</dd>

                </div>

                @if ($S['last_activity_summary'])

                    <div class="border-t border-dashed border-[var(--pb-border)] pt-2.5">

                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Latest activity</p>

                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($S['last_activity_summary'], 120) }}</p>

                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $S['last_activity_human'] }}</p>

                    </div>

                @endif

            </dl>

        </div>

    </section>

@endif

