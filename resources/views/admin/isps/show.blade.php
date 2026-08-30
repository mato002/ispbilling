<x-admin-layout>
    @php
        $currency = $isp->currency ?? 'KES';
        $fmt = fn ($n) => $currency.' '.number_format((float) $n, 2);
        $saasStatus = $isp->saas_subscription_status ?? 'none';
        $saasBadge = match ($saasStatus) {
            'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'trialing' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
            'past_due' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100',
            'suspended' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
        $maxMonthlyBar = max(1, (float) ($monthlyRevenue->max('revenue') ?: 1));
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                @if ($logoUrl = $isp->logoPublicUrl())
                    <img src="{{ $logoUrl }}" alt="" class="h-14 w-14 rounded-xl border border-gray-200 object-cover dark:border-gray-600">
                @else
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-indigo-100 text-lg font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                        {{ strtoupper(substr($isp->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $isp->name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $isp->company_name }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @if ($isp->is_active)
                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-800 dark:text-green-100">Active tenant</span>
                        @else
                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-800 dark:text-red-100">Inactive</span>
                        @endif
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize {{ $saasBadge }}">{{ str_replace('_', ' ', $saasStatus) }}</span>
                        @if ($isp->platform_access_locked)
                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">Dashboard locked</span>
                        @endif
                        @if ($isp->needsSaasOnboarding())
                            <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200">Onboarding pending</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.isps.edit', $isp) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">Edit</a>
                <a href="{{ route('admin.isps.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-sm font-medium">Back to list</a>
            </div>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-900/30 dark:text-rose-200">{{ session('error') }}</div>
    @endif

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
            ['label' => 'Customers', 'value' => $isp->customers_count, 'sub' => $stats['active_customers'].' active'],
            ['label' => 'Subscriptions', 'value' => $stats['active_subscriptions'], 'sub' => $stats['expiring_soon'].' expiring'],
            ['label' => 'Sessions', 'value' => $stats['active_sessions'], 'sub' => 'live now'],
            ['label' => 'Routers', 'value' => $isp->routers_count, 'sub' => $stats['reachable_routers'].'/'.$stats['active_routers'].' online'],
            ['label' => 'Plans', 'value' => $isp->plans_count, 'sub' => $isp->vouchers_count.' vouchers'],
            ['label' => 'Team', 'value' => $isp->users_count, 'sub' => 'operators'],
            ['label' => 'Revenue (MTD)', 'value' => $fmt($stats['current_month_revenue']), 'sub' => ($stats['revenue_change'] >= 0 ? '+' : '').number_format($stats['revenue_change'], 1).'% vs last month', 'raw' => true],
            ['label' => 'Lifetime revenue', 'value' => $fmt($stats['total_revenue']), 'sub' => $fmt($stats['platform_commission']).' commission', 'raw' => true],
        ] as $kpi)
            <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100 {{ ($kpi['raw'] ?? false) ? 'text-base' : '' }}">{{ $kpi['value'] }}</p>
                <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">{{ $kpi['sub'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Main column --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Revenue trend --}}
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Subscriber revenue (6 months)</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Completed payments for this tenant — not platform SaaS billing.</p>
                <div class="mt-4 space-y-2">
                    @forelse ($monthlyRevenue as $row)
                        @php $w = min(100, ((float) $row->revenue / $maxMonthlyBar) * 100); @endphp
                        <div>
                            <div class="mb-1 flex justify-between text-xs text-gray-600 dark:text-gray-300">
                                <span>{{ $row->ym }}</span>
                                <span class="font-medium">{{ $fmt($row->revenue) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $w }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No completed payments in the last six months.</p>
                    @endforelse
                </div>
                <dl class="mt-4 grid grid-cols-3 gap-4 border-t border-gray-100 pt-4 text-sm dark:border-gray-700">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Pending invoices</dt>
                        <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $stats['pending_invoices'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Overdue</dt>
                        <dd class="font-semibold {{ $stats['overdue_invoices'] ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $stats['overdue_invoices'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Total payments</dt>
                        <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $isp->payments_count }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Network --}}
            <div class="rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Network & routers</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Fleet health from last telemetry poll.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-700/50 dark:text-gray-300">
                            <tr>
                                <th class="px-6 py-3">Router</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Health</th>
                                <th class="px-6 py-3">Active users</th>
                                <th class="px-6 py-3">Last seen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($routers as $router)
                                <tr>
                                    <td class="px-6 py-3">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $router->name }}</div>
                                        <div class="font-mono text-[11px] text-gray-500">{{ $router->ip_address }}</div>
                                        @if ($router->location)
                                            <div class="text-[11px] text-gray-400">{{ $router->location }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        @if (! $router->is_active)
                                            <span class="text-gray-500">Disabled</span>
                                        @elseif ($router->reachability_status === 'online')
                                            <span class="font-medium text-emerald-600 dark:text-emerald-400">Online</span>
                                        @else
                                            <span class="font-medium text-rose-600 dark:text-rose-400">{{ $router->reachability_status ?? 'Unknown' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $router->last_health_pct !== null ? $router->last_health_pct.'%' : '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-gray-700 dark:text-gray-300">
                                        {{ ($router->last_active_hotspot_users ?? 0) + ($router->last_active_pppoe_users ?? 0) }}
                                    </td>
                                    <td class="px-6 py-3 text-xs text-gray-500">
                                        {{ $router->last_seen_at?->diffForHumans() ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No routers provisioned for this ISP.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent payments --}}
            <div class="rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent subscriber payments</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-700/50 dark:text-gray-300">
                            <tr>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Amount</th>
                                <th class="px-6 py-3">Method</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($recentPayments as $payment)
                                <tr>
                                    <td class="px-6 py-3 text-gray-900 dark:text-gray-100">
                                        {{ $payment->customer?->full_name ?? '—' }}
                                        @if ($payment->customer?->username)
                                            <div class="font-mono text-[11px] text-gray-500">{{ $payment->customer->username }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 font-medium">{{ $fmt($payment->amount) }}</td>
                                    <td class="px-6 py-3 capitalize text-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', $payment->payment_method ?? '—') }}</td>
                                    <td class="px-6 py-3 capitalize">{{ $payment->status }}</td>
                                    <td class="px-6 py-3 text-xs text-gray-500">{{ ($payment->processed_at ?? $payment->created_at)?->format('M j, Y g:i A') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No payments recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent customers + activity --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">New customers</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($recentCustomers as $customer)
                            <li class="px-6 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $customer->full_name }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $customer->username }} · {{ ucfirst($customer->connection_type ?? '—') }}</p>
                                    </div>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize
                                        {{ $customer->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $customer->status }}
                                    </span>
                                </div>
                                <p class="mt-1 text-[11px] text-gray-400">{{ $customer->created_at->diffForHumans() }}</p>
                            </li>
                        @empty
                            <li class="px-6 py-6 text-sm text-gray-500">No customers yet.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Recent activity</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($recentActivity as $log)
                            <li class="px-6 py-3">
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $log->description ?: $log->action }}</p>
                                <p class="mt-0.5 text-[11px] text-gray-500">
                                    {{ $log->user?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}
                                </p>
                            </li>
                        @empty
                            <li class="px-6 py-6 text-sm text-gray-500">No activity logged for this tenant.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- SaaS subscription --}}
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Platform SaaS</h3>
                @if ($isp->saasPlan)
                    <p class="mt-2 text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ $isp->saasPlan->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $isp->saasPlan->description }}</p>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Monthly</dt><dd class="font-medium">{{ $fmt($isp->saasPlan->monthly_price) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Customer limit</dt><dd class="font-medium">{{ $isp->saasPlan->max_customers ?? 'Unlimited' }}</dd></div>
                        @if ($isp->saas_trial_ends_at)
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Trial ends</dt><dd>{{ $isp->saas_trial_ends_at->format('M j, Y') }}</dd></div>
                        @endif
                        @if ($isp->saas_next_billing_at)
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Next billing</dt><dd>{{ $isp->saas_next_billing_at->format('M j, Y') }}</dd></div>
                        @endif
                        @if ($isp->saas_grace_ends_at)
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Grace until</dt><dd class="text-amber-700 dark:text-amber-300">{{ $isp->saas_grace_ends_at->format('M j, Y') }}</dd></div>
                        @endif
                        @if ($isp->saas_onboarding_completed_at)
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Onboarded</dt><dd>{{ $isp->saas_onboarding_completed_at->format('M j, Y') }}</dd></div>
                        @endif
                    </dl>
                @else
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No SaaS plan linked.</p>
                @endif
                @if ($isp->needsSaasOnboarding())
                    <a href="{{ route('admin.isps.saas-onboarding.show', $isp) }}" class="mt-4 block w-full rounded-lg bg-indigo-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-indigo-700">
                        Complete onboarding
                    </a>
                @endif
            </div>

            {{-- Platform invoices --}}
            @if ($isp->platformSaasInvoices->isNotEmpty())
                <div class="rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Platform invoices</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($isp->platformSaasInvoices as $inv)
                            <li class="px-6 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-xs text-gray-900 dark:text-gray-100">{{ $inv->invoice_number }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize
                                        {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : ($inv->status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' : 'bg-gray-100 text-gray-600') }}">
                                        {{ $inv->status }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm font-medium">{{ $fmt($inv->totalAmount()) }}</p>
                                <p class="text-[11px] text-gray-500">Due {{ $inv->due_date?->format('M j, Y') ?? '—' }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Helpdesk --}}
            <div class="rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Platform support</h3>
                    @if ($stats['open_tickets'] > 0)
                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">{{ $stats['open_tickets'] }} open</span>
                    @endif
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($recentTickets as $ticket)
                        <li class="px-6 py-3">
                            <a href="{{ route('admin.helpdesk.show', $ticket) }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ $ticket->subject }}</a>
                            <p class="mt-0.5 text-[11px] capitalize text-gray-500">{{ $ticket->status }} · {{ $ticket->priority }} · {{ $ticket->created_at->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="px-6 py-6 text-sm text-gray-500">No platform tickets from this ISP.</li>
                    @endforelse
                </ul>
                <div class="border-t border-gray-100 px-6 py-3 dark:border-gray-700">
                    <a href="{{ route('admin.helpdesk.index', ['isp_id' => $isp->id]) }}" class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">View all tickets →</a>
                </div>
            </div>

            {{-- Team --}}
            <div class="rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Team ({{ $teamUsers->count() }})</h3>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($teamUsers as $user)
                        <li class="px-6 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400">{{ $user->name }}</a>
                                    <p class="text-[11px] text-gray-500">{{ $user->email }}</p>
                                </div>
                                <span class="text-[10px] capitalize text-gray-400">{{ str_replace('_', ' ', $user->role ?? 'user') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Tenant profile --}}
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Tenant profile</h3>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-gray-500">Email</dt><dd class="text-gray-900 dark:text-gray-100">{{ $isp->email }}</dd></div>
                    <div><dt class="text-gray-500">Phone</dt><dd class="text-gray-900 dark:text-gray-100">{{ $isp->phone }}</dd></div>
                    <div><dt class="text-gray-500">Address</dt><dd class="text-gray-900 dark:text-gray-100">{{ $isp->address ?? 'N/A' }}</dd></div>
                    <div><dt class="text-gray-500">Timezone</dt><dd class="text-gray-900 dark:text-gray-100">{{ $isp->timezone }}</dd></div>
                    <div>
                        <dt class="text-gray-500">Public portal</dt>
                        <dd class="text-gray-900 dark:text-gray-100">
                            @if ($isp->slug && ! in_array(config('app.central_domain'), ['localhost', '127.0.0.1'], true))
                                <a class="break-all font-mono text-xs text-indigo-600 hover:underline dark:text-indigo-400" target="_blank" rel="noopener"
                                   href="{{ (parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https').'://'.$isp->slug.'.'.config('app.central_domain') }}">
                                    {{ $isp->slug }}.{{ config('app.central_domain') }}
                                </a>
                            @else
                                <span class="text-gray-500 text-xs">{{ $isp->slug ? $isp->slug.' (configure DNS)' : 'No slug set' }}</span>
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-gray-500">Created</dt><dd class="text-gray-900 dark:text-gray-100">{{ $isp->created_at->format('M d, Y') }}</dd></div>
                </dl>
            </div>

            @if ($isp->paymentMethods->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">Payment gateways</h3>
                    <p class="text-xs text-gray-500 mb-3">Configured for subscriber billing — secrets are never shown.</p>
                    <ul class="space-y-2">
                        @foreach ($isp->paymentMethods as $pm)
                            <li class="flex items-center justify-between text-sm">
                                <span class="font-mono text-xs">{{ $pm->provider }}</span>
                                <span class="{{ $pm->active ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">{{ $pm->active ? 'Active' : 'Inactive' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Quick actions --}}
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.isps.edit', $isp) }}" class="block w-full rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-blue-700">Edit ISP</a>
                    @if ($isp->is_active)
                        <form method="POST" action="{{ route('admin.isps.update', $isp) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_active" value="0">
                            <button type="submit" class="w-full rounded-lg bg-yellow-100 px-4 py-2 text-sm font-medium text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400">Deactivate</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.isps.update', $isp) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_active" value="1">
                            <button type="submit" class="w-full rounded-lg bg-green-100 px-4 py-2 text-sm font-medium text-green-800 hover:bg-green-200 dark:bg-green-900/20 dark:text-green-400">Activate</button>
                        </form>
                        <form method="POST" action="{{ route('admin.isps.force-suspend-network', $isp) }}" onsubmit="return confirm('Queue suspension sync on all routers for this ISP?');">
                            @csrf
                            <button type="submit" class="w-full rounded-lg bg-orange-100 px-4 py-2 text-sm font-medium text-orange-900 hover:bg-orange-200 dark:bg-orange-900/30 dark:text-orange-100">Force sync suspension</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.isps.destroy', $isp) }}" onsubmit="confirmFormSubmit(event, {title: 'Delete ISP', text: 'Are you sure you want to delete {{ $isp->name }}? This action cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, delete', confirmButtonColor: '#ef4444'}); return false;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full rounded-lg bg-red-100 px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400">Delete ISP</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
