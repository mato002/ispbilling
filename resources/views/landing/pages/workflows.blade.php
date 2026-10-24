<section class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl space-y-4">
        @foreach ([
            ['01', 'Set up your network', 'Add routers, confirm API access, sync available services and profiles, then group devices by site or operating area.'],
            ['02', 'Publish a service plan', 'Set the price, speed, billing period, and connection method. Use the plan when creating a customer subscription.'],
            ['03', 'Onboard a subscriber', 'Create a customer account, select a service plan, set connection credentials, and attach the customer to the correct router.'],
            ['04', 'Collect and reconcile payment', 'Issue an invoice or request an M-Pesa payment. Confirmed payments update the account and subscription balance.'],
            ['05', 'Provision network access', 'The platform applies the subscriber’s service settings to the router and enables or renews access according to the subscription state.'],
            ['06', 'Support and monitor service', 'Review active sessions, router health, usage, outages, support requests, and collection reports from the operations workspace.'],
        ] as [$step, $title, $description])
            <article class="pb-card flex gap-4 p-5 sm:gap-6 sm:p-7">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-sm font-extrabold text-blue-700">{{ $step }}</span>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $title }}</h2>
                    <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $description }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>
