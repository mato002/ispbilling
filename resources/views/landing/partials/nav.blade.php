@php
    $appName = config('app.name', 'PrimeBilling');
    $brandColor = $landingIsp?->brandPrimaryHex();
    $trialUrl = Route::has('register.isp') ? route('register.isp') : (Route::has('register') ? route('register') : route('login'));
@endphp

<header id="top" class="fixed inset-x-0 top-0 z-50 px-3 pt-3 sm:px-5 lg:px-8">
    <nav class="pb-nav-glass mx-auto max-w-7xl rounded-2xl border border-white/70 shadow-[0_12px_35px_-24px_rgba(15,23,42,.35)] ring-1 ring-slate-900/5" aria-label="Main navigation">
        <div class="flex min-h-[68px] items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('welcome') }}" class="group flex min-w-0 items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" @if($brandColor) style="color: {{ $brandColor }}" @endif>
                @if($landingIsp && $landingIsp->logoPublicUrl())
                    <img src="{{ $landingIsp->logoPublicUrl() }}" alt="{{ $landingIsp->name }}" class="h-10 w-auto max-w-[148px] object-contain">
                @else
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[14px] bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-600 text-xs font-black tracking-tight text-white shadow-lg shadow-indigo-600/20">PB</span>
                @endif
                <span class="min-w-0">
                    <span class="block truncate text-base font-extrabold tracking-tight text-slate-950 sm:text-[17px]">{{ $landingIsp?->name ?? $appName }}</span>
                    @unless($landingIsp)<span class="hidden text-[10px] font-semibold uppercase tracking-[.16em] text-slate-400 sm:block">ISP operations platform</span>@endunless
                </span>
            </a>

            <div class="hidden items-center gap-1 md:flex">
                @unless($landingIsp)
                    <a href="{{ route('landing.features') }}" @class(['rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-slate-100/80 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('landing.features'), 'text-slate-600' => !request()->routeIs('landing.features')])>Features</a>
                    <a href="{{ route('landing.workflows') }}" @class(['rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-slate-100/80 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('landing.workflows'), 'text-slate-600' => !request()->routeIs('landing.workflows')])>Workflows</a>
                    <a href="{{ route('landing.pricing') }}" @class(['rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-slate-100/80 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('landing.pricing'), 'text-slate-600' => !request()->routeIs('landing.pricing')])>Pricing</a>
                    <a href="{{ route('landing.faq') }}" @class(['rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-slate-100/80 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('landing.faq'), 'text-slate-600' => !request()->routeIs('landing.faq')])>FAQ</a>
                @else
                    <a href="#plans" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100/80 hover:text-blue-700">Internet plans</a>
                @endunless
            </div>

            <div class="hidden shrink-0 items-center gap-3 md:flex">
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-sm font-bold text-slate-700 transition hover:text-blue-700">Dashboard</a>
                @else
                    @if($landingIsp)
                        <a href="{{ route('customer.login') }}" class="text-sm font-bold text-slate-700 transition hover:text-blue-700">Sign in</a>
                        <a href="{{ route('customer.register') }}" class="pb-btn-primary !rounded-xl !px-4 !py-2.5">Join now <span aria-hidden="true">→</span></a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-bold text-slate-700 transition hover:text-blue-700">Sign in</a>
                        <a href="{{ $trialUrl }}" class="pb-btn-primary !rounded-xl !px-4 !py-2.5">Start free trial <span aria-hidden="true">→</span></a>
                    @endif
                @endauth
            </div>

            <button id="mobile-menu-btn" type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-blue-200 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 md:hidden" aria-controls="mobile-menu" aria-expanded="false" aria-label="Open menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>

        <div id="mobile-menu" class="hidden border-t border-slate-200/80 bg-white/95 px-4 pb-4 pt-3 md:hidden">
            <div class="flex flex-col gap-1">
                @unless($landingIsp)
                    <a href="{{ route('landing.features') }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Features</a>
                    <a href="{{ route('landing.workflows') }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Workflows</a>
                    <a href="{{ route('landing.pricing') }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Pricing</a>
                    <a href="{{ route('landing.faq') }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">FAQ</a>
                @else
                    <a href="#plans" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Internet plans</a>
                @endunless
                @auth
                    <a href="{{ url('/dashboard') }}" class="rounded-xl px-3 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-50">Dashboard</a>
                @else
                    @if($landingIsp)
                        <a href="{{ route('customer.login') }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Sign in</a>
                        <a href="{{ route('customer.register') }}" class="pb-btn-primary mt-2 justify-center">Join now <span aria-hidden="true">→</span></a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Sign in</a>
                        <a href="{{ $trialUrl }}" class="pb-btn-primary mt-2 justify-center">Start free trial <span aria-hidden="true">→</span></a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>
</header>
