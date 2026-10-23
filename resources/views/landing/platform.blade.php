@php
    $appName = config('app.name', 'PrimeBilling');
    $registerRoute = Route::has('register.isp') ? route('register.isp') : (Route::has('register') ? route('register') : route('login'));
@endphp

{{-- Hero --}}
<section class="pb-hero-mesh px-4 pb-16 pt-28 sm:px-6 lg:px-8" x-data="landingHero()" @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="if (!$el.contains($event.relatedTarget)) resume()" aria-roledescription="carousel" aria-label="Platform highlights">
    <div class="mx-auto max-w-7xl">
        <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
            <div class="pb-reveal">
                <div class="mb-6 flex flex-wrap gap-2" aria-label="Platform capabilities">
                    <template x-for="badge in slides[active].badges" :key="badge"><span class="rounded-full border border-blue-200/80 bg-white/85 px-3 py-1 text-xs font-bold text-blue-700 shadow-sm" x-text="badge"></span></template>
                </div>

                <div class="pb-hero-slide" aria-live="polite" aria-atomic="true">
                    <template x-for="(slide, index) in slides" :key="slide.emphasis">
                        <div x-show="active === index" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-2" class="pb-hero-slide-content">
                            <h1 class="text-4xl font-extrabold leading-[1.08] tracking-tight sm:text-5xl lg:text-[3.25rem]"><span x-text="slide.lead"></span> <span class="pb-gradient-text" x-text="slide.emphasis"></span> <span x-text="slide.end"></span></h1>
                            <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg" x-text="slide.description"></p>
                        </div>
                    </template>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    @guest
                        <a href="{{ $registerRoute }}" class="pb-btn-primary">Start {{ $defaultTrialDays }}-day free trial</a>
                        <a href="{{ route('login') }}" class="pb-btn-secondary">Operator sign in</a>
                    @else
                        <a href="{{ url('/dashboard') }}" class="pb-btn-primary">Go to dashboard</a>
                    @endguest
                </div>

                <p class="mt-4 text-sm text-slate-500">No credit card required · Typical setup within 24 hours · Unlimited MikroTik routers</p>
                <div class="mt-6 flex items-center gap-3" aria-label="Choose a platform highlight">
                    <button type="button" class="pb-hero-arrow" @click="previous()" aria-label="Previous highlight">←</button>
                    <template x-for="(slide, index) in slides" :key="index"><button type="button" class="pb-hero-dot" :class="{ 'is-active': active === index }" @click="goTo(index)" :aria-label="`Show highlight ${index + 1}`" :aria-current="active === index ? 'true' : null"></button></template>
                    <button type="button" class="pb-hero-arrow" @click="next()" aria-label="Next highlight">→</button>
                    <span class="ml-1 text-xs font-semibold text-slate-500" x-text="`${String(active + 1).padStart(2, '0')} / ${String(slides.length).padStart(2, '0')}`"></span>
                </div>
            </div>

            <div class="pb-reveal lg:pl-4" style="transition-delay: 0.1s">
                @include('landing.partials.dashboard-mockup')
            </div>
        </div>
    </div>
</section>

{{-- Trust stats --}}
<section class="border-y border-slate-200/80 bg-white px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 md:grid-cols-4">
        @foreach ([
            ['Billing modules', '12+', 'End-to-end ISP stack'],
            ['Payment rails', 'M-Pesa', 'STK Push & Paybill'],
            ['Network control', 'Real-time', 'RouterOS automation'],
            ['Free trial', $defaultTrialDays.' days', 'Full platform access'],
        ] as [$label, $value, $sub])
            <div class="pb-reveal text-center md:text-left">
                <p class="pb-stat-value text-slate-900">{{ $value }}</p>
                <p class="mt-1 text-sm font-bold text-slate-800">{{ $label }}</p>
                <p class="text-xs text-slate-500">{{ $sub }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- Payment marquee --}}
<section class="overflow-hidden bg-slate-900 px-4 py-5 sm:px-6">
    <div class="pb-marquee mx-auto max-w-7xl">
        <div class="pb-marquee-track">
            @foreach (array_merge(['M-Pesa STK Push', 'Paybill & Till', 'MikroTik RouterOS', 'Hotspot Vouchers', 'PPPoE RADIUS', 'SMS Campaigns', 'Customer Portal', 'Inventory & QR', 'Provisioning Hub', 'Router Migration'], ['M-Pesa STK Push', 'Paybill & Till', 'MikroTik RouterOS', 'Hotspot Vouchers', 'PPPoE RADIUS', 'SMS Campaigns', 'Customer Portal', 'Inventory & QR', 'Provisioning Hub', 'Router Migration']) as $item)
                <span class="flex shrink-0 items-center gap-2 text-sm font-semibold text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span>{{ $item }}
                </span>
            @endforeach
        </div>
    </div>
</section>

{{-- Problem --}}
<section class="px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mx-auto max-w-3xl text-center pb-reveal">
            <p class="text-sm font-bold uppercase tracking-wider text-blue-600">The problem</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Your ISP should not run on scattered admin work</h2>
            <p class="mt-4 text-slate-600">When payments, router access, invoices, and support live in different places, every growth step creates more manual work.</p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Manual reconnections', 'Teams spend hours checking payments and toggling users by hand after every M-Pesa confirmation.'],
                ['Disconnected billing', 'Spreadsheets and payment statements never show the full subscriber state in real time.'],
                ['Router script chaos', 'PPPoE profiles, hotspot users, and queues drift out of sync with billing records.'],
                ['Weak visibility', 'Owners lack a live view of collections, churn risk, sessions, and network health.'],
                ['Support overload', 'WhatsApp threads replace ticketing — issues get lost and repeat calls pile up.'],
                ['Field gear gaps', 'CPE and inventory live in notebooks while technicians install blind.'],
            ] as [$title, $desc])
                <article class="pb-card pb-card-lift p-6 pb-reveal">
                    <div class="pb-icon-wrap mb-4">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Solution workflow --}}
<section id="workflows" class="bg-white px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mx-auto max-w-3xl text-center pb-reveal">
            <p class="text-sm font-bold uppercase tracking-wider text-blue-600">The solution</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">One control layer for ISP operations</h2>
        </div>

        <div class="mt-14 grid gap-6 md:grid-cols-3 pb-reveal">
            @foreach ([
                ['01', 'Collect', 'STK Push, Paybill confirmations, invoices, and reminders stay tied to each subscriber lifecycle.'],
                ['02', 'Provision', 'MikroTik API syncs PPPoE, hotspot, bandwidth profiles, and IP pools from payment state.'],
                ['03', 'Operate', 'Support tickets, SMS campaigns, inventory, outages, and reports from one live dashboard.'],
            ] as [$step, $title, $desc])
                <div class="relative pb-card p-6">
                    <span class="text-4xl font-black text-blue-100">{{ $step }}</span>
                    <h3 class="mt-2 text-xl font-bold text-slate-900">{{ $title }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-10 grid gap-5 lg:grid-cols-2 pb-reveal">
            <div class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Payment → Access</p>
                <p class="mt-3 text-sm font-medium text-emerald-900">Customer pays → M-Pesa confirmed → Subscription renewed → MikroTik access restored → SMS sent</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Overdue → Suspension</p>
                <p class="mt-3 text-sm font-medium text-amber-900">Invoice overdue → Reminder sent → Grace ends → Auto suspension → Instant reconnect on payment</p>
            </div>
        </div>
    </div>
</section>

{{-- Interactive feature tabs --}}
<section id="features" class="px-4 py-20 sm:px-6 lg:px-8" x-data="landingFeatures()">
    <div class="mx-auto max-w-7xl">
        <div class="mx-auto max-w-3xl text-center pb-reveal">
            <p class="text-sm font-bold uppercase tracking-wider text-blue-600">Platform modules</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Everything your team needs, connected</h2>
        </div>

        <div class="mt-10 flex justify-center pb-reveal">
            <div class="inline-flex max-w-full gap-1 overflow-x-auto rounded-full bg-slate-100 p-1">
                @foreach (['billing' => 'Billing', 'network' => 'Network', 'support' => 'Support', 'operations' => 'Operations'] as $key => $label)
                    <button type="button" class="pb-tab" :class="{ 'is-active': active === '{{ $key }}' }" @click="active = '{{ $key }}'">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="mt-8 pb-card p-8 pb-reveal">
            <template x-for="(tab, key) in tabs" :key="key">
                <div x-show="active === key" x-cloak>
                    <h3 class="text-2xl font-bold text-slate-900" x-text="tab.title"></h3>
                    <p class="mt-2 max-w-2xl text-slate-600" x-text="tab.subtitle"></p>
                    <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                        <template x-for="bullet in tab.bullets" :key="bullet">
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="bullet"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>

        <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 pb-reveal">
            @foreach ([
                'Billing & Invoices', 'M-Pesa Payments', 'MikroTik Routers', 'Hotspot & Vouchers',
                'PPPoE Subscribers', 'Provisioning Hub', 'Router Migration', 'Data Usage',
                'SMS Campaigns', 'Helpdesk Tickets', 'Inventory & QR', 'Technician Field Ops',
                'Outage Compensation', 'Customer Portal', 'Reports & Analytics', 'Multi-tenant SaaS',
            ] as $module)
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-xs font-bold text-slate-700 shadow-sm">{{ $module }}</div>
            @endforeach
        </div>
    </div>
</section>

{{-- PPPoE vs Hotspot --}}
<section class="bg-white px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mx-auto max-w-3xl text-center pb-reveal">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">PPPoE or Hotspot? {{ $appName }} runs both.</h2>
            <p class="mt-4 text-slate-600">Two different ways to sell internet. Same platform. Run one or both from a single workspace.</p>
        </div>

        <div class="mt-12 grid gap-6 lg:grid-cols-2 pb-reveal">
            <article class="pb-compare-popular pb-card p-8">
                <span class="pb-compare-badge">Most popular</span>
                <h3 class="text-xl font-bold text-slate-900">PPPoE</h3>
                <p class="mt-1 text-sm text-slate-500">Fixed home &amp; office internet</p>
                <ul class="mt-6 space-y-3 text-sm text-slate-700">
                    <li class="flex gap-2"><span class="text-blue-600">✓</span> Monthly subscriptions per customer</li>
                    <li class="flex gap-2"><span class="text-blue-600">✓</span> Username &amp; password per subscriber</li>
                    <li class="flex gap-2"><span class="text-blue-600">✓</span> Bandwidth profiles synced to MikroTik</li>
                    <li class="flex gap-2"><span class="text-blue-600">✓</span> M-Pesa auto-renewal and suspension</li>
                    <li class="flex gap-2"><span class="text-blue-600">✓</span> Best for estates, fiber, and offices</li>
                </ul>
            </article>

            <article class="pb-card p-8">
                <h3 class="text-xl font-bold text-slate-900">Hotspot</h3>
                <p class="mt-1 text-sm text-slate-500">Pay-as-you-go Wi‑Fi sessions</p>
                <ul class="mt-6 space-y-3 text-sm text-slate-700">
                    <li class="flex gap-2"><span class="text-violet-600">✓</span> Hourly, daily, and weekly packages</li>
                    <li class="flex gap-2"><span class="text-violet-600">✓</span> Branded captive portal on your router</li>
                    <li class="flex gap-2"><span class="text-violet-600">✓</span> M-Pesa STK Push — pay and connect instantly</li>
                    <li class="flex gap-2"><span class="text-violet-600">✓</span> Voucher generation for counter sales</li>
                    <li class="flex gap-2"><span class="text-violet-600">✓</span> Best for markets, hotels, and campuses</li>
                </ul>
            </article>
        </div>
    </div>
</section>

{{-- Use cases --}}
<section class="px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl pb-reveal">
        <h2 class="text-center text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Built for how local networks actually grow</h2>
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Fiber ISPs', 'PPPoE subscriptions, billing cycles, renewals, and customer records.'],
                ['Hotspot providers', 'Vouchers, captive portals, micropayments, and session controls.'],
                ['Rural WISPs', 'Lean operations, router automation, and SMS payment reminders.'],
                ['Managed Wi‑Fi', 'Hotels, apartments, schools, campuses, and shared spaces.'],
            ] as [$title, $desc])
                <article class="pb-card pb-card-lift p-5">
                    <h3 class="font-bold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $desc }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Onboarding steps --}}
<section class="bg-white px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mx-auto max-w-3xl text-center pb-reveal">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Up and running in 5 steps</h2>
            <p class="mt-4 text-slate-600">Most ISPs go live within 24 hours once router access and payment details are ready.</p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-5 pb-reveal">
            @foreach ([
                ['Add your MikroTik router', 'Connect RouterOS via API — hotspot, PPPoE, and queues sync automatically.'],
                ['Create service plans', 'Set packages with speeds, durations, and pricing for PPPoE or hotspot.'],
                ['Import customers', 'Add manually or via CSV. Each subscriber gets a unique payment reference.'],
                ['Connect M-Pesa', 'Configure Paybill or STK Push — payments reconcile and renewals run themselves.'],
                ['Launch & monitor', 'Run billing, support, and network access from one dashboard.'],
            ] as $i => [$title, $desc])
                <div class="pb-card p-5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">{{ $i + 1 }}</span>
                    <h3 class="mt-3 text-sm font-bold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Pricing --}}
<section id="pricing" class="px-4 py-20 sm:px-6 lg:px-8" x-data="landingPricing({ defaultTrialDays: {{ $defaultTrialDays }} })">
    <div class="mx-auto max-w-7xl">
        <div class="mx-auto max-w-3xl text-center pb-reveal">
            <p class="text-sm font-bold uppercase tracking-wider text-blue-600">Pricing</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Transparent pricing that follows your footprint</h2>
            <p class="mt-4 text-slate-600">Start with a {{ $defaultTrialDays }}-day free trial. Pay based on your subscriber count and hotspot revenue.</p>
        </div>

        <div class="mt-12 grid gap-8 lg:grid-cols-2 pb-reveal">
            {{-- Calculator --}}
            <div class="pb-card p-8">
                <h3 class="text-lg font-bold text-slate-900">Estimate your monthly bill</h3>
                <p class="mt-1 text-sm text-slate-500">Drag the controls to match your network size.</p>

                <div class="mt-8 space-y-8">
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-slate-700">PPPoE subscribers</span>
                            <span class="font-bold text-blue-600" x-text="pppoeUsers + ' users'"></span>
                        </div>
                        <input type="range" class="pb-range mt-3" min="0" max="500" step="5" x-model.number="pppoeUsers" @input="updateRangeBg($event.target)">
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-slate-700">Monthly hotspot revenue</span>
                            <span class="font-bold text-blue-600" x-text="'KSh ' + format(hotspotRevenue)"></span>
                        </div>
                        <input type="range" class="pb-range mt-3" min="0" max="100000" step="500" x-model.number="hotspotRevenue" @input="updateRangeBg($event.target)">
                    </div>
                </div>

                <dl class="mt-8 space-y-2 border-t border-slate-100 pt-6 text-sm">
                    <div class="flex justify-between text-slate-600"><dt>PPPoE (<span x-text="pppoeUsers"></span> × KSh 25)</dt><dd class="font-semibold" x-text="'KSh ' + format(pppoeCost)"></dd></div>
                    <div class="flex justify-between text-slate-600"><dt>Hotspot (3% of revenue)</dt><dd class="font-semibold" x-text="'KSh ' + format(hotspotCost)"></dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-3 text-base font-bold text-slate-900"><dt>Monthly estimate</dt><dd x-text="'KSh ' + format(monthly) + '/mo'"></dd></div>
                </dl>
                <p class="mt-3 text-xs text-slate-400">KSh 500/month minimum if usage is lower. One-time activation after trial.</p>
            </div>

            {{-- Plans from DB or defaults --}}
            <div class="space-y-5">
                @if($platformSaasPlans->isNotEmpty())
                    @foreach($platformSaasPlans as $plan)
                        <div @class(['pb-card p-6', 'pb-compare-popular' => $loop->first])>
                            @if($loop->first)<span class="pb-compare-badge">Recommended</span>@endif
                            <h3 class="text-xl font-bold text-slate-900">{{ $plan->name }}</h3>
                            @if($plan->description)<p class="mt-1 text-sm text-slate-600">{{ $plan->description }}</p>@endif
                            <p class="mt-4 text-3xl font-extrabold text-slate-900">
                                KSh {{ number_format((float) $plan->monthly_price, 0) }}
                                <span class="text-sm font-normal text-slate-500">/ month</span>
                            </p>
                            @if($plan->max_customers)
                                <p class="mt-1 text-xs text-slate-500">Up to {{ number_format($plan->max_customers) }} subscribers</p>
                            @endif
                            @if($plan->trial_days)
                                <p class="mt-2 text-sm font-semibold text-blue-600">{{ $plan->trial_days }}-day free trial included</p>
                            @endif
                            @if(is_array($plan->features) && count($plan->features))
                                <ul class="mt-4 space-y-1.5 text-sm text-slate-600">
                                    @foreach(array_keys(array_filter($plan->features)) as $feature)
                                        <li class="flex gap-2"><span class="text-blue-600">✓</span> {{ str_replace('_', ' ', ucfirst($feature)) }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @guest
                                <a href="{{ $registerRoute }}" class="pb-btn-primary mt-6 w-full">Start trial</a>
                            @endguest
                        </div>
                    @endforeach
                @else
                    <div class="pb-compare-popular pb-card p-6">
                        <span class="pb-compare-badge">Pay as you grow</span>
                        <h3 class="text-xl font-bold text-slate-900">Metered</h3>
                        <p class="mt-1 text-sm text-slate-600">Perfect for growing ISPs who want automation without heavy fixed overhead.</p>
                        <p class="mt-4 text-3xl font-extrabold text-slate-900">KSh 25 <span class="text-sm font-normal text-slate-500">/ PPPoE subscriber</span></p>
                        <p class="mt-1 text-sm text-slate-600">+ 3% hotspot revenue · KSh 500 monthly minimum</p>
                        <ul class="mt-4 space-y-1.5 text-sm text-slate-600">
                            <li class="flex gap-2"><span class="text-blue-600">✓</span> M-Pesa STK Push integration</li>
                            <li class="flex gap-2"><span class="text-blue-600">✓</span> Unlimited MikroTik routers</li>
                            <li class="flex gap-2"><span class="text-blue-600">✓</span> Customer self-service portal</li>
                            <li class="flex gap-2"><span class="text-blue-600">✓</span> Full platform modules</li>
                        </ul>
                        @guest
                            <a href="{{ $registerRoute }}" class="pb-btn-primary mt-6 w-full">Start {{ $defaultTrialDays }}-day trial</a>
                        @endguest
                    </div>
                    <div class="pb-card p-6">
                        <h3 class="text-xl font-bold text-slate-900">Enterprise</h3>
                        <p class="mt-1 text-sm text-slate-600">White-label portals, dedicated support, custom integrations, and SLA options.</p>
                        <p class="mt-4 text-2xl font-extrabold text-slate-900">Custom</p>
                        <a href="{{ route('login') }}" class="pb-btn-secondary mt-6 w-full">Contact sales</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="bg-white px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="text-center pb-reveal">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Questions before you launch?</h2>
        </div>

        <div class="mt-10 space-y-3 pb-reveal">
            @foreach ([
                ['Does it support MikroTik?', 'Yes. Built around RouterOS hotspot, PPPoE, queues, and API-based provisioning including fleet migration and backups.'],
                ['Does it support M-Pesa?', 'Yes. STK Push, Paybill, and Till workflows with real-time payment confirmation and automatic subscriber activation.'],
                ['Can I automate suspensions?', 'Yes. Overdue invoices trigger reminders, grace periods, auto suspension, and instant reconnection when payment is received.'],
                ['Can customers self-serve?', 'Yes. Subscribers can renew, view usage, download receipts, and raise support tickets from the customer portal.'],
                ['Do you support inventory and field techs?', 'Yes. Track CPE with QR codes, assign devices to technicians, and bind equipment to customer installations.'],
                ['Is there a free trial?', 'Yes. Start with a '.$defaultTrialDays.'-day trial with full platform access — no credit card required.'],
            ] as [$q, $a])
                <details class="pb-faq-item pb-card group">
                    <summary class="flex cursor-pointer list-none items-center justify-between p-5 font-semibold text-slate-900">
                        {{ $q }}
                        <svg class="pb-faq-chevron h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <p class="border-t border-slate-100 px-5 pb-5 pt-3 text-sm leading-relaxed text-slate-600">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="px-4 pb-20 sm:px-6 lg:px-8">
    <div class="pb-cta-band mx-auto max-w-7xl rounded-3xl px-8 py-14 text-center text-white sm:px-12 pb-reveal">
        <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Put billing and network access on autopilot</h2>
        <p class="mx-auto mt-4 max-w-2xl text-blue-100">Join ISPs using {{ $appName }} to automate collections, MikroTik provisioning, and customer support from one operating system.</p>
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            @guest
                <a href="{{ $registerRoute }}" class="inline-flex rounded-xl bg-white px-8 py-3.5 text-sm font-bold text-blue-700 shadow-lg hover:bg-blue-50">Start {{ $defaultTrialDays }}-day free trial</a>
                <a href="{{ route('login') }}" class="inline-flex rounded-xl border-2 border-white/40 px-8 py-3.5 text-sm font-bold text-white hover:bg-white/10">Operator sign in</a>
            @else
                <a href="{{ url('/dashboard') }}" class="inline-flex rounded-xl bg-white px-8 py-3.5 text-sm font-bold text-blue-700 shadow-lg hover:bg-blue-50">Go to dashboard</a>
            @endguest
        </div>
    </div>
</section>
