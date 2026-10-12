<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F172A">

    <title>@yield('title', 'Dashboard') · {{ config('app.name', 'PrimeBilling') }}</title>
    @php $ibHead = $ispBranding ?? null; @endphp
    @if (! empty($ibHead['favicon_url'] ?? null))
        <link rel="icon" href="{{ $ibHead['favicon_url'] }}" type="image/png">
    @endif
    @if (! empty($ibHead['accent_hex'] ?? null))
        <style>
            :root { --pb-accent: {{ $ibHead['accent_hex'] }}; }
        </style>
    @endif

    {{-- Pre-paint dark-mode resolution to avoid theme flash --}}
    <script>
        (function () {
            try {
                var accountTheme = @json(data_get(auth()->user()?->preferences, 'theme'));
                var stored = accountTheme || localStorage.getItem('pb.theme');
                if (accountTheme) localStorage.setItem('pb.theme', accountTheme);
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || ((!stored || stored === 'system') && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
        try {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.documentElement.classList.add('pb-sidebar-collapsed');
            }
        } catch (e) {}
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <x-vite-assets />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.Swal = Swal.mixin({
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#64748b',
            denyButtonColor: '#ef4444',
            buttonsStyling: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
        });

        window.confirmAction = function(options) {
            const defaults = {
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel',
            };
            return Swal.fire(Object.assign({}, defaults, options));
        };

        window.confirmFormSubmit = function(event, options) {
            event.preventDefault();
            const form = event.target;
            confirmAction(options).then((result) => { if (result.isConfirmed) form.submit(); });
        };
    </script>

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --pb-bg: #f5f7fb;
            --pb-bg-soft: #f8fafc;
            --pb-panel: #ffffff;
            --pb-border: #e5e7eb;
            --pb-text: #0f172a;
            --pb-text-muted: #64748b;
            --pb-sidebar-bg: #0b1120;
            --pb-sidebar-bg-2: #0f172a;
            --pb-accent: #6366f1;
            --pb-accent-2: #06b6d4;
        }
        .dark {
            --pb-bg: #050912;
            --pb-bg-soft: #0a0f1f;
            --pb-panel: #0f172a;
            --pb-border: #1f2937;
            --pb-text: #e2e8f0;
            --pb-text-muted: #94a3b8;
        }

        html, body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }

        body {
            background: var(--pb-bg);
            color: var(--pb-text);
        }

        /* Faint operational mesh on the canvas */
        .pb-canvas {
            background:
                radial-gradient(900px 500px at 90% -10%, rgba(99, 102, 241, 0.06), transparent 60%),
                radial-gradient(700px 400px at -10% 110%, rgba(6, 182, 212, 0.05), transparent 60%),
                var(--pb-bg);
        }
        .dark .pb-canvas {
            background:
                radial-gradient(900px 500px at 90% -10%, rgba(99, 102, 241, 0.10), transparent 60%),
                radial-gradient(700px 400px at -10% 110%, rgba(6, 182, 212, 0.07), transparent 60%),
                var(--pb-bg);
        }

        /* ========== Cards ========== */
        .pb-card {
            background: var(--pb-panel);
            border: 1px solid var(--pb-border);
            border-radius: 16px;
            box-shadow:
                0 1px 0 rgba(255,255,255,0.6) inset,
                0 8px 24px -12px rgba(15, 23, 42, 0.10),
                0 2px 6px -2px rgba(15, 23, 42, 0.04);
            transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
        }
        .dark .pb-card {
            box-shadow:
                0 1px 0 rgba(255,255,255,0.04) inset,
                0 12px 32px -16px rgba(0, 0, 0, 0.6),
                0 2px 6px -2px rgba(0, 0, 0, 0.35);
        }
        .pb-card-hover:hover {
            transform: translateY(-1px);
            box-shadow:
                0 1px 0 rgba(255,255,255,0.6) inset,
                0 16px 32px -14px rgba(15, 23, 42, 0.16),
                0 4px 10px -4px rgba(15, 23, 42, 0.08);
        }

        /* ========== Glass topbar ========== */
        .pb-topbar {
            background: rgba(255, 255, 255, 0.78);
            border-bottom: 1px solid var(--pb-border);
            backdrop-filter: saturate(180%) blur(14px);
            -webkit-backdrop-filter: saturate(180%) blur(14px);
        }
        .dark .pb-topbar {
            background: rgba(11, 17, 32, 0.78);
            border-bottom-color: rgba(31, 41, 55, 0.7);
        }

        /* ========== Sidebar ========== */
        .pb-sidebar {
            width: 16rem;
            background:
                radial-gradient(80% 60% at 0% 0%, rgba(99, 102, 241, 0.18), transparent 60%),
                radial-gradient(70% 60% at 100% 100%, rgba(6, 182, 212, 0.10), transparent 60%),
                linear-gradient(180deg, var(--pb-sidebar-bg) 0%, var(--pb-sidebar-bg-2) 100%);
            color: #cbd5e1;
        }
        .pb-sidebar-collapsed .pb-sidebar {
            width: 4rem;
        }
        @media (min-width: 768px) {
            .pb-sidebar-collapsed #pb-admin-topbar {
                left: 4rem !important;
            }
            .pb-sidebar-collapsed #pb-main {
                margin-left: 4rem !important;
            }
        }
        @media (max-width: 767px) {
            .pb-sidebar-collapsed .pb-sidebar {
                width: 16rem;
            }
        }
        .pb-sidebar-section-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(148, 163, 184, 0.7);
            padding: 0 12px;
            margin-bottom: 0.75rem;
        }
        .pb-sidebar-modules {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .pb-sidebar-modules .pb-nav-item {
            padding-top: 0.6875rem;
            padding-bottom: 0.6875rem;
        }
        .pb-sidebar-brand-mark {
            background-size: 58% 58%, cover;
            background-position: center, center;
            background-repeat: no-repeat, no-repeat;
        }
        .pb-sidebar-mobile-close {
            -webkit-tap-highlight-color: transparent;
        }
        .pb-nav-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: #cbd5e1;
            transition: all .15s ease;
        }
        .pb-nav-item:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }
        .pb-nav-item.is-active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.22), rgba(6, 182, 212, 0.10));
            color: #fff;
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.05) inset, 0 6px 18px -8px rgba(99, 102, 241, 0.55);
        }
        .pb-nav-item.is-active::before {
            content: "";
            position: absolute;
            left: -2px;
            top: 6px;
            bottom: 6px;
            width: 3px;
            border-radius: 3px;
            background: linear-gradient(180deg, #818cf8, #22d3ee);
            box-shadow: 0 0 14px rgba(99, 102, 241, 0.7);
        }
        .pb-nav-item .pb-nav-icon {
            width: 16px;
            height: 16px;
            flex: none;
            opacity: .85;
        }
        .pb-nav-item.is-active .pb-nav-icon { opacity: 1; }
        .pb-subnav-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px 6px 34px;
            border-radius: 8px;
            font-size: 12.5px;
            color: rgba(203, 213, 225, 0.85);
            transition: color .15s ease, background .15s ease;
        }
        .pb-subnav-item::before {
            content: "";
            width: 4px;
            height: 4px;
            border-radius: 9999px;
            background: rgba(148, 163, 184, 0.45);
            display: inline-block;
            margin-left: -16px;
            margin-right: 12px;
            transition: background .15s ease;
        }
        .pb-subnav-item:hover { color: #fff; background: rgba(255, 255, 255, 0.04); }
        .pb-subnav-item:hover::before { background: #67e8f9; }
        .pb-subnav-item.is-active { color: #fff; background: rgba(99, 102, 241, 0.18); }
        .pb-subnav-item.is-active::before { background: #22d3ee; box-shadow: 0 0 8px #22d3ee; }

        /* ========== Pills / Status ========== */
        .pb-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid var(--pb-border);
            background: var(--pb-panel);
            color: var(--pb-text);
        }
        .pb-pill-ok    { background: rgba(16, 185, 129, 0.10); border-color: rgba(16,185,129,0.25); color: #047857; }
        .pb-pill-warn  { background: rgba(245, 158, 11, 0.10); border-color: rgba(245,158,11,0.30); color: #b45309; }
        .pb-pill-bad   { background: rgba(239, 68, 68, 0.10);  border-color: rgba(239,68,68,0.30);  color: #b91c1c; }
        .pb-pill-info  { background: rgba(99, 102, 241, 0.10); border-color: rgba(99,102,241,0.30); color: #4338ca; }
        .pb-pill-cyan  { background: rgba(6, 182, 212, 0.10);  border-color: rgba(6,182,212,0.30);  color: #0e7490; }
        .dark .pb-pill-ok    { color: #6ee7b7; }
        .dark .pb-pill-warn  { color: #fcd34d; }
        .dark .pb-pill-bad   { color: #fca5a5; }
        .dark .pb-pill-info  { color: #a5b4fc; }
        .dark .pb-pill-cyan  { color: #67e8f9; }

        .pb-dot { display: inline-block; width: 6px; height: 6px; border-radius: 9999px; }
        .pb-dot-ok   { background: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.18); }
        .pb-dot-warn { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.18); }
        .pb-dot-bad  { background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.18); }

        .pb-pulse-dot {
            position: relative;
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background: #10b981;
        }
        .pb-pulse-dot::after {
            content: "";
            position: absolute;
            inset: -4px;
            border-radius: 9999px;
            background: #10b981;
            opacity: 0.45;
            animation: pb-ping 1.8s cubic-bezier(0,0,.2,1) infinite;
        }
        @keyframes pb-ping {
            0%   { transform: scale(0.6); opacity: .55; }
            80%, 100% { transform: scale(1.8); opacity: 0; }
        }

        /* ========== Buttons ========== */
        .pb-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid var(--pb-border);
            background: var(--pb-panel);
            color: var(--pb-text);
            transition: all .15s ease;
        }
        .pb-btn:hover { border-color: #c7d2fe; color: #4338ca; }
        .dark .pb-btn:hover { border-color: rgba(99, 102, 241, 0.5); color: #c7d2fe; }
        .pb-btn-primary {
            background-image: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #06b6d4 100%);
            background-size: 200% 200%;
            background-position: 0% 50%;
            color: #fff;
            border: none;
            box-shadow: 0 8px 18px -10px rgba(79, 70, 229, 0.55), 0 1px 0 rgba(255,255,255,0.18) inset;
            transition: background-position .35s ease, transform .15s ease, box-shadow .2s ease;
        }
        .pb-btn-primary:hover { background-position: 100% 50%; transform: translateY(-1px); color: #fff; }

        /* ========== Activity & list rows ========== */
        .pb-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            transition: background .15s ease;
        }
        .pb-row + .pb-row { border-top: 1px dashed var(--pb-border); }
        .pb-row:hover { background: rgba(99, 102, 241, 0.05); }
        .dark .pb-row:hover { background: rgba(99, 102, 241, 0.10); }

        /* ========== Sparkline + bars ========== */
        .pb-spark { color: #6366f1; }
        .pb-bar {
            width: 6px;
            border-radius: 3px;
            background: linear-gradient(180deg, #22d3ee, #6366f1);
            opacity: 0.85;
        }

        /* ========== Bandwidth meter ========== */
        .pb-meter {
            position: relative;
            height: 6px;
            background: rgba(148, 163, 184, 0.18);
            border-radius: 9999px;
            overflow: hidden;
        }
        .pb-meter-fill {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, #22d3ee, #6366f1);
            border-radius: 9999px;
        }

        /* Fade-in animations */
        .pb-fade-up { animation: pb-fadeUp .45s ease both; }
        .pb-fade-up.delay-1 { animation-delay: .05s; }
        .pb-fade-up.delay-2 { animation-delay: .10s; }
        .pb-fade-up.delay-3 { animation-delay: .15s; }
        .pb-fade-up.delay-4 { animation-delay: .20s; }
        @keyframes pb-fadeUp {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Skeleton */
        .pb-skeleton {
            background: linear-gradient(90deg, rgba(148,163,184,0.12) 25%, rgba(148,163,184,0.22) 50%, rgba(148,163,184,0.12) 75%);
            background-size: 200% 100%;
            animation: pb-shimmer 1.4s linear infinite;
            border-radius: 8px;
        }
        @keyframes pb-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        @media (prefers-reduced-motion: reduce) {
            .pb-pulse-dot::after, .pb-fade-up, .pb-skeleton { animation: none !important; }
        }
    </style>
</head>
@php
    $pbSaasOnboardingOverlay = (bool) ($pbSaasOnboardingOverlay ?? false);
@endphp
<body class="pb-canvas isp-page antialiased overflow-x-hidden @if($pbSaasOnboardingOverlay) pb-onboarding-locked @endif">
    @include('layouts.admin-header')

    <div
        id="pb-admin-shell"
        class="min-h-screen"
        x-data
        @if ($pbSaasOnboardingOverlay)
            inert
            aria-hidden="true"
        @endif
    >
        @include('layouts.admin-sidebar')

        {{-- Mobile sidebar overlay --}}
        <div
            x-show="$store.sidebar.mobileOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-30 bg-slate-900/60 backdrop-blur-sm md:hidden"
            @click="$store.sidebar.closeMobile()"
        ></div>

        <turbo-frame
            id="pb-main"
            target="_top"
            class="block min-h-screen pt-16 transition-all duration-300 ease-in-out md:ml-64"
            :class="$store.sidebar.collapsed ? 'md:!ml-16' : ''"
        >
            <main class="min-h-screen">
                <div class="pb-module-workspace-dock px-4 sm:px-6 lg:px-8">
                    <x-module-workspace-bar />
                </div>

                <div class="overflow-x-hidden px-4 pb-6 pt-4 sm:px-6 lg:px-8">
                    @php
                        $pbQuickCreatePayload = app(\App\Support\Navigation\PbQuickCreatePresenter::class)->forRoute(
                            Route::currentRouteName(),
                            auth()->user(),
                        );
                    @endphp
                    <span
                        id="pb-route-meta"
                        class="sr-only"
                        data-route="{{ Route::currentRouteName() }}"
                        data-title="{{ trim((string) $__env->yieldContent('title')) }}"
                        data-quick-create="{{ json_encode($pbQuickCreatePayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                        data-i18n-create="{{ __('Create') }}"
                        aria-hidden="true"
                    ></span>

                    <x-system-announcements-banner :announcements="$systemAnnouncements ?? collect()" />

                    @hasSection('header')
                        <div class="pb-fade-up mb-6 pb-card p-4 sm:p-5">
                            @yield('header')
                        </div>
                    @elseif(request()->routeIs('isp.*'))
                        <div class="pb-card pb-fade-up p-4 sm:p-5 mb-6">
                            <x-isp-page-header
                                :title="trim((string) $__env->yieldContent('title')) ?: 'ISP Operations'"
                                :description="'Unified PrimeBilling ISP operating surface.'"
                            />
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
            @include('partials.turbo-frame-flash')
        </turbo-frame>
    </div>

    @if ($pbSaasOnboardingOverlay)
        @include('isp.saas-onboarding.partials.overlay')
    @endif

    @stack('scripts')

    <div id="pb-modal-overlay" class="pb-modal-overlay" hidden aria-hidden="true">
        <div class="pb-modal-overlay__backdrop" data-pb-form-modal-close></div>
        <div class="pb-modal-overlay__panel" role="dialog" aria-modal="true" aria-labelledby="pb-form-modal-title">
            <div id="pb-form-modal" class="pb-form-modal-host"></div>
        </div>
    </div>
    <div id="pb-toast-host" class="pb-toast-host" aria-live="polite"></div>
    @php
        $pbModalFormConfig = [
            'blockedPathFragments' => ['/saas-onboarding', '/profile', '/payment-settings'],
        ];
        $pbDiscoveryConfig = [
            'searchUrl' => route('feature-discovery.search'),
            'categoryLabels' => config('feature_registry.category_labels', []),
            'resultsLimit' => config('feature_registry.results_limit', 24),
            'recentLimit' => config('feature_registry.recent_limit', 12),
            'favoritesLimit' => config('feature_registry.favorites_limit', 12),
        ];
    @endphp
    <script>
        window.__pbModalForm = @json($pbModalFormConfig);
        window.__pbDiscovery = @json($pbDiscoveryConfig);
    </script>

</body>
</html>
