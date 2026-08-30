@extends('layouts.admin')

@section('title', 'ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php $S = $ispSettingsShell; @endphp

    <x-isp-settings-workspace hide-meta="true" :show-health="true">
        @if ($S)
            {{-- Mission metrics --}}
            <div class="isp-cc-hero pb-card isp-settings-panel overflow-hidden">
                <div class="isp-cc-hero__band"></div>
                <div class="relative px-4 py-5 sm:px-6 sm:py-6">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Workspace posture</p>
                            <h2 class="mt-2 text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl">Operational readiness</h2>
</div>
                        <div class="grid w-full max-w-xl shrink-0 grid-cols-2 gap-2 sm:grid-cols-4 lg:w-auto">
                            <div class="isp-cc-kpi">
                                <p class="isp-cc-kpi__label">Onboarding</p>
                                <p class="isp-cc-kpi__value text-indigo-700 dark:text-indigo-300">{{ $S['onboarding_percent'] }}%</p>
                            </div>
                            <div class="isp-cc-kpi">
                                <p class="isp-cc-kpi__label">Customers</p>
                                <p class="isp-cc-kpi__value">{{ $S['stats']['active_customers'] }}</p>
                            </div>
                            <div class="isp-cc-kpi">
                                <p class="isp-cc-kpi__label">Admins</p>
                                <p class="isp-cc-kpi__value">{{ $S['stats']['admin_users'] }}</p>
                            </div>
                            <div class="isp-cc-kpi">
                                <p class="isp-cc-kpi__label">Last change</p>
                                <p class="isp-cc-kpi__value text-sm font-bold leading-snug">{{ \Illuminate\Support\Str::limit($S['last_settings_human'] ?? '—', 14) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Live integration strip --}}
            <div class="isp-cc-live-strip pb-card isp-settings-panel overflow-hidden">
                <div class="border-b border-[var(--pb-border)] px-4 py-2.5 sm:px-5">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Live configuration</p>
                </div>
                <div class="grid grid-cols-1 divide-y divide-[var(--pb-border)] md:grid-cols-3 md:divide-x md:divide-y-0">
                    @php
                        $mTone = $S['mpesa']['tone'];
                        $mDot = $mTone === 'ok' ? 'pb-dot-ok' : ($mTone === 'bad' ? 'pb-dot-bad' : 'pb-dot-warn');
                    @endphp
                    <div class="isp-cc-live-cell">
                        <div class="flex items-center gap-2">
                            <span class="pb-dot {{ $mDot }}"></span>
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">M-Pesa / Daraja</span>
                        </div>
                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $S['mpesa']['label'] }}</p>
<a href="{{ route('isp.settings.payment') }}" class="isp-cc-live-cell__link">Open payments →</a>
                    </div>
                    @php
                        $eTone = $S['smtp']['tone'];
                        $eDot = $eTone === 'ok' ? 'pb-dot-ok' : 'pb-dot-warn';
                    @endphp
                    <div class="isp-cc-live-cell">
                        <div class="flex items-center gap-2">
                            <span class="pb-dot {{ $eDot }}"></span>
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Email transport</span>
                        </div>
                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $S['smtp']['label'] }}</p>
<a href="{{ route('isp.settings.notifications') }}" class="isp-cc-live-cell__link">Communications →</a>
                    </div>
                    @php
                        $sTone = $S['sms']['tone'] ?? 'warn';
                        $sDot = $sTone === 'ok' ? 'pb-dot-ok' : 'pb-dot-warn';
                    @endphp
                    <div class="isp-cc-live-cell">
                        <div class="flex items-center gap-2">
                            <span class="pb-dot {{ $sDot }}"></span>
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">SMS channel</span>
                        </div>
                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $S['sms']['label'] }}</p>
<a href="{{ route('isp.settings.notifications') }}" class="isp-cc-live-cell__link">SMS settings →</a>
                    </div>
                </div>
            </div>

            @if ($S['mpesa']['tone'] === 'bad' || $S['smtp']['tone'] !== 'ok' || ($S['stats']['routers_total'] ?? 0) === 0)
                <div class="space-y-3">
                    <p class="px-1 text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Setup intelligence</p>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @if ($S['mpesa']['tone'] === 'bad')
                            <div class="isp-cc-setup-card isp-cc-setup-card--danger">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">M-Pesa API not configured</p>
<a href="{{ route('isp.settings.payment') }}" class="isp-cc-setup-card__cta">Configure Daraja</a>
                            </div>
                        @endif
                        @if ($S['smtp']['tone'] !== 'ok')
                            <div class="isp-cc-setup-card isp-cc-setup-card--warn">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">SMTP credentials missing or dev mode</p>
<span class="isp-cc-setup-card__cta isp-cc-setup-card__cta--muted">Environment-level</span>
                            </div>
                        @endif
                        @if (($S['stats']['routers_total'] ?? 0) === 0)
                            <div class="isp-cc-setup-card">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">No edge routers linked</p>
<a href="{{ route('isp.routers.index') }}" class="isp-cc-setup-card__cta">Open routers</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @else
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                <span class="font-semibold">Limited workspace context.</span>
                <span class="text-amber-900/90 dark:text-amber-200/90"> Live metrics and integration health load when your account is linked to an ISP workspace.</span>
            </div>
        @endif

        {{-- Module map by domain --}}
        <div class="space-y-8">
            <section class="isp-cc-section">
                <header class="isp-cc-section__head">
                    <h3 class="isp-cc-section__title">Business</h3>
</header>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <a href="{{ route('isp.settings.company') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--indigo"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Company</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Company information</h4>
</div>
                            <span class="pb-pill pb-pill-info text-[10px]">Profile</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.company') }}#branding" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--violet"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Brand</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Branding &amp; portals</h4>
</div>
                            <span class="pb-pill pb-pill-ok text-[10px]">Configure</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.branches') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--cyan"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Locations</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Branches</h4>
</div>
                            <span class="pb-pill pb-pill-info text-[10px]">Map</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.custom-fields') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--slate"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">ERP</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Custom fields</h4>
</div>
                            <span class="pb-pill pb-pill-info text-[10px]">Extend</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.forms.index') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5 md:col-span-2">
                        <span class="isp-cc-module__accent isp-cc-module__accent--violet"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Forms</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Form controls</h4>
</div>
                            <span class="pb-pill pb-pill-info shrink-0 text-[10px]">Configure</span>
                        </div>
                    </a>
                </div>
            </section>

            <section class="isp-cc-section">
                <header class="isp-cc-section__head">
                    <h3 class="isp-cc-section__title">Payments</h3>
</header>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <a href="{{ route('isp.settings.payment') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--emerald"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Safaricom</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">M-Pesa Daraja</h4>
</div>
                            @if ($S)
                                @php
                                    $tone = $S['mpesa']['tone'];
                                    $pill = match ($tone) {
                                        'ok' => 'pb-pill-ok',
                                        'warn' => 'pb-pill-warn',
                                        'bad' => 'pb-pill-bad',
                                        default => 'pb-pill-info',
                                    };
                                @endphp
                                <span class="pb-pill {{ $pill }} shrink-0 text-[10px]">{{ $S['mpesa']['short'] }}</span>
                            @endif
                        </div>
                    </a>
                    <a href="{{ route('isp.payment-settings.index') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--violet"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Catalog</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Payment methods</h4>
</div>
                            <span class="pb-pill pb-pill-info text-[10px]">Manage</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.billing') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--cyan"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Finance</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Tax &amp; invoicing</h4>
</div>
                            <span class="pb-pill pb-pill-cyan text-[10px]">Policy</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.payment-gateways') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--amber"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Legacy</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">JSON gateway store</h4>
</div>
                            <span class="pb-pill pb-pill-warn text-[10px]">Migrate</span>
                        </div>
                    </a>
                </div>
            </section>

            <section class="isp-cc-section">
                <header class="isp-cc-section__head">
                    <h3 class="isp-cc-section__title">Communications</h3>
</header>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <a href="{{ route('isp.settings.notifications') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--sky"></span>
                        <div class="relative flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Channels</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Notification preferences</h4>
</div>
                            @if ($S)
                                @php
                                    $tone = $S['sms']['tone'] ?? 'info';
                                    $pill = match ($tone) {
                                        'ok' => 'pb-pill-ok',
                                        'warn' => 'pb-pill-warn',
                                        default => 'pb-pill-info',
                                    };
                                @endphp
                                <span class="pb-pill {{ $pill }} shrink-0 self-start text-[10px]">{{ $S['sms']['short'] }}</span>
                            @endif
                        </div>
                    </a>
                    <a href="{{ route('isp.notifications.index') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--indigo"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Inbox</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Staff alerts</h4>
</div>
                            <span class="pb-pill pb-pill-info text-[10px]">Open</span>
                        </div>
                    </a>
                </div>
            </section>

            <section class="isp-cc-section">
                <header class="isp-cc-section__head">
                    <h3 class="isp-cc-section__title">Users &amp; security</h3>
</header>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <a href="{{ route('isp.settings.users') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--rose"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Access</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">User management</h4>
</div>
                            @if ($S)
                                <span class="pb-pill pb-pill-info shrink-0 text-[10px]">{{ $S['stats']['admin_users'] }} admins</span>
                            @endif
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.roles') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--slate"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Governance</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Roles &amp; permissions</h4>
</div>
                            <span class="pb-pill pb-pill-ok text-[10px]">Policy</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.security') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--rose"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Protection</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Security preferences</h4>
</div>
                            <span class="pb-pill pb-pill-warn text-[10px]">Policy</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.logs.activity.index') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5 md:col-span-2">
                        <span class="isp-cc-module__accent isp-cc-module__accent--slate"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Governance</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Audit logs</h4>
</div>
                            <span class="pb-pill pb-pill-ok text-[10px]">Review</span>
                        </div>
                    </a>
                </div>
            </section>

            <section class="isp-cc-section">
                <header class="isp-cc-section__head">
                    <h3 class="isp-cc-section__title">Network defaults</h3>
</header>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <a href="{{ route('isp.settings.router-defaults') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--indigo"></span>
                        <div class="relative">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Edge</p>
                            <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Router defaults</h4>
</div>
                    </a>
                    <a href="{{ route('isp.settings.pppoe-defaults') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--cyan"></span>
                        <div class="relative">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Access</p>
                            <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">PPPoE defaults</h4>
</div>
                    </a>
                    <a href="{{ route('isp.settings.hotspot') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--amber"></span>
                        <div class="relative">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Guest</p>
                            <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Hotspot settings</h4>
</div>
                    </a>
                </div>
            </section>

            <section class="isp-cc-section">
                <header class="isp-cc-section__head">
                    <h3 class="isp-cc-section__title">System</h3>
</header>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <a href="{{ route('isp.settings.backups') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--slate"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Reliability</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Backups</h4>
</div>
                            <span class="pb-pill pb-pill-info text-[10px]">Configure</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.maintenance') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--amber"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Operations</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Maintenance</h4>
</div>
                            <span class="pb-pill pb-pill-warn text-[10px]">Portal</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.settings.api-integrations') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--violet"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Developers</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">API integrations</h4>
</div>
                            <span class="pb-pill pb-pill-info text-[10px]">Keys</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.platform-subscription.index') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5">
                        <span class="isp-cc-module__accent isp-cc-module__accent--indigo"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Platform</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">PrimeBilling subscription</h4>
</div>
                            <span class="pb-pill pb-pill-cyan text-[10px]">Plan</span>
                        </div>
                    </a>
                    <a href="{{ route('isp.logs.router-actions.index') }}" class="isp-cc-module pb-card isp-settings-panel isp-settings-panel--lift block p-4 sm:p-5 md:col-span-2">
                        <span class="isp-cc-module__accent isp-cc-module__accent--slate"></span>
                        <div class="relative flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Telemetry</p>
                                <h4 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Router action logs</h4>
</div>
                            <span class="pb-pill pb-pill-info text-[10px]">Logs</span>
                        </div>
                    </a>
                </div>
            </section>
        </div>
    </x-isp-settings-workspace>
@endsection
