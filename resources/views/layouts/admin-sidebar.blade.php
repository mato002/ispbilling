@php
    $user = Auth::user();
    $isSuper = $user->isSuperAdmin();
    $isIsp = $user->isIspAdmin() || $user->isIspStaff();
    $ispName = optional($user->isp)->name ?? ($isSuper ? 'PrimeBilling Admin' : 'Your ISP');
    $homeRoute = $isSuper ? route('admin.dashboard') : route('isp.dashboard');

    /**
     * Helper closures for active-state matching.
     * - $active(routes...) returns true if the current route name matches any of the given patterns
     */
    $active = function (...$patterns) {
        foreach ($patterns as $p) {
            if (request()->routeIs($p)) return true;
        }
        return false;
    };
@endphp

<aside
    :class="[
        $store.sidebar.collapsed ? 'md:!w-16' : '',
        $store.sidebar.mobileOpen ? '!translate-x-0' : '',
    ]"
    class="pb-sidebar fixed top-0 bottom-0 left-0 z-40 flex w-64 -translate-x-full flex-col transition-all duration-300 ease-in-out md:translate-x-0"
>
    {{-- Brand (ISP logo + workspace title when configured) --}}
    @php
        $ib = $ispBranding ?? null;
        $brandTitle = $ib['workspace_title'] ?? 'PrimeBilling';
        $brandTag = $ib['workspace_tagline'] ?? $ispName;
        $logoUrl = $ib['logo_url'] ?? null;
    @endphp
    <a href="{{ $homeRoute }}"
       class="pb-sidebar-brand group shrink-0 flex h-20 items-center border-b border-white/5 no-underline outline-none transition-colors hover:bg-white/5 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-400/50"
       :class="$store.sidebar.collapsed ? 'justify-center px-2' : 'gap-3 px-4'"
       title="{{ $brandTitle }}">
        <span
            class="pb-sidebar-brand-mark h-9 w-9 shrink-0 rounded-xl border border-white/10 bg-slate-900/40 shadow-[0_8px_18px_-8px_rgba(99,102,241,0.55),inset_0_1px_0_rgba(255,255,255,0.12)] bg-cover bg-center"
            @if ($logoUrl)
                style="background-image: url('{{ e($logoUrl) }}');"
            @else
                style="background-image: url('{{ asset('images/primebilling-mark.svg') }}'), linear-gradient(135deg, #6366f1 0%, #7c3aed 50%, #22d3ee 100%);"
            @endif
            aria-hidden="true"></span>
        <span class="flex min-w-0 flex-col leading-tight text-left" x-show="!$store.sidebar.collapsed" x-cloak>
            <span class="truncate text-sm font-bold tracking-tight text-white">{{ $brandTitle }}</span>
            <span class="truncate text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $brandTag }}</span>
        </span>
    </a>

    {{-- Scroll area --}}
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-4">
        <nav class="space-y-5"
             :class="$store.sidebar.collapsed ? 'px-2' : 'px-3'">

            @if($isSuper)
                {{-- ============= SUPER ADMIN ============= --}}
                <div>
                    <div class="pb-sidebar-section-label mb-2" x-show="!$store.sidebar.collapsed">Platform</div>
                    <div class="space-y-1">
                        <a href="{{ route('admin.dashboard') }}"
                           class="pb-nav-item {{ $active('admin.dashboard') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Dashboard</span>
                        </a>
                        <a href="{{ route('admin.isps.index') }}"
                           class="pb-nav-item {{ $active('admin.isps.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/></svg>
                            <span x-show="!$store.sidebar.collapsed">All ISPs</span>
                        </a>
                        <a href="{{ route('admin.users.index') }}"
                           class="pb-nav-item {{ $active('admin.users.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Operators</span>
                        </a>
                        <a href="{{ route('admin.helpdesk.index') }}"
                           class="pb-nav-item {{ $active('admin.helpdesk.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                            <span x-show="!$store.sidebar.collapsed">ISP helpdesk</span>
                        </a>
                        <a href="{{ route('admin.announcements.index') }}"
                           class="pb-nav-item {{ $active('admin.announcements.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Announcements</span>
                        </a>
                        <a href="{{ route('admin.integrations.index') }}"
                           class="pb-nav-item {{ $active('admin.integrations.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Integrations</span>
                        </a>
                        <a href="{{ route('admin.finance.index') }}"
                           class="pb-nav-item {{ $active('admin.finance.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Finance</span>
                        </a>
                        <a href="{{ route('admin.platform-saas-plans.index') }}"
                           class="pb-nav-item {{ $active('admin.platform-saas-plans.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M6 7v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7"/><path d="M9 21h6"/></svg>
                            <span x-show="!$store.sidebar.collapsed">SaaS plans</span>
                        </a>
                        <a href="{{ route('admin.audit.activity') }}"
                           class="pb-nav-item {{ $active('admin.audit.activity', 'admin.audit.activity.show') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Global audit</span>
                        </a>
                        <a href="{{ route('admin.audit.security') }}"
                           class="pb-nav-item {{ $active('admin.audit.security') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><circle cx="12" cy="10" r="3"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Security</span>
                        </a>
                        <a href="{{ route('admin.network.index') }}"
                           class="pb-nav-item {{ $active('admin.network.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="9" rx="2"/><path d="M7 7v4M17 7v4M5 7h14"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Network NOC</span>
                        </a>
                        <a href="{{ route('admin.system-health.index') }}"
                           class="pb-nav-item {{ $active('admin.system-health.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4"/><path d="M6 12H2"/><path d="M12 6V2"/><path d="M12 22v-4"/><circle cx="12" cy="12" r="3"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Fleet health</span>
                        </a>
                        <a href="{{ route('admin.intelligence.index') }}"
                           class="pb-nav-item {{ $active('admin.intelligence.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l3-3 4 4 5-5"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Intelligence</span>
                        </a>
                        <a href="{{ route('admin.marketing.index') }}"
                           class="pb-nav-item {{ $active('admin.marketing.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12H4z"/><path d="M8 20h8"/><path d="M12 16v4"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Marketing</span>
                        </a>
                        <a href="{{ route('admin.promo-codes.index') }}"
                           class="pb-nav-item {{ $active('admin.promo-codes.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H4v10h16v-5"/><path d="M16 12h2"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Promo codes</span>
                        </a>
                    </div>
                </div>
            @elseif($isIsp)

                {{-- ============= MAIN ============= --}}
                <div>
                    <div class="pb-sidebar-section-label mb-2" x-show="!$store.sidebar.collapsed">Main</div>
                    <div class="space-y-1">
                        <a href="{{ route('isp.dashboard') }}"
                           class="pb-nav-item {{ $active('isp.dashboard') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Dashboard' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Dashboard</span>
                        </a>
                        <a href="{{ route('isp.customers.index') }}"
                           class="pb-nav-item {{ $active('isp.customers.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Customers' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Customers</span>
                        </a>
                        <a href="{{ route('isp.subscriptions.index') }}"
                           class="pb-nav-item {{ $active('isp.subscriptions.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Subscriptions' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><polyline points="21 4 21 10 15 10"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Subscriptions</span>
                        </a>
                        <a href="{{ route('isp.plans.index') }}"
                           class="pb-nav-item {{ $active('isp.plans.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Plans' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Plans &amp; Vouchers</span>
                        </a>
                    </div>
                </div>

                {{-- ============= NETWORK ============= --}}
                <div>
                    <div class="pb-sidebar-section-label mb-2" x-show="!$store.sidebar.collapsed">Network</div>
                    <div class="space-y-1">
                        <a href="{{ route('isp.routers.index') }}"
                           class="pb-nav-item {{ $active('isp.routers.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Routers' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="9" rx="2"/><path d="M7 7v4M17 7v4M5 7h14"/><circle cx="8" cy="16" r="0.6" fill="currentColor"/><circle cx="12" cy="16" r="0.6" fill="currentColor"/><circle cx="16" cy="16" r="0.6" fill="currentColor"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Routers</span>
                        </a>
                        <a href="{{ route('isp.hotspot-servers.index') }}"
                           class="pb-nav-item {{ $active('isp.hotspot-servers.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Hotspot servers' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14 0"/><path d="M2 9.32a16 16 0 0 1 20 0"/><path d="M8.5 16.43a6 6 0 0 1 7 0"/><circle cx="12" cy="20" r="1" fill="currentColor"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Hotspot servers</span>
                        </a>
                        <a href="{{ route('isp.user-connections.index') }}"
                           class="pb-nav-item {{ $active('isp.user-connections.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'PPPoE / Connections' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.72"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.72-1.72"/></svg>
                            <span x-show="!$store.sidebar.collapsed">PPPoE &amp; Connections</span>
                        </a>
                        <a href="{{ route('isp.active-users.index') }}"
                           class="pb-nav-item {{ $active('isp.active-users.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Active users' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M2 12a10 10 0 0 1 20 0"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Active users</span>
                        </a>
                        <a href="{{ route('isp.sessions.index') }}"
                           class="pb-nav-item {{ $active('isp.sessions.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Sessions' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3 9 4-18 3 9h4"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Live sessions</span>
                        </a>
                        <a href="{{ route('isp.ip-pools.index') }}"
                           class="pb-nav-item {{ $active('isp.ip-pools.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'IP pools' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.27 6.96 8.73 5.05 8.73-5.05"/><path d="M12 22.08V12"/></svg>
                            <span x-show="!$store.sidebar.collapsed">IP pools</span>
                        </a>
                        <a href="{{ route('isp.bandwidth-profiles.index') }}"
                           class="pb-nav-item {{ $active('isp.bandwidth-profiles.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Bandwidth profiles' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12c-2.5 0-3.5-3-6-3s-3.5 3-6 3-3.5-3-6-3-3.5 3-2 3"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Bandwidth profiles</span>
                        </a>
                    </div>
                </div>

                {{-- ============= OPERATIONS ============= --}}
                <div>
                    <div class="pb-sidebar-section-label mb-2" x-show="!$store.sidebar.collapsed">Operations</div>
                    <div class="space-y-1">
                        <a href="{{ route('isp.invoices.index') }}"
                           class="pb-nav-item {{ $active('isp.invoices.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Invoices' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Invoices</span>
                        </a>
                        <a href="{{ route('isp.payments.index') }}"
                           class="pb-nav-item {{ $active('isp.payments.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Payments' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Payments</span>
                        </a>
                        <a href="{{ route('isp.vouchers.index') }}"
                           class="pb-nav-item {{ $active('isp.vouchers.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Vouchers' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V7H4v10h16v-5"/><path d="M16 12h2"/><path d="M8 7v10"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Vouchers</span>
                        </a>
                        <a href="{{ route('isp.tickets.index') }}"
                           class="pb-nav-item {{ $active('isp.tickets.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Tickets' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Support tickets</span>
                        </a>
                        <a href="{{ route('isp.helpdesk.index') }}"
                           class="pb-nav-item {{ $active('isp.helpdesk.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'PrimeBilling helpdesk' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            <span x-show="!$store.sidebar.collapsed">PrimeBilling helpdesk</span>
                        </a>
                    </div>
                </div>

                {{-- ============= ANALYTICS ============= --}}
                <div>
                    <div class="pb-sidebar-section-label mb-2" x-show="!$store.sidebar.collapsed">Analytics</div>
                    <div class="space-y-1">
                        <a href="{{ route('isp.reports.index') }}"
                           class="pb-nav-item {{ $active('isp.reports.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Reports' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l3-3 4 4 5-5"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Reports</span>
                        </a>
                        <a href="{{ route('isp.reports.revenue-per-plan') }}"
                           class="pb-nav-item {{ $active('isp.reports.revenue-per-plan') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Revenue' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Revenue</span>
                        </a>
                        <a href="{{ route('isp.reports.monthly-collections') }}"
                           class="pb-nav-item {{ $active('isp.reports.monthly-collections') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Collections' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="8" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="16" y2="21"/><line x1="3" y1="12" x2="21" y2="12"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Collections</span>
                        </a>
                        <a href="{{ route('isp.reports.customer-status') }}"
                           class="pb-nav-item {{ $active('isp.reports.customer-status') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Customer growth' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 17 9 11 13 15 21 7"/><polyline points="14 7 21 7 21 14"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Customer growth</span>
                        </a>
                    </div>
                </div>

                {{-- ============= SYSTEM ============= --}}
                <div>
                    <div class="pb-sidebar-section-label mb-2" x-show="!$store.sidebar.collapsed">System</div>
                    <div class="space-y-1">
                        <a href="{{ route('isp.platform-subscription.index') }}"
                           class="pb-nav-item {{ $active('isp.platform-subscription.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'PrimeBilling subscription' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            <span x-show="!$store.sidebar.collapsed">PrimeBilling subscription</span>
                        </a>
                        <a href="{{ route('isp.notifications.index') }}"
                           class="pb-nav-item {{ $active('isp.notifications.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Notifications' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Notifications</span>
                        </a>
                        <a href="{{ route('isp.settings.index') }}"
                           class="pb-nav-item {{ $active('isp.settings.*') || $active('isp.payment-settings.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Settings' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06A2 2 0 1 1 7.04 4.29l.06.06A1.65 1.65 0 0 0 8.92 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.36.18.66.46.86.84a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Settings</span>
                        </a>
                        <a href="{{ route('isp.logs.activity.index') }}"
                           class="pb-nav-item {{ $active('isp.logs.*') ? 'is-active' : '' }}"
                           :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                           :title="$store.sidebar.collapsed ? 'Audit logs' : ''">
                            <svg class="pb-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
                            <span x-show="!$store.sidebar.collapsed">Audit logs</span>
                        </a>
                    </div>
                </div>
            @endif

        </nav>
    </div>

    {{-- Footer / Status --}}
    <div class="border-t border-white/5 p-3" x-show="!$store.sidebar.collapsed">
        <div class="rounded-xl bg-white/5 p-3">
            <div class="flex items-center gap-2">
                <span class="pb-pulse-dot"></span>
                <span class="text-[11px] font-semibold text-emerald-300">All systems operational</span>
            </div>
            <p class="mt-1 text-[10px] text-slate-400 leading-relaxed">
                {{ $user->isp?->name ?? 'PrimeBilling' }} · v{{ config('app.version', '1.0') }}
            </p>
        </div>
    </div>

    <div class="border-t border-white/5 p-2" x-show="$store.sidebar.collapsed" x-cloak>
        <div class="flex items-center justify-center" title="All systems operational">
            <span class="pb-pulse-dot"></span>
        </div>
    </div>
</aside>
