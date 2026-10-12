@php
    $appName = config('app.name', 'PrimeBilling');
    $platformEmail = config('mail.from.address');
    if ($platformEmail && str_contains($platformEmail, 'example.com')) $platformEmail = null;
    $trialUrl = Route::has('register.isp') ? route('register.isp') : (Route::has('register') ? route('register') : route('login'));
@endphp

<footer class="relative overflow-hidden bg-slate-950 text-white">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -right-32 -top-40 h-96 w-96 rounded-full bg-blue-600/20 blur-3xl"></div>
        <div class="absolute -bottom-48 left-1/4 h-96 w-96 rounded-full bg-violet-600/15 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 pb-7 pt-12 sm:px-6 sm:pt-16 lg:px-8">
        @unless($landingIsp)
            <div class="mb-14 overflow-hidden rounded-[28px] border border-white/15 bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-700 p-6 shadow-2xl shadow-blue-950/30 sm:p-9 lg:p-10">
                <div class="flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[.15em] text-blue-100">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>Built for growing ISP teams
                        </span>
                        <h2 class="mt-4 text-2xl font-extrabold tracking-tight sm:text-3xl">Run your ISP with more clarity and less busywork.</h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-blue-100 sm:text-base">Bring billing, subscriber access, payments, and network operations into one workspace.</p>
                    </div>
                    <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
                        <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-extrabold text-blue-700 shadow-lg shadow-blue-950/20 transition hover:-translate-y-0.5 hover:bg-blue-50">Start your free trial <span aria-hidden="true">→</span></a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-white/35 bg-white/5 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">Operator sign in</a>
                    </div>
                </div>
            </div>
        @endunless

        <div class="grid grid-cols-2 gap-x-8 gap-y-10 border-b border-white/10 pb-10 sm:grid-cols-3 lg:grid-cols-12 lg:gap-8">
            <div class="col-span-2 sm:col-span-3 lg:col-span-4">
                <a href="{{ route('welcome') }}" class="inline-flex items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                    @if($landingIsp && $landingIsp->logoPublicUrl())
                        <img src="{{ $landingIsp->logoPublicUrl() }}" alt="{{ $landingIsp->name }}" class="h-11 max-w-[150px] object-contain">
                    @else
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-violet-500 text-xs font-black text-white shadow-lg shadow-blue-500/20">PB</span>
                    @endif
                    <span class="text-lg font-extrabold tracking-tight">{{ $landingIsp?->name ?? $appName }}</span>
                </a>
                <p class="mt-4 max-w-sm text-sm leading-6 text-slate-400">
                    @if($landingIsp)
                        Your customer portal for plans, payments, account access, and support from {{ $landingIsp->name }}.
                    @else
                        The operating workspace for modern internet service providers — from customer billing to network operations.
                    @endif
                </p>
                @unless($landingIsp)
                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-[11px] font-semibold text-slate-300">MikroTik ready</span>
                        <span class="rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-[11px] font-semibold text-slate-300">M-Pesa enabled</span>
                    </div>
                @endunless
            </div>

            <div class="lg:col-span-2">
                <h3 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-slate-500">{{ $landingIsp ? 'Your account' : 'Platform' }}</h3>
                <ul class="mt-4 space-y-3 text-sm">
                    @if($landingIsp)
                        <li><a href="#plans" class="text-slate-300 transition hover:text-white">Internet plans</a></li>
                        <li><a href="{{ route('customer.login') }}" class="text-slate-300 transition hover:text-white">Customer sign in</a></li>
                        <li><a href="{{ route('customer.register') }}" class="text-slate-300 transition hover:text-white">Create an account</a></li>
                    @else
                        <li><a href="#features" class="text-slate-300 transition hover:text-white">Features</a></li>
                        <li><a href="#workflows" class="text-slate-300 transition hover:text-white">Workflows</a></li>
                        <li><a href="#pricing" class="text-slate-300 transition hover:text-white">Pricing</a></li>
                        <li><a href="#faq" class="text-slate-300 transition hover:text-white">FAQs</a></li>
                    @endif
                </ul>
            </div>

            @unless($landingIsp)
                <div class="lg:col-span-2">
                    <h3 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-slate-500">Integrations</h3>
                    <ul class="mt-4 space-y-3 text-sm text-slate-300">
                        <li>MikroTik RouterOS</li><li>M-Pesa payments</li><li>SMS &amp; email</li><li>Hotspot &amp; PPPoE</li>
                    </ul>
                </div>
            @endunless

            <div class="{{ $landingIsp ? 'lg:col-span-3' : 'lg:col-span-2' }}">
                <h3 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-slate-500">Resources</h3>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('documentation') }}" class="text-slate-300 transition hover:text-white">Documentation</a></li>
                    <li><a href="{{ route('terms') }}" class="text-slate-300 transition hover:text-white">Terms of service</a></li>
                    <li><a href="{{ route('privacy') }}" class="text-slate-300 transition hover:text-white">Privacy policy</a></li>
                </ul>
            </div>

            <div class="{{ $landingIsp ? 'lg:col-span-3' : 'lg:col-span-2' }}">
                <h3 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-slate-500">{{ $landingIsp ? 'Contact your ISP' : 'Get started' }}</h3>
                <ul class="mt-4 space-y-3 text-sm">
                    @if($landingIsp)
                        @if($landingIsp->phone)<li><a href="tel:{{ preg_replace('/\s+/', '', $landingIsp->phone) }}" class="text-slate-300 transition hover:text-white">{{ $landingIsp->phone }}</a></li>@endif
                        @if($landingIsp->email)<li><a href="mailto:{{ $landingIsp->email }}" class="break-all text-slate-300 transition hover:text-white">{{ $landingIsp->email }}</a></li>@endif
                        <li><a href="{{ route('customer.login') }}" class="text-slate-300 transition hover:text-white">Manage your account</a></li>
                    @else
                        <li><a href="{{ $trialUrl }}" class="text-slate-300 transition hover:text-white">Start free trial</a></li>
                        <li><a href="{{ route('login') }}" class="text-slate-300 transition hover:text-white">Operator sign in</a></li>
                        @if($platformEmail)<li><a href="mailto:{{ $platformEmail }}" class="break-all text-slate-300 transition hover:text-white">{{ $platformEmail }}</a></li>@endif
                    @endif
                </ul>
            </div>
        </div>

        <div class="flex flex-col gap-4 pt-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ $landingIsp?->name ?? $appName }}. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('privacy') }}" class="transition hover:text-slate-300">Privacy</a>
                <a href="{{ route('terms') }}" class="transition hover:text-slate-300">Terms</a>
                <a href="#top" class="inline-flex items-center gap-1 font-semibold text-slate-400 transition hover:text-white">Back to top <span aria-hidden="true">↑</span></a>
            </div>
        </div>
    </div>
</footer>
