<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-seo-meta
        :title="($title ?? 'Sign in').' · '.config('app.name', 'PrimeBilling')"
        :description="$metaDescription ?? config('seo.default_description')"
        robots="noindex, nofollow"
    />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <x-vite-assets />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.Swal = Swal.mixin({
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#64748b',
            buttonsStyling: true,
        });
    </script>

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --pb-bg-0: #05070f;
            --pb-bg-1: #0a0f1f;
            --pb-bg-2: #0d1430;
            --pb-accent: #6366f1;
            --pb-accent-2: #22d3ee;
            --pb-accent-3: #8b5cf6;
        }

        .pb-auth-shell {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        /* Soft mesh gradient backdrop on the right side */
        .pb-mesh {
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(99, 102, 241, 0.18), transparent 60%),
                radial-gradient(900px 600px at 110% 10%, rgba(34, 211, 238, 0.14), transparent 60%),
                radial-gradient(800px 500px at 50% 120%, rgba(139, 92, 246, 0.16), transparent 60%),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        }
        .dark .pb-mesh {
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(99, 102, 241, 0.22), transparent 60%),
                radial-gradient(900px 600px at 110% 10%, rgba(34, 211, 238, 0.14), transparent 60%),
                radial-gradient(800px 500px at 50% 120%, rgba(139, 92, 246, 0.18), transparent 60%),
                linear-gradient(180deg, #0b1120 0%, #0a0f1f 100%);
        }

        /* Telecom panel: deep gradient + subtle network grid */
        .pb-brand-panel {
            background:
                radial-gradient(60% 60% at 20% 10%, rgba(99, 102, 241, 0.28), transparent 70%),
                radial-gradient(50% 50% at 90% 30%, rgba(34, 211, 238, 0.22), transparent 70%),
                radial-gradient(60% 50% at 60% 100%, rgba(139, 92, 246, 0.28), transparent 70%),
                linear-gradient(135deg, #05070f 0%, #0a0f1f 45%, #0d1430 100%);
        }
        .pb-grid::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: radial-gradient(ellipse at 50% 40%, #000 30%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse at 50% 40%, #000 30%, transparent 75%);
            pointer-events: none;
        }

        /* Animated gradient blobs */
        .pb-blob {
            position: absolute;
            border-radius: 9999px;
            filter: blur(80px);
            opacity: 0.55;
            animation: pb-float 14s ease-in-out infinite;
        }
        .pb-blob.b1 { top: -90px; left: -60px; width: 320px; height: 320px; background: #6366f1; }
        .pb-blob.b2 { bottom: -120px; right: -80px; width: 380px; height: 380px; background: #22d3ee; animation-delay: -4s; }
        .pb-blob.b3 { top: 40%; left: 55%; width: 260px; height: 260px; background: #8b5cf6; animation-delay: -8s; opacity: 0.4; }

        @keyframes pb-float {
            0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
            50% { transform: translate3d(20px, -30px, 0) scale(1.06); }
        }

        /* Glow ring for logo */
        .pb-logo-glow {
            box-shadow:
                0 10px 40px -10px rgba(99, 102, 241, 0.55),
                0 0 0 1px rgba(255, 255, 255, 0.06) inset;
        }

        /* Auth card glassmorphism */
        .pb-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: saturate(180%) blur(18px);
            -webkit-backdrop-filter: saturate(180%) blur(18px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.6) inset,
                0 30px 60px -20px rgba(15, 23, 42, 0.18),
                0 10px 25px -10px rgba(15, 23, 42, 0.10);
        }
        .dark .pb-card {
            background: rgba(15, 23, 42, 0.7);
            border-color: rgba(51, 65, 85, 0.6);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.04) inset,
                0 30px 60px -20px rgba(0, 0, 0, 0.6);
        }

        /* Floating stat cards in branding panel */
        .pb-stat-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            box-shadow: 0 20px 40px -20px rgba(0, 0, 0, 0.5);
        }
        .pb-float-1 { animation: pb-floatY 7s ease-in-out infinite; }
        .pb-float-2 { animation: pb-floatY 8s ease-in-out -2s infinite; }
        .pb-float-3 { animation: pb-floatY 9s ease-in-out -4s infinite; }
        @keyframes pb-floatY {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        /* Pulse dot for live indicators */
        .pb-pulse {
            position: relative;
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background: #22c55e;
        }
        .pb-pulse::after {
            content: "";
            position: absolute;
            inset: -4px;
            border-radius: 9999px;
            background: #22c55e;
            opacity: 0.45;
            animation: pb-ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        @keyframes pb-ping {
            0% { transform: scale(0.6); opacity: 0.55; }
            80%, 100% { transform: scale(1.8); opacity: 0; }
        }

        /* Input refinements */
        .pb-input {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgb(226 232 240);
            background: rgba(255, 255, 255, 0.85);
            color: rgb(15 23 42);
            padding: 12px 14px 12px 44px;
            font-size: 14px;
            line-height: 1.4;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .pb-input::placeholder { color: rgb(148 163 184); }
        .pb-input:focus {
            outline: none;
            border-color: rgb(99 102 241);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.14);
        }
        .dark .pb-input {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgb(51 65 85);
            color: rgb(241 245 249);
        }
        .dark .pb-input::placeholder { color: rgb(100 116 139); }
        .dark .pb-input:focus {
            background: rgba(15, 23, 42, 0.85);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25);
        }

        /* Premium CTA */
        .pb-cta {
            background-image: linear-gradient(135deg, #4f46e5 0%, #6366f1 45%, #22d3ee 100%);
            background-size: 200% 200%;
            background-position: 0% 50%;
            color: #fff;
            border: none;
            font-weight: 600;
            letter-spacing: 0.01em;
            border-radius: 12px;
            padding: 14px 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow:
                0 12px 24px -10px rgba(79, 70, 229, 0.55),
                0 1px 0 rgba(255, 255, 255, 0.2) inset;
            transition: background-position .35s ease, transform .15s ease, box-shadow .2s ease, opacity .2s ease;
        }
        .pb-cta:hover { background-position: 100% 50%; transform: translateY(-1px); box-shadow: 0 18px 30px -12px rgba(79, 70, 229, 0.7), 0 1px 0 rgba(255,255,255,.2) inset; }
        .pb-cta:active { transform: translateY(0); }
        .pb-cta:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        /* Modern checkbox */
        .pb-check {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 6px;
            border: 1.5px solid rgb(203 213 225);
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .15s ease;
            position: relative;
        }
        .dark .pb-check { border-color: rgb(71 85 105); background: rgba(15, 23, 42, 0.6); }
        .pb-check:checked {
            background: linear-gradient(135deg, #4f46e5, #22d3ee);
            border-color: transparent;
        }
        .pb-check:checked::after {
            content: "";
            width: 10px;
            height: 10px;
            background: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='20 6 9 17 4 12'/></svg>") center/contain no-repeat;
        }
        .pb-check:focus-visible {
            outline: none;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }

        /* Fade-up entrance */
        .pb-fade-up { animation: pb-fadeUp .6s ease both; }
        .pb-fade-up.delay-1 { animation-delay: .08s; }
        .pb-fade-up.delay-2 { animation-delay: .16s; }
        .pb-fade-up.delay-3 { animation-delay: .24s; }
        @keyframes pb-fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Trust pill */
        .pb-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.01em;
            color: rgb(226 232 240);
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.10);
        }

        /* Mock chart bars */
        .pb-bar {
            display: inline-block;
            width: 6px;
            border-radius: 3px;
            background: linear-gradient(180deg, #22d3ee, #6366f1);
            opacity: 0.9;
        }

        /* Reduce motion respect */
        @media (prefers-reduced-motion: reduce) {
            .pb-blob, .pb-float-1, .pb-float-2, .pb-float-3, .pb-pulse::after, .pb-fade-up { animation: none !important; }
        }

        /* Compact auth — fit register & similar flows in one viewport */
        .pb-auth-compact,
        .pb-auth-compact .pb-mesh,
        .pb-auth-compact .pb-auth-grid {
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
        }
        html:has(.pb-auth-compact) {
            overflow: hidden;
        }
        .pb-auth-compact .pb-brand-panel > .relative {
            padding: 1.5rem 1.75rem;
        }
        .pb-auth-compact .pb-brand-panel h1 {
            font-size: 1.875rem;
            line-height: 1.1;
        }
        .pb-auth-compact .pb-brand-live-badge,
        .pb-auth-compact .pb-brand-stat-grid {
            display: none;
        }
        .pb-auth-compact .pb-brand-panel .pb-fade-up.delay-2 p {
            margin-top: 0.75rem;
            font-size: 0.8125rem;
            line-height: 1.45;
        }
        .pb-auth-compact .pb-brand-panel .pb-fade-up.delay-3.mt-9 {
            margin-top: 1rem;
            gap: 0.625rem;
        }
        .pb-auth-compact .pb-stat-card {
            padding: 0.625rem 0.75rem;
            border-radius: 12px;
        }
        .pb-auth-compact .pb-stat-card .text-2xl {
            font-size: 1.125rem;
            line-height: 1.2;
        }
        .pb-auth-compact .pb-stat-card .mt-3,
        .pb-auth-compact .pb-stat-card .mt-2.text-\[11px\] {
            margin-top: 0.25rem;
        }
        .pb-auth-compact .pb-stat-card .h-8 {
            height: 1.25rem;
            margin-top: 0.375rem;
        }
        .pb-auth-compact .pb-brand-panel .pb-pill {
            padding: 4px 8px;
            font-size: 10px;
        }
        .pb-auth-compact .pb-brand-panel .mt-4.text-\[11px\] {
            margin-top: 0.5rem;
        }
        .pb-auth-compact .pb-auth-main {
            min-height: 0;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        .pb-auth-compact .pb-card {
            padding: 1rem 1.125rem;
        }
        .pb-auth-compact .pb-input {
            padding: 8px 12px;
            font-size: 13px;
            border-radius: 10px;
        }
        .pb-auth-compact .pb-cta {
            padding: 10px 14px;
            border-radius: 10px;
        }
        .pb-auth-compact .pb-auth-trust-strip {
            display: none;
        }
    </style>
</head>
<body @class(['pb-auth-shell antialiased text-slate-900 dark:text-slate-100', 'pb-auth-compact' => $compact ?? false]) data-turbo="false">

    <div class="pb-mesh min-h-screen w-full">
        <div @class(['pb-auth-grid grid min-h-screen w-full lg:grid-cols-[1.05fr_1fr] xl:grid-cols-[1.1fr_1fr]', 'min-h-0' => $compact ?? false])>

            {{-- ============================================================
                 LEFT — BRANDING / TELECOM PANEL (desktop only)
                 ============================================================ --}}
            <aside class="relative hidden overflow-hidden pb-brand-panel pb-grid lg:flex lg:flex-col">
                <span class="pb-blob b1"></span>
                <span class="pb-blob b2"></span>
                <span class="pb-blob b3"></span>

                <div class="relative z-10 flex h-full flex-col justify-between p-10 xl:p-14">

                    {{-- Brand mark --}}
                    <div class="pb-fade-up flex items-center gap-3">
                        <div class="pb-logo-glow flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-violet-500 to-cyan-400">
                            <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12a10 10 0 0 1 20 0"/>
                                <path d="M5 12a7 7 0 0 1 14 0"/>
                                <path d="M8.5 12a3.5 3.5 0 0 1 7 0"/>
                                <circle cx="12" cy="18" r="1.4" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="leading-tight">
                            <div class="text-base font-bold tracking-tight text-white">PrimeBilling</div>
                            <div class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">ISP Operating System</div>
                        </div>
                    </div>

                    {{-- Headline + supporting copy --}}
                    <div class="max-w-xl">
                        <div class="pb-brand-live-badge pb-fade-up delay-1 mb-5 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">
                            <span class="pb-pulse"></span>
                            <span>Live network operations · running on PrimeBilling</span>
                        </div>

                        <h1 class="pb-fade-up delay-1 text-4xl font-extrabold leading-[1.05] tracking-tight text-white xl:text-5xl">
                            The Operating System
                            <span class="block bg-gradient-to-r from-indigo-300 via-cyan-300 to-violet-300 bg-clip-text text-transparent">
                                for Modern ISPs.
                            </span>
                        </h1>

                        <p class="pb-fade-up delay-2 mt-5 max-w-lg text-sm leading-7 text-slate-300/90 xl:text-base">
                            Automate billing, run MikroTik routers at scale, accept M-Pesa, manage hotspot &amp; PPPoE,
                            and grow your subscriber base — all from one telecom-grade control plane built for African ISPs.
                        </p>

                        {{-- Live dashboard preview / floating stat cards --}}
                        @php
                            $stats = $authPanelStats ?? [];
                            $growthPct = $stats['subscriber_growth_pct'] ?? null;
                            $growthPositive = $growthPct !== null && $growthPct >= 0;
                            $routerTone = $stats['router_health_tone'] ?? 'muted';
                            $routerLabelClass = match ($routerTone) {
                                'ok' => 'text-emerald-300',
                                'warn' => 'text-amber-300',
                                'bad' => 'text-rose-300',
                                default => 'text-slate-400',
                            };
                            $routerDotClass = match ($routerTone) {
                                'ok' => 'bg-emerald-400',
                                'warn' => 'bg-amber-400',
                                'bad' => 'bg-rose-400',
                                default => 'bg-slate-400',
                            };
                        @endphp
                        <div class="pb-brand-stat-grid pb-fade-up delay-3 mt-9 grid grid-cols-2 gap-4 max-w-lg">
                            <div class="pb-stat-card pb-float-1 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Active Subscribers</span>
                                    @if($growthPct !== null)
                                        <span @class([
                                            'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-semibold',
                                            'bg-emerald-500/15 text-emerald-300' => $growthPositive,
                                            'bg-rose-500/15 text-rose-300' => ! $growthPositive,
                                        ])>
                                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v8.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.22 3.22V3.75A.75.75 0 0 1 10 3Z" @unless($growthPositive) transform="rotate(180 10 10)" @endunless clip-rule="evenodd"/></svg>
                                            {{ $growthPositive ? '+' : '' }}{{ number_format($growthPct, 1) }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-2 flex items-baseline gap-1">
                                    <span class="text-2xl font-bold text-white">{{ number_format($stats['active_subscribers'] ?? 0) }}</span>
                                </div>
                                <div class="mt-3 flex items-end gap-1 h-8">
                                    @foreach ($stats['subscriber_sparkline'] ?? array_fill(0, 12, 8) as $h)
                                        <span class="pb-bar" style="height: {{ $h }}px"></span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="pb-stat-card pb-float-2 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Online Sessions</span>
                                    @if(($stats['online_sessions'] ?? 0) > 0)
                                        <span class="pb-pulse"></span>
                                    @endif
                                </div>
                                <div class="mt-2 text-2xl font-bold text-white">{{ number_format($stats['online_sessions'] ?? 0) }}</div>
                                <div class="mt-2 text-[11px] text-slate-400">PPPoE · Hotspot · Static</div>
                                <div class="mt-3 flex items-center gap-1 text-[11px] text-slate-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-400"></span>
                                    {{ number_format($stats['routers_reporting'] ?? 0) }} {{ Str::plural('router', $stats['routers_reporting'] ?? 0) }} reporting
                                </div>
                            </div>

                            <div class="pb-stat-card pb-float-3 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Revenue Today</span>
                                    <span class="rounded-md bg-violet-500/15 px-1.5 py-0.5 text-[10px] font-semibold text-violet-300">M-PESA</span>
                                </div>
                                <div class="mt-2 text-2xl font-bold text-white">{{ $stats['revenue_today_formatted'] ?? 'KSh 0' }}</div>
                                <div class="mt-2 text-[11px] text-slate-400">{{ number_format($stats['transactions_today'] ?? 0) }} {{ Str::plural('transaction', $stats['transactions_today'] ?? 0) }} · auto-reconciled</div>
                            </div>

                            <div class="pb-stat-card pb-float-1 p-4" style="animation-delay: -3s">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Router Status</span>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold {{ $routerLabelClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $routerDotClass }}"></span> {{ $stats['router_health_label'] ?? 'No routers' }}
                                    </span>
                                </div>
                                <div class="mt-2 text-2xl font-bold text-white">{{ number_format($stats['routers_healthy'] ?? 0) }} / {{ number_format($stats['routers_total'] ?? 0) }}</div>
                                <div class="mt-2 text-[11px] text-slate-400">MikroTik · API reachable</div>
                            </div>
                        </div>
                    </div>

                    {{-- Trust badges --}}
                    <div class="pb-fade-up delay-3">
                        <div class="flex flex-wrap gap-2">
                            <span class="pb-pill"><span class="h-1.5 w-1.5 rounded-full bg-cyan-400"></span> MikroTik Ready</span>
                            <span class="pb-pill"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> M-Pesa Enabled</span>
                            <span class="pb-pill"><span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span> PPPoE Support</span>
                            <span class="pb-pill"><span class="h-1.5 w-1.5 rounded-full bg-violet-400"></span> Hotspot Automation</span>
                            <span class="pb-pill"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> Multi-Tenant SaaS</span>
                        </div>
                        <p class="mt-4 text-[11px] text-slate-500">&copy; {{ date('Y') }} PrimeBilling · Built for African ISPs</p>
                    </div>
                </div>
            </aside>

            {{-- ============================================================
                 RIGHT — AUTH CARD
                 ============================================================ --}}
            <main @class(['pb-auth-main relative flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-10', 'min-h-0 py-3 lg:py-4' => $compact ?? false])>

                <a href="{{ url('/') }}" class="absolute right-4 top-4 hidden items-center gap-1.5 rounded-full border border-slate-200/70 bg-white/70 px-3 py-1.5 text-xs font-medium text-slate-600 backdrop-blur transition hover:border-indigo-300 hover:text-indigo-700 sm:inline-flex dark:border-slate-700/70 dark:bg-slate-900/40 dark:text-slate-300 dark:hover:text-indigo-300">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>
                    </svg>
                    Back to website
                </a>

                <div @class(['w-full max-w-md', 'max-w-xl' => $compact ?? false])>

                    {{-- Mobile-only brand header (branding panel hidden on mobile) --}}
                    <div class="pb-fade-up mb-6 flex flex-col items-center text-center lg:hidden">
                        <div class="pb-logo-glow mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-violet-500 to-cyan-400">
                            <svg class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12a10 10 0 0 1 20 0"/>
                                <path d="M5 12a7 7 0 0 1 14 0"/>
                                <path d="M8.5 12a3.5 3.5 0 0 1 7 0"/>
                                <circle cx="12" cy="18" r="1.4" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">PrimeBilling</div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">ISP Operating System</div>
                    </div>

                    {{-- Auth card --}}
                    <div @class(['pb-card pb-fade-up delay-1 rounded-2xl p-6 sm:p-8', '!p-4 sm:!p-5' => $compact ?? false])>
                        {{ $slot }}
                    </div>

                    {{-- Trust strip --}}
                    <div class="pb-auth-trust-strip pb-fade-up delay-2 mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            Secure Login
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
                            </svg>
                            Encrypted Access
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-cyan-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
                            </svg>
                            Role-Based Access
                        </span>
                    </div>

                    <p class="mt-4 text-center text-[11px] text-slate-400 lg:hidden">
                        &copy; {{ date('Y') }} PrimeBilling · Built for African ISPs
                    </p>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: @json(session('success')),
                    confirmButtonColor: '#10b981',
                    timer: 3000,
                    timerProgressBar: true,
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Something went wrong',
                    text: @json(session('error')),
                    confirmButtonColor: '#ef4444',
                });
            @endif
        });
    </script>
</body>
</html>
