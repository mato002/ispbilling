<section class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl space-y-3">
        @foreach ([
            ['Which MikroTik features are supported?', 'Manage RouterOS devices, PPPoE and hotspot subscribers, bandwidth profiles, vouchers, sessions, router health, and provisioning actions. Available operations depend on router configuration and connectivity.'],
            ['Can payments activate or renew service?', 'Confirmed payments can be reconciled with customer invoices and subscriptions. Provisioning follows the subscription state and the network settings configured for that customer.'],
            ['Which M-Pesa payment methods are available?', 'The platform supports configured STK Push, Paybill, and Till payment workflows. Your Daraja credentials and callback configuration must be set up for live transactions.'],
            ['Can I manage both PPPoE and hotspot customers?', 'Yes. The platform supports both connection types, with plans, credentials, service profiles, and lifecycle actions managed from the ISP workspace.'],
            ['Can subscribers manage their own accounts?', 'The customer portal can provide account access to invoices, renewals, usage, receipts, and support, depending on the features enabled by the ISP.'],
            ['Does the platform include field inventory?', 'Inventory tools track equipment, assignments, and technician issues so deployed CPE and other gear can be associated with customer installations.'],
            ['How do we get started?', 'Start a trial, configure the ISP workspace, add and test a router, create service plans, then onboard a small group of subscribers before expanding provisioning.'],
            ['Where can I find technical guidance?', 'See the platform documentation or contact the team for help with setup and account questions.'],
        ] as [$question, $answer])
            <details class="pb-card group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-4 font-semibold text-slate-900 sm:p-5">
                    {{ $question }}
                    <span class="shrink-0 text-blue-700 transition group-open:rotate-45" aria-hidden="true">＋</span>
                </summary>
                <p class="border-t border-slate-100 px-4 pb-5 pt-3 text-sm leading-relaxed text-slate-600 sm:px-5">{{ $answer }}</p>
            </details>
        @endforeach
        <p class="pt-3 text-sm text-slate-600">Need technical documentation? <a href="{{ route('documentation') }}" class="font-semibold text-blue-700 underline">Read the documentation</a>.</p>
    </div>
</section>
