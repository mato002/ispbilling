@php
    $appName = config('app.name', 'PrimeBilling');
@endphp

{{-- Tenant hero --}}
<section class="pb-tenant-hero px-4 pb-16 pt-28 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div class="pb-reveal">
                <div class="mb-5 flex flex-wrap gap-2">
                    @foreach (['Internet packages', 'Self-service portal', 'Secure payments', 'Local ISP'] as $badge)
                        <span class="rounded-full border border-blue-200 bg-white px-3 py-1 text-xs font-bold text-blue-700 shadow-sm">{{ $badge }}</span>
                    @endforeach
                </div>

                <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                    {{ $landingIsp->name }} — internet you can manage online
                </h1>

                <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                    Choose a package, pay securely, and sign in to view usage, invoices, and support. For technical help, contact {{ $landingIsp->name }} directly.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="pb-btn-primary">Go to dashboard</a>
                    @else
                        <a href="{{ route('customer.register') }}" class="pb-btn-primary">Join now</a>
                        <a href="{{ route('customer.login') }}" class="pb-btn-secondary">Sign in</a>
                    @endauth
                </div>

                @if($landingIsp->phone || $landingIsp->email)
                    <div class="mt-6 flex flex-wrap gap-4 text-sm text-slate-600">
                        @if($landingIsp->phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $landingIsp->phone) }}" class="flex items-center gap-2 font-medium hover:text-blue-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $landingIsp->phone }}
                            </a>
                        @endif
                        @if($landingIsp->email)
                            <a href="mailto:{{ $landingIsp->email }}" class="flex items-center gap-2 font-medium hover:text-blue-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                {{ $landingIsp->email }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="pb-reveal">
                <div class="pb-card overflow-hidden p-0">
                    <div class="bg-gradient-to-br from-blue-600 to-violet-600 px-6 py-8 text-white">
                        <p class="text-sm font-semibold text-blue-100">Customer portal</p>
                        <p class="mt-1 text-2xl font-bold">Manage your account online</p>
                    </div>
                    <ul class="space-y-0 divide-y divide-slate-100 p-6 text-sm text-slate-700">
                        <li class="flex items-center gap-3 py-3"><span class="text-blue-600">✓</span> View and pay invoices</li>
                        <li class="flex items-center gap-3 py-3"><span class="text-blue-600">✓</span> Check data usage</li>
                        <li class="flex items-center gap-3 py-3"><span class="text-blue-600">✓</span> Download receipts</li>
                        <li class="flex items-center gap-3 py-3"><span class="text-blue-600">✓</span> Raise support tickets</li>
                        <li class="flex items-center gap-3 py-3"><span class="text-blue-600">✓</span> Renew your subscription</li>
                    </ul>
                    @guest
                        <div class="border-t border-slate-100 p-6">
                            <a href="{{ route('customer.register') }}" class="pb-btn-primary w-full">Create your account</a>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Plans --}}
<section id="plans" class="bg-white px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mx-auto max-w-2xl text-center pb-reveal">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Packages from {{ $landingIsp->name }}</h2>
            <p class="mt-4 text-slate-600">
                @if($landingPlans->isNotEmpty())
                    Choose a plan and join online in minutes.
                @else
                    Plans are being updated. Contact {{ $landingIsp->name }} for current availability.
                @endif
            </p>
        </div>

        @if($landingPlans->isNotEmpty())
            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3 pb-reveal">
                @foreach ($landingPlans as $plan)
                    <article @class(['pb-card p-6 pb-card-lift', 'pb-compare-popular' => $loop->first])>
                        @if($loop->first)<span class="pb-compare-badge">Popular</span>@endif
                        <h3 class="text-xl font-bold text-slate-900">{{ $plan->name }}</h3>
                        @if($plan->description)
                            <p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($plan->description, 120) }}</p>
                        @endif
                        <p class="mt-4 text-3xl font-extrabold text-slate-900">
                            {{ $landingIsp->currency }} {{ number_format((float) $plan->price, 0) }}
                            <span class="text-sm font-normal text-slate-500">/ {{ $plan->duration_days }} days</span>
                        </p>
                        <ul class="mt-4 space-y-2 text-sm text-slate-600">
                            @if($plan->access_type)<li class="flex gap-2"><span class="text-blue-600">✓</span> {{ strtoupper($plan->access_type) }} access</li>@endif
                            @if($plan->download_speed_kbps)<li class="flex gap-2"><span class="text-blue-600">✓</span> Speed: {{ $plan->formatted_speed }}</li>@endif
                            @if($plan->billing_type)<li class="flex gap-2"><span class="text-blue-600">✓</span> {{ $plan->billing_type }} billing</li>@endif
                        </ul>
                        @guest
                            <a href="{{ route('customer.register') }}" class="pb-btn-primary mt-6 w-full">Get started</a>
                        @endguest
                    </article>
                @endforeach
            </div>
        @else
            <div class="mx-auto mt-10 max-w-lg rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center pb-reveal">
                <p class="font-semibold text-slate-800">No public plans published yet</p>
                <p class="mt-2 text-sm text-slate-600">Reach out to {{ $landingIsp->name }} for package details and availability.</p>
                @if($landingIsp->phone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $landingIsp->phone) }}" class="pb-btn-primary mt-6 inline-flex">Call {{ $landingIsp->name }}</a>
                @endif
            </div>
        @endif
    </div>
</section>

{{-- CTA --}}
<section class="px-4 pb-20 sm:px-6 lg:px-8">
    <div class="pb-cta-band mx-auto max-w-4xl rounded-3xl px-8 py-12 text-center text-white pb-reveal">
        <h2 class="text-2xl font-extrabold sm:text-3xl">Ready to get connected?</h2>
        <p class="mx-auto mt-3 max-w-lg text-blue-100">Create your account, choose a plan, and manage everything from the {{ $landingIsp->name }} customer portal.</p>
        <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
            @guest
                <a href="{{ route('customer.register') }}" class="inline-flex rounded-xl bg-white px-8 py-3.5 text-sm font-bold text-blue-700 hover:bg-blue-50">Join now</a>
                <a href="{{ route('customer.login') }}" class="inline-flex rounded-xl border-2 border-white/40 px-8 py-3.5 text-sm font-bold text-white hover:bg-white/10">Sign in</a>
            @endguest
        </div>
        <p class="mt-6 text-xs text-blue-200/80">Powered by {{ $appName }}</p>
    </div>
</section>
