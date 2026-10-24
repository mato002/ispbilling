<section class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto grid max-w-7xl gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            ['Billing and subscriptions', 'Create plans, issue invoices, track due dates, apply grace periods, and keep each customer’s subscription state in sync with payments.'],
            ['M-Pesa payments', 'Accept STK Push, Paybill, and Till payments. Reconcile confirmations against customer accounts and keep an auditable payment history.'],
            ['MikroTik network control', 'Connect RouterOS devices, manage PPPoE and hotspot users, sync bandwidth profiles, and review router reachability.'],
            ['Hotspot and vouchers', 'Publish time or data-based packages, generate vouchers in batches, and let customers pay and connect through the portal.'],
            ['Customer support', 'Keep service requests, outage work, customer communication, and account context together for the operations team.'],
            ['Field operations and inventory', 'Track CPE and network equipment, assign devices to technicians, and record installations against customers.'],
            ['Reports and data usage', 'Review collections, active subscriptions, sessions, and usage trends to understand service and revenue performance.'],
            ['Customer self-service', 'Give subscribers a place to review invoices, renew plans, view usage, and contact support.'],
            ['Multi-tenant workspace', 'Run ISP organizations with separate users, customers, network devices, branding, and operating data.'],
        ] as [$title, $description])
            <article class="pb-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-slate-900">{{ $title }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $description }}</p>
            </article>
        @endforeach
    </div>
</section>
