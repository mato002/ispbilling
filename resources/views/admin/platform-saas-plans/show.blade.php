<x-admin-layout>
    @php
        $features = $plan->features ?? [];
        $maxSignupBar = max(1, (int) $monthlySignups->max('count'));
        $statusLabels = [
            'active' => 'Active',
            'trialing' => 'Trialing',
            'past_due' => 'Past due',
            'suspended' => 'Suspended',
            'cancelled' => 'Cancelled',
            'none' => 'None',
        ];
        $statusBadge = fn (string $status) => match ($status) {
            'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'trialing' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
            'past_due' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100',
            'suspended' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-lg font-bold text-white shadow-lg shadow-indigo-500/25">
                    {{ strtoupper(substr($plan->name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Plan 360 · {{ $plan->name }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-mono text-xs">{{ $plan->slug }}</span>
                        <span class="text-gray-400">·</span>
                        KES {{ number_format($plan->monthly_price, 0) }}/month
                        <span class="text-gray-400">·</span>
                        {{ $plan->trial_days }}d trial
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @if ($plan->is_active)
                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-800 dark:text-green-100">Active tier</span>
                        @else
                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-800 dark:text-red-100">Inactive tier</span>
                        @endif
                        <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200">
                            {{ $stats['tenant_count'] }} tenant{{ $stats['tenant_count'] === 1 ? '' : 's' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.platform-saas-plans.edit', $plan) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">Edit plan</a>
                <a href="{{ route('admin.platform-saas-plans.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-sm font-medium">Back to plans</a>
            </div>
        </div>
    </x-slot>

    @if (! empty($alerts))
        <div class="mb-6 space-y-2">
            @foreach ($alerts as $alert)
                @php
                    $alertClass = match ($alert['type']) {
                        'danger' => 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-100',
                        'warning' => 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100',
                        default => 'border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-100',
                    };
                @endphp
                <div class="rounded-lg border px-4 py-3 text-sm {{ $alertClass }}">{{ $alert['message'] }}</div>
            @endforeach
        </div>
    @endif

    {{-- KPI strip --}}
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-8">
        @foreach ([
            ['label' => 'Tenants', 'value' => $stats['tenant_count'], 'sub' => $stats['active_tenant_count'].' active'],
            ['label' => 'Trialing', 'value' => $stats['trialing_count'], 'sub' => 'on trial'],
            ['label' => 'Billing', 'value' => $stats['billing_tenant_count'], 'sub' => 'active + trialing + past due'],
            ['label' => 'Subscribers', 'value' => number_format($stats['total_customers']), 'sub' => number_format($stats['active_customers']).' active'],
            ['label' => 'Routers', 'value' => number_format($stats['total_routers']), 'sub' => 'fleet total'],
            ['label' => 'MRR est.', 'value' => 'KES '.number_format($stats['mrr_estimate'], 0), 'sub' => 'platform billing'],
            ['label' => 'Collected', 'value' => 'KES '.number_format($stats['paid_revenue'], 0), 'sub' => 'paid invoices'],
            ['label' => 'Pending', 'value' => $stats['pending_invoice_count'], 'sub' => 'KES '.number_format($stats['pending_invoice_amount'], 0)],
        ] as $kpi)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $kpi['value'] }}</p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $kpi['sub'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Tenant roster --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Tenants on this plan</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ISPs subscribed to {{ $plan->name }} — click a row to open tenant 360.</p>
                </div>
                @if ($tenants->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        No tenants on this plan yet.
                    </div>
                @else
                    <x-listing-table>
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>SaaS status</th>
                                <th>Customers</th>
                                <th>Routers</th>
                                <th>Team</th>
                                <th>Joined</th>
                                <x-table-actions.header />
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tenants as $tenant)
                                <x-listing-table.row :href="route('admin.isps.show', $tenant)">
                                    <td>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $tenant->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $tenant->company_name }}</div>
                                    </td>
                                    <td>
                                        @php $saasStatus = $tenant->saas_subscription_status ?? 'none'; @endphp
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize {{ $statusBadge($saasStatus) }}">
                                            {{ str_replace('_', ' ', $saasStatus) }}
                                        </span>
                                        @if ($tenant->needsSaasOnboarding())
                                            <span class="ml-1 text-[10px] font-medium text-indigo-600 dark:text-indigo-400">Onboarding</span>
                                        @endif
                                    </td>
                                    <td class="tabular-nums">
                                        {{ number_format($tenant->customers_count) }}
                                        @if ($plan->max_customers)
                                            <span class="text-xs text-gray-400">/ {{ number_format($plan->max_customers) }}</span>
                                        @endif
                                    </td>
                                    <td class="tabular-nums">{{ number_format($tenant->routers_count) }}</td>
                                    <td class="tabular-nums">{{ number_format($tenant->users_count) }}</td>
                                    <td class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $tenant->created_at->format('M j, Y') }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        <x-table-actions>
                                            <x-table-actions.link :href="route('admin.isps.show', $tenant)" label="View tenant" />
                                            <x-table-actions.link :href="route('admin.isps.edit', $tenant)" label="Edit" />
                                        </x-table-actions>
                                    </td>
                                </x-listing-table.row>
                            @endforeach
                        </tbody>
                    </x-listing-table>
                @endif
            </div>

            {{-- Recent platform invoices --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Recent platform invoices</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">SaaS billing invoices issued for tenants on this tier.</p>
                </div>
                @if ($recentInvoices->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No invoices yet for this plan.</div>
                @else
                    <x-listing-table>
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Tenant</th>
                                <th>Period</th>
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentInvoices as $invoice)
                                <tr>
                                    <td class="font-mono text-xs">{{ $invoice->invoice_number }}</td>
                                    <td>
                                        @if ($invoice->isp)
                                            <a href="{{ route('admin.isps.show', $invoice->isp) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ $invoice->isp->name }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ $invoice->period_start?->format('M j') }} – {{ $invoice->period_end?->format('M j, Y') }}
                                    </td>
                                    <td class="text-right font-mono font-semibold">KES {{ number_format($invoice->totalAmount(), 2) }}</td>
                                    <td class="capitalize">{{ $invoice->status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-listing-table>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            {{-- Plan configuration --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Tier configuration</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Monthly price</dt>
                        <dd class="font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($plan->monthly_price, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Setup fee</dt>
                        <dd class="font-semibold text-gray-900 dark:text-gray-100">KES {{ number_format($plan->setup_fee, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Trial period</dt>
                        <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $plan->trial_days }} days</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Customer cap</dt>
                        <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $plan->max_customers ? number_format($plan->max_customers) : 'Unlimited' }}</dd>
                    </div>
                    @if (! empty($features['max_routers']))
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Router cap</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($features['max_routers']) }}</dd>
                        </div>
                    @endif
                    @if (! empty($features['max_tenants']))
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Tenant cap</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($features['max_tenants']) }}</dd>
                        </div>
                    @endif
                    @if (! empty($features['speed_limit_mbps']))
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Speed limit</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $features['speed_limit_mbps'] }} Mbps</dd>
                        </div>
                    @endif
                </dl>
                @if ($plan->description)
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300 border-t border-gray-100 pt-4 dark:border-gray-700">{{ $plan->description }}</p>
                @endif
                <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">
                    @if (! empty($features['advanced_analytics']))
                        <span class="rounded-md bg-violet-100 px-2 py-1 text-xs font-semibold text-violet-800 dark:bg-violet-900/40 dark:text-violet-200">Advanced analytics</span>
                    @endif
                    @if (! empty($features['sms_automation']))
                        <span class="rounded-md bg-cyan-100 px-2 py-1 text-xs font-semibold text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200">SMS automation</span>
                    @endif
                </div>
            </div>

            {{-- Subscription status mix --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">SaaS status mix</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">How tenants on this plan are billed today.</p>
                <ul class="mt-4 space-y-2">
                    @forelse ($statusBreakdown as $status => $count)
                        <li class="flex items-center justify-between text-sm">
                            <span class="capitalize text-gray-600 dark:text-gray-300">{{ $statusLabels[$status] ?? str_replace('_', ' ', $status) }}</span>
                            <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $count }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-gray-500 dark:text-gray-400">No tenants assigned.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Signup trend --}}
            @if ($monthlySignups->isNotEmpty())
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">New tenants (6 mo)</h3>
                    <div class="mt-4 space-y-2">
                        @foreach ($monthlySignups as $row)
                            <div class="flex items-center gap-3 text-sm">
                                <span class="w-16 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->ym }}</span>
                                <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-2 rounded-full bg-indigo-500" style="width: {{ max(4, ($row->count / $maxSignupBar) * 100) }}%"></div>
                                </div>
                                <span class="w-6 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $row->count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
