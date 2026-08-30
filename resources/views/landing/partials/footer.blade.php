@php
    $appName = config('app.name', 'PrimeBilling');
@endphp

<footer class="border-t border-slate-200 bg-white px-4 py-12 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="grid grid-cols-2 gap-8 md:grid-cols-5">
            <div class="col-span-2 md:col-span-1">
                <p class="font-bold text-slate-900">{{ $landingIsp?->name ?? $appName }}</p>
                <p class="mt-2 text-sm text-slate-500">
                    @if($landingIsp)
                        Customer portal powered by {{ $appName }}.
                    @else
                        The operating system for modern ISP teams — billing, network, and growth in one platform.
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Product</p>
                <div class="mt-3 flex flex-col gap-2 text-sm text-slate-600">
                    @if($landingIsp)
                        <a href="#plans" class="hover:text-blue-600">Plans</a>
                        <a href="{{ route('customer.login') }}" class="hover:text-blue-600">Sign in</a>
                        <a href="{{ route('customer.register') }}" class="hover:text-blue-600">Join now</a>
                    @else
                        <a href="#features" class="hover:text-blue-600">Features</a>
                        <a href="#pricing" class="hover:text-blue-600">Pricing</a>
                        <a href="#workflows" class="hover:text-blue-600">Workflows</a>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Integrations</p>
                <div class="mt-3 flex flex-col gap-2 text-sm text-slate-600">
                    <span>MikroTik RouterOS</span>
                    <span>M-Pesa STK Push</span>
                    <span>SMS &amp; Email</span>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Resources</p>
                <div class="mt-3 flex flex-col gap-2 text-sm text-slate-600">
                    <a href="{{ route('documentation') }}" class="hover:text-blue-600">Documentation</a>
                    <a href="{{ route('terms') }}" class="hover:text-blue-600">Terms</a>
                    <a href="{{ route('privacy') }}" class="hover:text-blue-600">Privacy</a>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Support</p>
                <div class="mt-3 flex flex-col gap-2 text-sm text-slate-600">
                    @if($landingIsp)
                        @if($landingIsp->phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $landingIsp->phone) }}" class="hover:text-blue-600">{{ $landingIsp->phone }}</a>
                        @endif
                        @if($landingIsp->email)
                            <a href="mailto:{{ $landingIsp->email }}" class="hover:text-blue-600">{{ $landingIsp->email }}</a>
                        @endif
                        <a href="{{ route('customer.login') }}" class="hover:text-blue-600">Customer sign in</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-blue-600">Operator sign in</a>
                        @if (Route::has('register.isp'))
                            <a href="{{ route('register.isp') }}" class="hover:text-blue-600">Start free trial</a>
                        @endif
                        @if(config('mail.from.address'))
                            <a href="mailto:{{ config('mail.from.address') }}" class="hover:text-blue-600">{{ config('mail.from.address') }}</a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <p class="mt-10 border-t border-slate-100 pt-6 text-xs text-slate-400">&copy; {{ date('Y') }} {{ $landingIsp?->name ?? $appName }}. All rights reserved.</p>
    </div>
</footer>
