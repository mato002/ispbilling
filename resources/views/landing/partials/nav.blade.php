@php
    $appName = config('app.name', 'PrimeBilling');
    $brandColor = $landingIsp?->brandPrimaryHex();
@endphp

<nav class="pb-nav-glass fixed inset-x-0 top-0 z-50 border-b border-slate-200/80">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('welcome') }}" class="flex items-center gap-2.5 text-lg font-extrabold tracking-tight text-slate-900" @if($brandColor) style="color: {{ $brandColor }}" @endif>
            @if($landingIsp && $landingIsp->logoPublicUrl())
                <img src="{{ $landingIsp->logoPublicUrl() }}" alt="{{ $landingIsp->name }}" class="h-9 w-auto max-w-[160px] object-contain">
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-violet-600 text-sm font-black text-white">PB</span>
            @endif
            <span>{{ $landingIsp?->name ?? $appName }}</span>
        </a>

        <div class="hidden items-center gap-6 md:flex">
            @unless($landingIsp)
                <a href="#features" class="text-sm font-medium text-slate-600 hover:text-blue-600">Features</a>
                <a href="#workflows" class="text-sm font-medium text-slate-600 hover:text-blue-600">Workflows</a>
                <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-blue-600">Pricing</a>
                <a href="#faq" class="text-sm font-medium text-slate-600 hover:text-blue-600">FAQ</a>
            @else
                <a href="#plans" class="text-sm font-medium text-slate-600 hover:text-blue-600">Plans</a>
            @endunless

            @auth
                <a href="{{ url('/dashboard') }}" class="pb-btn-secondary !py-2 !px-4">Dashboard</a>
            @else
                @if($landingIsp)
                    <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-600">Sign in</a>
                    <a href="{{ route('customer.register') }}" class="pb-btn-primary !py-2 !px-4">Join now</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-600">Sign in</a>
                    @if (Route::has('register.isp'))
                        <a href="{{ route('register.isp') }}" class="pb-btn-primary !py-2 !px-4">Start free trial</a>
                    @elseif (Route::has('register'))
                        <a href="{{ route('register') }}" class="pb-btn-primary !py-2 !px-4">Start free trial</a>
                    @endif
                @endif
            @endauth
        </div>

        <button id="mobile-menu-btn" type="button" class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-700 md:hidden" aria-controls="mobile-menu" aria-expanded="false">
            <span class="sr-only">Open menu</span>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>

    <div id="mobile-menu" class="hidden border-t border-slate-200 bg-white px-4 py-4 md:hidden">
        <div class="flex flex-col gap-3">
            @unless($landingIsp)
                <a href="#features" class="text-sm font-medium text-slate-700">Features</a>
                <a href="#workflows" class="text-sm font-medium text-slate-700">Workflows</a>
                <a href="#pricing" class="text-sm font-medium text-slate-700">Pricing</a>
                <a href="#faq" class="text-sm font-medium text-slate-700">FAQ</a>
            @else
                <a href="#plans" class="text-sm font-medium text-slate-700">Plans</a>
            @endunless
            @auth
                <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-blue-600">Dashboard</a>
            @else
                @if($landingIsp)
                    <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-slate-700">Sign in</a>
                    <a href="{{ route('customer.register') }}" class="pb-btn-primary w-fit">Join now</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700">Sign in</a>
                    @if (Route::has('register.isp'))
                        <a href="{{ route('register.isp') }}" class="pb-btn-primary w-fit">Start free trial</a>
                    @endif
                @endif
            @endauth
        </div>
    </div>
</nav>
