@php
    $user = Auth::user();
    $isSuper = $user->isSuperAdmin();
    $isTechnician = $user->isTechnician();
    $isIsp = $user->isIspAdmin() || $user->isIspStaff();
    $tenantIsp = request()->attributes->get(\App\Http\Middleware\LoadTenantIsp::ATTRIBUTE);
    $ispName = $tenantIsp?->name ?? optional($user->isp)->name ?? ($isSuper ? 'PrimeBilling Admin' : 'Your ISP');
    $homeRoute = $isSuper
        ? route('admin.dashboard')
        : ($isTechnician ? route('isp.technician.inventory.index') : route('isp.dashboard'));
@endphp

<aside
    data-turbo-permanent
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
    <div class="pb-sidebar-brand-row shrink-0 flex h-20 items-center border-b border-white/5">
        <a href="{{ $homeRoute }}"
           class="pb-sidebar-brand group flex min-w-0 flex-1 items-center no-underline outline-none transition-colors hover:bg-white/5 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-400/50"
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
        <button
            type="button"
            class="pb-sidebar-mobile-close mr-3 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/5 hover:text-white md:hidden"
            @click="$store.sidebar.closeMobile()"
            aria-label="Close menu"
            title="Close menu"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>
    </div>

    {{-- Scroll area --}}
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-5">
        <nav
             :class="$store.sidebar.collapsed ? 'px-2' : 'px-3'">

            @if($isSuper)
                {{-- ============= SUPER ADMIN ============= --}}
                <div>
                    <div class="pb-sidebar-section-label" x-show="!$store.sidebar.collapsed">Navigation</div>
                    @include('layouts.partials.sidebar-modules', ['modules' => config('navigation_workspaces.admin')])
                </div>
            @elseif($isTechnician)

                <div>
                    <div class="pb-sidebar-section-label" x-show="!$store.sidebar.collapsed">Field</div>
                    @include('layouts.partials.sidebar-modules', ['modules' => config('navigation_workspaces.technician')])
                </div>

            @elseif($isIsp)

                <div>
                    <div class="pb-sidebar-section-label" x-show="!$store.sidebar.collapsed">Workspace</div>
                    @include('layouts.partials.sidebar-modules', ['modules' => config('navigation_workspaces.isp')])
                </div>
            @endif

        </nav>
    </div>

    {{-- Desktop collapse toggle --}}
    <div class="hidden shrink-0 border-t border-white/5 p-2 md:block">
        <button
            type="button"
            @click="$store.sidebar.toggle()"
            class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-slate-400 transition hover:bg-white/5 hover:text-white"
            :class="$store.sidebar.collapsed ? 'justify-center' : ''"
            :title="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            :aria-label="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            :aria-expanded="(!$store.sidebar.collapsed).toString()"
        >
            <svg class="h-4 w-4 shrink-0 transition-transform duration-300" :class="$store.sidebar.collapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            <span class="text-xs font-semibold" x-show="!$store.sidebar.collapsed" x-cloak>Collapse</span>
        </button>
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
