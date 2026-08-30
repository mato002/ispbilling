@php
    use App\Support\SaasOnboardingContext;

    $user = Auth::user();
    $onboardingIsp = SaasOnboardingContext::resolveIsp($user);
    $ispName = $onboardingIsp?->name ?? $user->isp?->name ?? ($user->isSuperAdmin() ? 'PrimeBilling Admin' : 'Your ISP');
    $homeRoute = $user->isSuperAdmin() ? route('admin.dashboard') : route('isp.dashboard');
    $pbSaasOnboardingOverlay = (bool) ($pbSaasOnboardingOverlay ?? false);
    $onboardingHomeRoute = $homeRoute;
@endphp

<header
    id="pb-admin-topbar"
    data-turbo-permanent
    x-data="pbTopbar()"
    @keydown.window.cmd.k.prevent="if (!document.body.classList.contains('pb-onboarding-locked')) searchOpen = true"
    @keydown.window.ctrl.k.prevent="if (!document.body.classList.contains('pb-onboarding-locked')) searchOpen = true"
    @keydown.window.escape="closePalette(); quickActionsOpen = false; profileOpen = false; $store.sidebar.closeMobile()"
    class="pb-topbar fixed top-0 right-0 z-30 h-16 transition-all duration-300 ease-in-out left-0 md:left-64"
    :class="$store.sidebar.collapsed ? 'md:!left-16' : ''"
>
    <div class="flex h-full items-center gap-2 px-3 sm:gap-4 sm:px-6">

        {{-- ============= LEFT CLUSTER ============= --}}
        <div class="flex items-center gap-2 min-w-0">
            {{-- Sidebar collapse toggle (desktop) --}}
            <button @click="$store.sidebar.toggle()"
                    class="hidden md:inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    title="Collapse sidebar"
                    :title="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    :aria-label="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    :aria-expanded="(!$store.sidebar.collapsed).toString()">
                <svg x-show="$store.sidebar.collapsed" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/>
                </svg>
                <svg x-show="!$store.sidebar.collapsed" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="10" y1="18" x2="20" y2="18"/>
                </svg>
            </button>

            {{-- Mobile menu toggle --}}
            <button @click="$store.sidebar.openMobile()"
                    class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/>
                </svg>
            </button>

            {{-- Logo + brand --}}
            <a href="{{ $onboardingHomeRoute }}" class="flex items-center gap-2.5 min-w-0">
                <span class="relative flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-violet-500 to-cyan-400 shadow-[0_8px_18px_-8px_rgba(99,102,241,0.6),inset_0_1px_0_rgba(255,255,255,0.18)]">
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12a10 10 0 0 1 20 0"/>
                        <path d="M5 12a7 7 0 0 1 14 0"/>
                        <path d="M8.5 12a3.5 3.5 0 0 1 7 0"/>
                        <circle cx="12" cy="18" r="1.4" fill="currentColor"/>
                    </svg>
                </span>
                <span class="hidden sm:flex flex-col leading-tight min-w-0">
                    <span class="text-sm font-bold tracking-tight text-slate-900 dark:text-white truncate">PrimeBilling</span>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400 truncate">{{ $ispName }}</span>
                </span>
            </a>
        </div>

        @if ($pbSaasOnboardingOverlay)
            <div class="hidden flex-1 justify-center px-2 sm:flex">
                <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200/80 bg-indigo-50/90 px-3 py-1.5 text-xs font-semibold text-indigo-800 shadow-sm dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-indigo-400 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                    </span>
                    Workspace setup in progress
                </span>
            </div>
        @else
        {{-- ============= GLOBAL SEARCH ============= --}}
        <div class="hidden md:flex flex-1 justify-center px-2">
            <button
                @click="searchOpen = true"
                class="group flex w-full max-w-xl items-center gap-2 rounded-xl border border-slate-200 bg-white/70 px-3 py-2 text-left text-xs text-slate-500 transition hover:border-indigo-200 hover:bg-white hover:text-slate-700 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400 dark:hover:border-indigo-500/30 dark:hover:bg-slate-900"
            >
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <span class="text-[13px]">Search pages, settings, reports, network…</span>
                <span class="ml-auto inline-flex items-center gap-1 rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-400">
                    ⌘K
                </span>
            </button>
        </div>
        @endif

        {{-- ============= RIGHT CLUSTER ============= --}}
        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">

            {{-- Context-aware Create (jana-prints quick-create pattern) --}}
            <div id="pb-quick-create" class="hidden sm:contents"></div>

            @unless ($pbSaasOnboardingOverlay)
            {{-- Mobile search trigger --}}
            <button @click="searchOpen = true"
                    class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                    title="Search">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                </svg>
            </button>

            {{-- Network status indicator --}}
            <div class="hidden lg:inline-flex items-center gap-1.5 rounded-full border border-emerald-200/70 bg-emerald-50/70 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                <span class="pb-pulse-dot"></span>
                <span>Network healthy</span>
            </div>

            {{-- Quick actions --}}
            @if($user->isIspAdmin() || $user->isIspStaff())
                <div class="relative" data-pb-quick-actions @click.outside="quickActionsOpen = false">
                    <button @click="quickActionsOpen = !quickActionsOpen"
                            class="hidden sm:inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-br from-indigo-500 to-cyan-500 px-3 py-2 text-xs font-semibold text-white shadow-[0_8px_18px_-10px_rgba(99,102,241,0.6)] hover:from-indigo-600 hover:to-cyan-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14"/><path d="M5 12h14"/>
                        </svg>
                        <span>Quick action</span>
                        <svg class="h-3 w-3 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div x-show="quickActionsOpen" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute right-0 mt-2 w-72 origin-top-right rounded-xl border border-slate-200 bg-white p-2 shadow-xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900">
                        <div class="px-2 pb-1.5 pt-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Operations</div>
                        <a href="{{ route('isp.customers.create') }}" data-pb-modal-open class="pb-row !p-2 text-sm">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-300">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                            </span>
                            <span class="leading-tight">
                                <span class="block font-medium text-slate-800 dark:text-slate-100">Add new customer</span>
                                <span class="block text-[11px] text-slate-500 dark:text-slate-400">Onboard a new subscriber</span>
                            </span>
                        </a>
                        <a href="{{ route('isp.payments.initiate') }}" class="pb-row !p-2 text-sm">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-300">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            </span>
                            <span class="leading-tight">
                                <span class="block font-medium text-slate-800 dark:text-slate-100">Record payment</span>
                                <span class="block text-[11px] text-slate-500 dark:text-slate-400">Initiate M-Pesa or manual</span>
                            </span>
                        </a>
                        <a href="{{ route('isp.subscriptions.create') }}" data-pb-modal-open class="pb-row !p-2 text-sm">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-300">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                            </span>
                            <span class="leading-tight">
                                <span class="block font-medium text-slate-800 dark:text-slate-100">New subscription</span>
                                <span class="block text-[11px] text-slate-500 dark:text-slate-400">Activate a service plan</span>
                            </span>
                        </a>
                        <a href="{{ route('isp.routers.create') }}" data-pb-modal-open class="pb-row !p-2 text-sm">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-300">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="9" rx="2"/><path d="M7 7v4M17 7v4M5 7h14"/></svg>
                            </span>
                            <span class="leading-tight">
                                <span class="block font-medium text-slate-800 dark:text-slate-100">Add MikroTik router</span>
                                <span class="block text-[11px] text-slate-500 dark:text-slate-400">Connect new device</span>
                            </span>
                        </a>
                        <a href="{{ route('isp.reports.index') }}" class="pb-row !p-2 text-sm">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-300">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l3-3 4 4 5-5"/></svg>
                            </span>
                            <span class="leading-tight">
                                <span class="block font-medium text-slate-800 dark:text-slate-100">Generate report</span>
                                <span class="block text-[11px] text-slate-500 dark:text-slate-400">Revenue, customer status</span>
                            </span>
                        </a>
                    </div>
                </div>
            @endif
            @endunless

            {{-- Theme toggle --}}
            <button @click="toggleTheme()"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'">
                <svg x-show="theme !== 'dark'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
                </svg>
                <svg x-show="theme === 'dark'" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                </svg>
            </button>

            {{-- Notifications --}}
            <div class="relative">
                <x-notification-dropdown :recentNotifications="$recentNotifications ?? null" :unreadCount="$unreadCount ?? 0" />
            </div>

            {{-- Profile dropdown --}}
            <div class="relative" @click.outside="profileOpen = false">
                <button @click="profileOpen = !profileOpen"
                        class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <div class="relative flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-cyan-500 text-xs font-bold text-white">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                        <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-white dark:ring-slate-900"></span>
                    </div>
                    <div class="hidden md:block text-left leading-tight">
                        <div class="text-xs font-semibold text-slate-700 dark:text-slate-100">{{ $user->name }}</div>
                        <div class="text-[10px] font-medium text-slate-400">
                            {{ $user->isSuperAdmin() ? 'Super Admin' : ($user->isIspAdmin() ? 'ISP Admin' : 'ISP Staff') }}
                        </div>
                    </div>
                    <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <div x-show="profileOpen" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute right-0 mt-2 w-60 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900">
                    <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $user->email }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="pb-row !p-2 text-sm text-slate-600 dark:text-slate-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        Profile settings
                    </a>
                    @if($user->isIspAdmin() || $user->isIspStaff())
                        <a href="{{ route('isp.settings.index') }}" class="pb-row !p-2 text-sm text-slate-600 dark:text-slate-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06A2 2 0 1 1 7.04 4.29l.06.06A1.65 1.65 0 0 0 8.92 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.36.18.66.46.86.84a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                            ISP settings
                        </a>
                    @endif
                    <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="pb-row !p-2 w-full text-left text-sm text-rose-600 dark:text-rose-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ COMMAND-K SEARCH ============================ --}}
    <x-command-palette />
</header>
