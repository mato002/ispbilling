<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-landing-seo-meta :isp="$landingIsp" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased"@if($landingIsp?->brandPrimaryHex()) style="--tenant-brand: {{ $landingIsp->brandPrimaryHex() }};"@endif>
        <div class="absolute inset-x-0 top-0 -z-10 h-[30rem] bg-gradient-to-br from-blue-600/10 via-violet-500/10 to-white"></div>

        <nav class="fixed top-0 inset-x-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('welcome') }}" class="flex items-center gap-2 text-xl font-extrabold tracking-tight text-blue-700" @if($landingIsp?->brandPrimaryHex()) style="color: var(--tenant-brand)"@endif>
                    @if($landingIsp && $landingIsp->logoPublicUrl())
                        <img src="{{ $landingIsp->logoPublicUrl() }}" alt="{{ $landingIsp->name }}" class="h-9 w-auto max-w-[160px] object-contain">
                    @endif
                    <span>{{ $landingIsp->name ?? config('app.name', 'PrimeBilling') }}</span>
                </a>

                <div class="hidden items-center gap-7 md:flex">
                    <a href="#features" class="text-sm font-medium text-slate-600 hover:text-blue-700">Features</a>
                    <a href="#operations" class="text-sm font-medium text-slate-600 hover:text-blue-700">Operations</a>
                    <a href="#integrations" class="text-sm font-medium text-slate-600 hover:text-blue-700">Integrations</a>
                    <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-blue-700">How it Works</a>
                    <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-blue-700">Pricing</a>

                    @auth
                        <a href="{{ url('/dashboard') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-blue-600 hover:text-blue-700">Dashboard</a>
                    @else
                        @if($landingIsp)
                            <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Customer log in</a>
                            <a href="{{ route('customer.register') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Join now</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Operator log in</a>
                            @if (Route::has('register.isp'))
                                <a href="{{ route('register.isp') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Register your ISP</a>
                            @elseif (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Get Started</a>
                            @endif
                        @endif
                    @endauth
                </div>

                <button id="mobile-menu-btn" type="button" class="inline-flex items-center justify-center rounded-md border border-slate-200 p-2 text-slate-700 md:hidden" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open menu</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <div id="mobile-menu" class="hidden border-t border-slate-200 bg-white px-4 py-4 md:hidden">
                <div class="flex flex-col gap-3">
                    <a href="#features" class="text-sm font-medium text-slate-700">Features</a>
                    <a href="#operations" class="text-sm font-medium text-slate-700">Operations</a>
                    <a href="#integrations" class="text-sm font-medium text-slate-700">Integrations</a>
                    <a href="#how-it-works" class="text-sm font-medium text-slate-700">How it Works</a>
                    <a href="#pricing" class="text-sm font-medium text-slate-700">Pricing</a>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-blue-700">Dashboard</a>
                    @else
                        @if($landingIsp)
                            <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-slate-700">Customer log in</a>
                            <a href="{{ route('customer.register') }}" class="inline-flex w-fit rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Join now</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700">Operator log in</a>
                            @if (Route::has('register.isp'))
                                <a href="{{ route('register.isp') }}" class="inline-flex w-fit rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Register your ISP</a>
                            @elseif (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex w-fit rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Get Started</a>
                            @endif
                        @endif
                    @endauth
                </div>
            </div>
        </nav>

        <main class="overflow-x-hidden pt-24">
            <section class="px-4 pb-14 pt-12 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                        <div>
                            <div class="mb-5 flex flex-wrap gap-2">
                                @if($landingIsp)
                                    @foreach (['Internet packages', 'Hotspot & billing', 'Local ISP', 'Self-service'] as $badge)
                                        <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $badge }}</span>
                                    @endforeach
                                @else
                                    @foreach (['MikroTik Ready', 'M-Pesa Payments', 'Hotspot & PPPoE', 'Automated Billing', 'Built for ISPs'] as $badge)
                                        <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $badge }}</span>
                                    @endforeach
                                @endif
                            </div>

                            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                                @if($landingIsp)
                                    {{ $landingIsp->name }} — internet you can manage online
                                @else
                                    Run Your Entire ISP From One Platform
                                @endif
                            </h1>

                            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                                @if($landingIsp)
                                    Choose a package, pay securely, and sign in to view usage and invoices. For technical support, contact {{ $landingIsp->name }} directly.
                                @else
                                    Billing, MikroTik automation, M-Pesa payments, and customer management in one system built for African and local ISPs.
                                @endif
                            </p>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                                @unless($landingIsp)
                                    Move from manual chaos to automated operations: manage subscriptions, automate invoices, monitor collections, trigger suspensions/reconnections, and track business outcomes without juggling spreadsheets, router scripts, and manual follow-ups.
                                @endunless
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-md hover:bg-blue-700">Go to Dashboard</a>
                                @else
                                    @if($landingIsp)
                                        <a href="{{ route('customer.register') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-md hover:bg-blue-700">Join now</a>
                                        <a href="{{ route('customer.login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-6 py-3 text-sm font-bold text-slate-700 hover:border-blue-600 hover:text-blue-700">Customer log in</a>
                                    @else
                                        @if (Route::has('register.isp'))
                                            <a href="{{ route('register.isp') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-md hover:bg-blue-700">Register your ISP</a>
                                        @elseif (Route::has('register'))
                                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-md hover:bg-blue-700">Start free trial</a>
                                        @endif
                                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-6 py-3 text-sm font-bold text-slate-700 hover:border-blue-600 hover:text-blue-700">Operator log in</a>
                                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl px-2 py-3 text-sm font-semibold text-blue-700 hover:text-blue-800">Talk to sales</a>
                                    @endif
                                @endauth
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-lg sm:p-6">
                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-slate-800">Live Operations Snapshot</h2>
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Mock Preview</span>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
                                    <p class="text-xs text-slate-500">Revenue Trend (7 Days)</p>
                                    <div class="mt-3 flex h-16 items-end gap-2">
                                        <span class="w-1/6 rounded-t bg-blue-200" style="height: 38%;"></span>
                                        <span class="w-1/6 rounded-t bg-blue-300" style="height: 58%;"></span>
                                        <span class="w-1/6 rounded-t bg-blue-400" style="height: 44%;"></span>
                                        <span class="w-1/6 rounded-t bg-blue-500" style="height: 70%;"></span>
                                        <span class="w-1/6 rounded-t bg-blue-600" style="height: 62%;"></span>
                                        <span class="w-1/6 rounded-t bg-violet-600" style="height: 82%;"></span>
                                    </div>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs text-slate-500">Total Customers</p>
                                    <p class="mt-1 text-2xl font-bold text-slate-900">1,286</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs text-slate-500">Monthly Revenue</p>
                                    <p class="mt-1 text-2xl font-bold text-slate-900">KES 2.48M</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs text-slate-500">Active Subscriptions</p>
                                    <p class="mt-1 text-2xl font-bold text-slate-900">1,094</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs text-slate-500">Overdue Invoices</p>
                                    <p class="mt-1 text-2xl font-bold text-amber-600">83</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs text-slate-500">Online Sessions</p>
                                    <p class="mt-1 text-2xl font-bold text-slate-900">742</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs text-slate-500">Router Status</p>
                                    <p class="mt-1 text-2xl font-bold text-emerald-600">18/19 Online</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Your Team Should Not Spend The Day Chasing Payments</h2>
                    <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-5">
                        @php
                            $painPoints = [
                                ['Manual Billing', 'Staff waste hours generating invoices and reconnecting customers manually.'],
                                ['Late Payments', 'Collections slip because reminders and follow-up are inconsistent.'],
                                ['Network Disconnections', 'Manual suspension and reconnection creates support chaos.'],
                                ['Poor Visibility', 'No real-time insight into revenue, collections, and subscriber activity.'],
                                ['Scattered Systems', 'Excel, WhatsApp, router scripts, and separate tools slow daily operations.'],
                            ];
                        @endphp
                        @foreach ($painPoints as [$title, $desc])
                            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <h3 class="text-base font-bold text-slate-900">{{ $title }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $desc }}</p>
                            </article>
                        @endforeach
                    </div>
                    <p class="mt-8 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm font-medium text-blue-800">
                        ISP Billing automates your ISP operations so you can focus on growth instead of repetitive operational work.
                    </p>
                </div>
            </section>

            <section id="features" class="bg-white px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Operational Workflows That Run Your ISP Daily</h2>
                    <p class="mt-3 max-w-3xl text-slate-600">Each module is built around how real ISPs operate day to day.</p>

                    <div class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-base font-bold text-slate-900">Billing Automation</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>- Recurring billing and automated invoicing</li>
                                <li>- Due-date tracking and reminder workflows</li>
                                <li>- Automatic suspension and reconnection logic</li>
                            </ul>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-base font-bold text-slate-900">MikroTik Integration</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>- Hotspot and PPPoE support</li>
                                <li>- Auto disconnect/reconnect from payment state</li>
                                <li>- Bandwidth profile assignment and router sync</li>
                            </ul>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-base font-bold text-slate-900">M-Pesa Integration</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>- STK Push workflows</li>
                                <li>- Paybill/Till payment tracking</li>
                                <li>- Real-time confirmation and activation</li>
                            </ul>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-base font-bold text-slate-900">Customer Management</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>- Subscription lifecycle management</li>
                                <li>- KYC, contracts, and service records</li>
                                <li>- Usage and payment history visibility</li>
                            </ul>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-base font-bold text-slate-900">ISP Analytics</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>- Revenue trend and collection tracking</li>
                                <li>- Overdue accounts and churn indicators</li>
                                <li>- Active sessions and package performance</li>
                            </ul>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-base font-bold text-slate-900">Notifications</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>- SMS reminders and overdue alerts</li>
                                <li>- Payment confirmation messaging</li>
                                <li>- Planned outage and network notices</li>
                            </ul>
                        </article>
                    </div>
                </div>
            </section>

            <section id="operations" class="px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">One Live View Across Billing, Network, and Collections</h2>
                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            'Active Subscribers: 1,094',
                            'Online Sessions: 742',
                            'Suspended Users: 63',
                            'Overdue Accounts: 83',
                            'Revenue Today: KES 94,300',
                            'Network Alerts: 4',
                            'Expiring Subscriptions: 37',
                        ] as $metric)
                            <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-800 shadow-sm">{{ $metric }}</div>
                        @endforeach
                    </div>
                    <p class="mt-4 text-xs text-slate-500">Metrics shown as mock dashboard preview data.</p>
                </div>
            </section>

            <section class="bg-white px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Automate The Work That Slows ISP Growth</h2>
                    <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                            <h3 class="text-base font-bold text-blue-900">Payment-to-Access Automation</h3>
                            <p class="mt-3 text-sm text-blue-800">Customer Pays -> Payment Confirmed -> Subscription Activated -> MikroTik Access Restored -> SMS Sent</p>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                            <h3 class="text-base font-bold text-amber-900">Overdue-to-Suspension Automation</h3>
                            <p class="mt-3 text-sm text-amber-800">Invoice Overdue -> Reminder Sent -> Grace Period Ends -> Auto Suspension -> Reconnection After Payment</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Built For Every Type of Growing ISP</h2>
                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach (['Wireless ISPs', 'Fiber ISPs', 'Estate WiFi Providers', 'Hotspot Businesses', 'Rural ISPs', 'Campus Networks', 'SACCO ISPs'] as $segment)
                            <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-800 shadow-sm">{{ $segment }}</div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="bg-white px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Why ISPs Choose ISP Billing</h2>
                    <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['African Payments', 'M-Pesa-native workflows for local collection patterns.'],
                            ['MikroTik First', 'Built around RouterOS hotspot and PPPoE realities.'],
                            ['Automation Focused', 'Reduce repetitive operational work and manual follow-up.'],
                            ['Multi-Tenant SaaS', 'Manage multiple ISP entities from one platform.'],
                            ['Scalable', 'From small hotspot operations to growing regional ISPs.'],
                        ] as [$title, $desc])
                            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <h3 class="text-base font-bold text-slate-900">{{ $title }}</h3>
                                <p class="mt-2 text-sm text-slate-600">{{ $desc }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">A Full ISP Operating Stack</h2>
                    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ([
                            'Billing', 'Customers', 'Subscriptions', 'Routers',
                            'Hotspots', 'Payments', 'Notifications', 'Reports',
                            'CRM', 'Field Operations (Coming Soon)', 'Accounting (Coming Soon)', 'Inventory (Coming Soon)',
                            'Technician Management (Coming Soon)'
                        ] as $module)
                            <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-800 shadow-sm">{{ $module }}</div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="bg-white px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">See The Platform In Action</h2>
                    <p class="mt-3 text-slate-600">Replace placeholders below with real screenshots as your visual assets are finalized.</p>
                    <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            'Dashboard Overview',
                            'Customer Management',
                            'Invoices & Billing',
                            'Hotspot Packages',
                            'Payment Collection',
                            'Reports & Router Monitoring'
                        ] as $screen)
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6">
                                <div class="h-32 rounded-lg bg-slate-200"></div>
                                <p class="mt-3 text-sm font-semibold text-slate-700">{{ $screen }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">What Early ISP Teams Say</h2>
                    <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm text-slate-700">"We reduced manual reconnection calls after automating payment-to-access flow."</p>
                            <p class="mt-3 text-xs font-semibold text-slate-500">Beta pilot - Local wireless ISP ops team</p>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm text-slate-700">"Collections visibility improved once overdue tracking and reminders were centralized."</p>
                            <p class="mt-3 text-xs font-semibold text-slate-500">Internal pilot statement - Finance/Admin workflow</p>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm text-slate-700">"MikroTik automation removed repetitive operator tasks and speeded up service restoration."</p>
                            <p class="mt-3 text-xs font-semibold text-slate-500">Beta pilot - Hotspot and PPPoE deployment</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="how-it-works" class="px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">How it works</h2>
                    <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                        @php
                            $steps = [
                                'Register ISP account',
                                'Add routers and service packages',
                                'Add customers/subscriptions',
                                'Collect payments and automate access',
                            ];
                        @endphp
                        @foreach ($steps as $index => $step)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">{{ $index + 1 }}</span>
                                <p class="mt-3 text-sm font-semibold text-slate-900">{{ $step }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="integrations" class="bg-white px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Integrations</h2>
                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach (['MikroTik', 'M-Pesa', 'SMS', 'Email', 'WhatsApp (Placeholder)', 'Payment Gateway (Placeholder)'] as $integration)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-800">{{ $integration }}</div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="pricing" class="px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                        @if($landingIsp)
                            Packages from {{ $landingIsp->name }}
                        @else
                            Platform pricing (editable)
                        @endif
                    </h2>
                    <p class="mt-3 text-slate-600">
                        @if($landingIsp && $landingPlans->isNotEmpty())
                            Active hotspot and access plans configured by your provider.
                        @elseif($landingIsp)
                            No public plans are published yet. Please contact {{ $landingIsp->name }} for availability.
                        @else
                            Use these placeholders now and adjust plan limits and pricing later.
                        @endif
                    </p>
                    <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @if($landingIsp && $landingPlans->isNotEmpty())
                            @foreach ($landingPlans as $plan)
                                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                    <h3 class="text-lg font-bold text-slate-900">{{ $plan->name }}</h3>
                                    @if($plan->description)
                                        <p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($plan->description, 120) }}</p>
                                    @endif
                                    <p class="mt-3 text-2xl font-extrabold text-slate-900">
                                        {{ $landingIsp->currency }} {{ number_format((float) $plan->price, 2) }}
                                        <span class="text-sm font-normal text-slate-500">/ {{ $plan->duration_days }} days</span>
                                    </p>
                                    <ul class="mt-4 space-y-1 text-sm text-slate-600">
                                        @if($plan->access_type)
                                            <li>Access: {{ strtoupper($plan->access_type) }}</li>
                                        @endif
                                        @if($plan->download_speed_kbps)
                                            <li>Down: {{ $plan->formatted_speed ?? $plan->download_speed_kbps.' Kbps' }}</li>
                                        @endif
                                        @if($plan->billing_type)
                                            <li>Billing: {{ $plan->billing_type }}</li>
                                        @endif
                                    </ul>
                                    @guest
                                        @if($landingIsp)
                                            <a href="{{ route('customer.register') }}" class="mt-5 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Join now</a>
                                        @endif
                                    @endguest
                                </div>
                            @endforeach
                        @else
                            @foreach (['Starter', 'Growth', 'Enterprise'] as $plan)
                                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                    <h3 class="text-lg font-bold text-slate-900">{{ $plan }}</h3>
                                    <p class="mt-2 text-sm text-slate-500">Price: <span class="font-semibold">[Editable]</span></p>
                                    <ul class="mt-4 space-y-2 text-sm text-slate-600">
                                        <li>- Plan limits configurable</li>
                                        <li>- Billing cycle configurable</li>
                                        <li>- Feature scope configurable</li>
                                    </ul>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </section>

            <section class="bg-white px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">ROI and Operational Impact</h2>
                    <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-base font-bold text-slate-900">Manual Operations vs ISP Billing</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>- Manual: Disconnected billing, messaging, and router actions</li>
                                <li>- ISP Billing: One workflow from invoice to access control</li>
                                <li>- Manual: Delayed payment follow-up and service restoration</li>
                                <li>- ISP Billing: Trigger-based automation and real-time visibility</li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-base font-bold text-slate-900">Security and Control</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>- Role-based access and permissions</li>
                                <li>- Activity and router action logs</li>
                                <li>- Secure payment workflows</li>
                                <li>- Backup strategy configurable per deployment</li>
                            </ul>
                            <p class="mt-4 text-xs text-slate-500">Automation impact varies by operations size and current process maturity.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-4 py-16 sm:px-6 lg:px-8" id="faq">
                <div class="mx-auto max-w-7xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Questions Before You Launch?</h2>
                    <div class="mt-8 space-y-4">
                        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm font-semibold text-slate-900">Does it support MikroTik?</p><p class="mt-2 text-sm text-slate-600">Yes. Built with MikroTik hotspot and PPPoE workflows in mind.</p></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm font-semibold text-slate-900">Does it support M-Pesa?</p><p class="mt-2 text-sm text-slate-600">Yes. Supports M-Pesa collection flows and payment confirmation handling.</p></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm font-semibold text-slate-900">Can I automate suspensions?</p><p class="mt-2 text-sm text-slate-600">Yes. Suspension and reconnection can follow billing and payment states automatically.</p></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm font-semibold text-slate-900">Can I manage hotspots and PPPoE?</p><p class="mt-2 text-sm text-slate-600">Yes. Both use cases are supported in the operations workflow.</p></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm font-semibold text-slate-900">Is it cloud based?</p><p class="mt-2 text-sm text-slate-600">Yes, cloud deployment is supported.</p></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm font-semibold text-slate-900">Can I host it myself?</p><p class="mt-2 text-sm text-slate-600">Self-hosting can be provided as an optional deployment model.</p></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm font-semibold text-slate-900">Does it support multiple branches?</p><p class="mt-2 text-sm text-slate-600">Yes. Multi-tenant and multi-branch operations can be managed centrally.</p></div>
                    </div>
                </div>
            </section>

            <section class="px-4 pb-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl rounded-3xl bg-gradient-to-r from-blue-600 to-violet-600 px-6 py-12 text-white sm:px-10">
                    <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Ready To Run Your ISP With Automation And Control?</h2>
                    <p class="mt-3 max-w-3xl text-blue-100">Stop managing subscribers manually. Start running billing, payments, and network access from one operating system.</p>
                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                        @guest
                            @if($landingIsp)
                                <a href="{{ route('customer.register') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-bold text-blue-700 hover:bg-blue-50">Join now</a>
                                <a href="{{ route('customer.login') }}" class="inline-flex items-center justify-center rounded-xl border border-white/60 px-6 py-3 text-sm font-bold text-white hover:bg-white/10">Customer log in</a>
                            @else
                                @if (Route::has('register.isp'))
                                    <a href="{{ route('register.isp') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-bold text-blue-700 hover:bg-blue-50">Register your ISP</a>
                                @elseif (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-bold text-blue-700 hover:bg-blue-50">Start free trial</a>
                                @endif
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-white/60 px-6 py-3 text-sm font-bold text-white hover:bg-white/10">Operator log in</a>
                            @endif
                        @endguest
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl text-sm text-slate-600">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-5">
                    <div>
                        <p class="font-semibold text-slate-800">{{ $landingIsp->name ?? config('app.name', 'PrimeBilling') }}</p>
                        <p class="mt-2 text-xs text-slate-500">
                            @if($landingIsp)
                                Customer portal powered by {{ config('app.name', 'PrimeBilling') }}.
                            @else
                                The operating system for modern ISP operations.
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800">Product</p>
                        <div class="mt-2 flex flex-col gap-2">
                            <a href="#features" class="hover:text-blue-700">Features</a>
                            <a href="#pricing" class="hover:text-blue-700">Pricing</a>
                            <a href="#operations" class="hover:text-blue-700">Operations</a>
                        </div>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800">Integrations</p>
                        <div class="mt-2 flex flex-col gap-2">
                            <a href="#integrations" class="hover:text-blue-700">MikroTik</a>
                            <a href="#integrations" class="hover:text-blue-700">M-Pesa</a>
                            <a href="#integrations" class="hover:text-blue-700">SMS & Email</a>
                        </div>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800">Resources</p>
                        <div class="mt-2 flex flex-col gap-2">
                            <a href="{{ route('documentation') }}" class="hover:text-blue-700">Documentation</a>
                            <a href="{{ route('terms') }}" class="hover:text-blue-700">Terms</a>
                            <a href="{{ route('privacy') }}" class="hover:text-blue-700">Privacy</a>
                        </div>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800">Support</p>
                        <div class="mt-2 flex flex-col gap-2">
                            @if($landingIsp)
                                @if($landingIsp->phone)
                                    <a href="tel:{{ preg_replace('/\s+/', '', $landingIsp->phone) }}" class="hover:text-blue-700">Call {{ $landingIsp->name }}</a>
                                @endif
                                @if($landingIsp->email)
                                    <a href="mailto:{{ $landingIsp->email }}" class="hover:text-blue-700">Email {{ $landingIsp->name }}</a>
                                @endif
                                <a href="{{ route('customer.login') }}" class="hover:text-blue-700">Customer log in</a>
                            @else
                                <a href="{{ route('login') }}" class="hover:text-blue-700">Contact sales</a>
                                <a href="{{ route('login') }}" class="hover:text-blue-700">Operator log in</a>
                                @if(config('mail.from.address'))
                                    <a href="mailto:{{ config('mail.from.address') }}" class="hover:text-blue-700">Email platform</a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <p class="mt-4">&copy; {{ date('Y') }} {{ $landingIsp->name ?? config('app.name', 'PrimeBilling') }}. All rights reserved.</p>
            </div>
        </footer>

        <script>
            const menuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            if (menuBtn && mobileMenu) {
                menuBtn.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                    const expanded = menuBtn.getAttribute('aria-expanded') === 'true';
                    menuBtn.setAttribute('aria-expanded', String(!expanded));
                });
            }
        </script>
    </body>
</html>
