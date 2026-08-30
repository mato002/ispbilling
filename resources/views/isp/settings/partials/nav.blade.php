@php
    $S = $ispSettingsShell ?? null;
    $ispIdLabel = $S ? 'ISP-'.str_pad((string) $S['isp']->id, 6, '0', STR_PAD_LEFT) : '';
@endphp

@php
    $navItem = function (string $href, string $label, string $routePattern, ?string $icon = null) {
        $active = request()->routeIs($routePattern);
        $base = 'isp-settings-nav__link group relative flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium transition-colors';
        $state = $active
            ? 'isp-settings-nav__link--active text-slate-900 dark:text-white shadow-sm'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-white';
        $iconWrap = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-200/80 bg-white text-slate-500 transition group-hover:border-indigo-200 group-hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400 dark:group-hover:border-indigo-500/40 dark:group-hover:text-indigo-300';
        if ($active) {
            $iconWrap .= ' border-indigo-200/90 bg-indigo-50 text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200';
        }
        echo '<a href="'.e($href).'" data-turbo-frame="pb-main" data-turbo-action="advance" class="'.$base.' '.$state.'">';
        echo '<span class="'.$iconWrap.'">'.$icon.'</span>';
        echo '<span class="min-w-0 truncate">'.e($label).'</span>';
        echo '</a>';
    };

    $navSoon = function (string $label, string $iconSvg) {
        $base = 'isp-settings-nav__link flex cursor-not-allowed items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-slate-400 opacity-70 dark:text-slate-500';
        $iconWrap = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-dashed border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-900/40';
        echo '<span class="'.$base.'" title="Coming soon">';
        echo '<span class="'.$iconWrap.'">'.$iconSvg.'</span>';
        echo '<span class="min-w-0 truncate">'.e($label).'</span>';
        echo '</span>';
    };

    $iBuilding = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path stroke-linecap="round" d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01M13 15v.01M13 18v.01"/></svg>';
    $iPalette = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
    $iMap = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>';
    $iCard = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>';
    $iReceipt = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l2 2 4-4M7 7h.01M17 7h.01M7 11h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
    $iUsers = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
    $iShield = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
    $iBell = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>';
    $iMail = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
    $iDevice = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>';
    $iServer = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>';
    $iCog = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
    $iKey = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>';
    $iClipboard = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>';
    $iGauge = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>';
@endphp

<div
    class="isp-settings-nav isp-settings-nav-shell pb-card min-w-0 overflow-hidden"
    x-data="{ open: false }"
>
    <div class="flex items-center justify-between gap-2 border-b border-[var(--pb-border)] px-3 py-2.5 lg:hidden">
        <span class="text-[11px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Sections</span>
        <button
            type="button"
            class="pb-btn !py-1.5 !px-3 !text-xs"
            @click="open = !open"
            :aria-expanded="open.toString()"
        >
            <span x-text="open ? 'Hide' : 'Show'"></span>
        </button>
    </div>
    <div class="isp-settings-nav__scroll px-2 py-3 lg:block" :class="open ? 'block' : 'hidden lg:block'">
        @if ($S)
            <div class="mb-3 px-2">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Workspace</p>
                <p class="mt-0.5 truncate text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $S['isp']->company_name }}</p>
                <p class="text-[11px] font-mono text-slate-500 dark:text-slate-400">{{ $ispIdLabel }}</p>
            </div>
        @endif

        <p class="isp-settings-nav__group-label">Overview</p>
        {!! $navItem(route('isp.settings.index'), 'Operations hub', 'isp.settings.index', $iGauge) !!}

        <p class="isp-settings-nav__group-label">Business</p>
        {!! $navItem(route('isp.settings.company'), 'Company information', 'isp.settings.company*', $iBuilding) !!}
        {!! $navItem(route('isp.settings.company').'#branding', 'Branding & logo', 'isp.settings.company*', $iPalette) !!}
        {!! $navItem(route('isp.settings.branches'), 'Branches', 'isp.settings.branches*', $iMap) !!}
        {!! $navItem(route('isp.settings.custom-fields'), 'Custom fields (ERP)', 'isp.settings.custom-fields*', $iClipboard) !!}
        {!! $navItem(route('isp.settings.forms.index'), 'Form controls', 'isp.settings.forms.*', $iGauge) !!}

        <p class="isp-settings-nav__group-label">Payments</p>
        {!! $navItem(route('isp.settings.payment'), 'M-Pesa Daraja', 'isp.settings.payment*', $iCard) !!}
        {!! $navItem(route('isp.settings.billing'), 'Tax &amp; invoicing', 'isp.settings.billing*', $iReceipt) !!}
        {!! $navItem(route('isp.payment-settings.index'), 'Payment methods', 'isp.payment-settings.*', $iKey) !!}
        {!! $navItem(route('isp.settings.payment-gateways'), 'Legacy gateways', 'isp.settings.payment-gateways*', $iServer) !!}

        <p class="isp-settings-nav__group-label">Users &amp; security</p>
        {!! $navItem(route('isp.settings.users'), 'User management', 'isp.settings.users*', $iUsers) !!}
        {!! $navItem(route('isp.settings.roles'), 'Roles &amp; permissions', 'isp.settings.roles*', $iShield) !!}
        {!! $navItem(route('isp.settings.security'), 'Security preferences', 'isp.settings.security*', $iShield) !!}
        {!! $navItem(route('isp.logs.activity.index'), 'Audit logs', 'isp.logs.activity*', $iClipboard) !!}

        <p class="isp-settings-nav__group-label">Communications</p>
        {!! $navItem(route('isp.settings.notifications'), 'Notification preferences', 'isp.settings.notifications*', $iBell) !!}
        {!! $navItem(route('isp.notifications.index'), 'Alerts inbox', 'isp.notifications.*', $iMail) !!}

        <p class="isp-settings-nav__group-label">Network</p>
        {!! $navItem(route('isp.settings.router-defaults'), 'Router defaults', 'isp.settings.router-defaults*', $iDevice) !!}
        {!! $navItem(route('isp.settings.pppoe-defaults'), 'PPPoE defaults', 'isp.settings.pppoe-defaults*', $iDevice) !!}
        {!! $navItem(route('isp.settings.hotspot'), 'Hotspot settings', 'isp.settings.hotspot*', $iDevice) !!}

        <p class="isp-settings-nav__group-label">System</p>
        {!! $navItem(route('isp.settings.backups'), 'Backups', 'isp.settings.backups*', $iCog) !!}
        {!! $navItem(route('isp.settings.maintenance'), 'Maintenance', 'isp.settings.maintenance*', $iCog) !!}
        {!! $navItem(route('isp.settings.api-integrations'), 'API integrations', 'isp.settings.api-integrations*', $iKey) !!}
        {!! $navItem(route('isp.platform-subscription.index'), 'PrimeBilling subscription', 'isp.platform-subscription.*', $iReceipt) !!}
        {!! $navItem(route('isp.logs.router-actions.index'), 'Router action logs', 'isp.logs.router-actions*', $iClipboard) !!}
    </div>
</div>
