<x-admin-layout>
    @php
        $tenant = $user->isp;
        $hasTenant360 = $tenant && $tenantOverview;
        $stats = $hasTenant360 ? $tenantOverview['stats'] : null;
        $currency = $tenant?->currency ?? 'KES';
        $fmt = fn ($n) => $currency.' '.number_format((float) $n, 2);
        $saasStatus = $tenant?->saas_subscription_status ?? 'none';
        $saasBadge = match ($saasStatus) {
            'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'trialing' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
            'past_due' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100',
            'suspended' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    @if ($hasTenant360)
                        Tenant 360
                        <a href="{{ route('admin.isps.show', $tenant) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ $tenant->name }}</a>
                    @else
                        {{ __('Operator profile') }}
                    @endif
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Operator:
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $user->name }}</span>
                    <span class="text-gray-400">·</span>
                    <span class="font-mono text-xs">{{ $user->email }}</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-sm font-medium">
                    Back to List
                </a>
                @if ($hasTenant360)
                    <a href="{{ route('admin.isps.show', $tenant) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                        Open tenant dashboard
                    </a>
                @endif
                <a href="{{ route('admin.users.edit', $user->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold shadow-sm">
                    Edit User
                </a>
            </div>
        </div>
    </x-slot>

    @if ($hasTenant360 && ! empty($tenantOverview['alerts']))
        <div class="mb-6 space-y-2">
            @foreach ($tenantOverview['alerts'] as $alert)
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if ($hasTenant360)
                {{-- Tenant identity --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                @if ($logoUrl = $tenant->logoPublicUrl())
                                    <img src="{{ $logoUrl }}" alt="" class="h-12 w-12 rounded-xl border border-gray-200 object-cover dark:border-gray-600">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-sm font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                                        {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                        <a href="{{ route('admin.isps.show', $tenant) }}" class="text-indigo-600 hover:underline font-medium dark:text-indigo-400">{{ $tenant->name }}</a>
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $tenant->company_name }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if ($tenant->is_active)
                                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-800 dark:text-green-100">Active tenant</span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-800 dark:text-red-100">Inactive</span>
                                        @endif
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize {{ $saasBadge }}">{{ str_replace('_', ' ', $saasStatus) }}</span>
                                        @if ($tenant->platform_access_locked)
                                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">Dashboard locked</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Tenant email</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tenant->email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Phone</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tenant->phone }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Currency</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tenant->currency }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Tenant operational KPIs --}}
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    @foreach ([
                        ['label' => 'Customers', 'value' => $tenant->customers_count, 'sub' => $stats['active_customers'].' active · '.$stats['suspended_customers'].' suspended'],
                        ['label' => 'Routers', 'value' => $tenant->routers_count, 'sub' => $stats['reachable_routers'].'/'.$stats['active_routers'].' online'],
                        ['label' => 'Live sessions', 'value' => $stats['active_sessions'], 'sub' => 'hotspot + PPPoE now'],
                        ['label' => 'Revenue (MTD)', 'value' => $fmt($stats['current_month_revenue']), 'sub' => ($stats['revenue_change'] >= 0 ? '+' : '').number_format($stats['revenue_change'], 1).'% vs last month', 'raw' => true],
                        ['label' => 'Billing risk', 'value' => $stats['pending_invoices'] + $stats['overdue_invoices'], 'sub' => $stats['pending_invoices'].' pending · '.$stats['overdue_invoices'].' overdue'],
                        ['label' => 'Support', 'value' => $stats['open_tickets'], 'sub' => 'open platform tickets'],
                    ] as $kpi)
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ $kpi['label'] }}</p>
                            <p class="mt-2 text-xl font-bold text-slate-900 dark:text-white {{ ($kpi['raw'] ?? false) ? 'text-base' : '' }}">{{ $kpi['value'] }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $kpi['sub'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Operator summary --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Operator account</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Identity, access role, and verification for this workspace user.</p>
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</h4>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $user->email }}</p>
                        </div>
                        @if ($user->is_active)
                            <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Active</span>
                        @else
                            <span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">Inactive</span>
                        @endif
                    </div>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</dt>
                            <dd class="mt-1">
                                @switch($user->role)
                                    @case('super_admin')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">Super Admin</span>
                                        @break
                                    @case('isp_admin')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">ISP Admin</span>
                                        @break
                                    @case('isp_staff')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Staff</span>
                                        @break
                                @endswitch
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenant</dt>
                            <dd class="mt-1 text-sm">
                                @if ($tenant)
                                    <a href="{{ route('admin.isps.show', $tenant) }}" class="text-indigo-600 hover:underline font-medium dark:text-indigo-400">{{ $tenant->name }}</a>
                                @else
                                    N/A
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->phone ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email verified</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if ($user->email_verified_at)
                                    <span class="text-green-600 dark:text-green-400">Yes ({{ $user->email_verified_at->format('M d, Y') }})</span>
                                @else
                                    <span class="text-red-600 dark:text-red-400">No</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Joined</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last updated</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->updated_at->format('M d, Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Operator + tenant signals --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Operator activity</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($operatorDashboard['activity_volume_30d']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Actions by this user (30d)</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Active subscribers</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                        @if ($operatorDashboard['active_subscribers'] !== null)
                            {{ number_format($operatorDashboard['active_subscribers']) }}
                        @else
                            —
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $operatorDashboard['network_scope_label'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Security signals</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($operatorDashboard['security_events_30d']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ number_format($operatorDashboard['failed_logins_30d']) }} failed auth ·
                        {{ number_format($operatorDashboard['router_api_failures_30d']) }} router API (30d)
                    </p>
                </div>
            </div>

            @if ($hasTenant360)
                {{-- Tenant activity --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tenant activity feed</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Latest workspace audit events · {{ number_format($operatorDashboard['tenant_activity_30d'] ?? 0) }} in the last 30 days
                                </p>
                            </div>
                            <a href="{{ route('admin.audit.activity', ['isp_id' => $tenant->id]) }}" class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                Tenant audit →
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        @include('admin.users.partials.activity-timeline', [
                            'logs' => $tenantOverview['recentActivity']->take(5),
                            'emptyMessage' => 'No tenant audit events recorded yet.',
                        ])
                    </div>
                </div>

                {{-- Network fleet snapshot --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Network fleet</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Router reachability for this tenant.</p>
                    </div>
                    <div class="overflow-x-auto p-6">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <th class="pb-2 pr-4">Router</th>
                                    <th class="pb-2 pr-4">IP</th>
                                    <th class="pb-2 pr-4">Status</th>
                                    <th class="pb-2">Sessions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($tenantOverview['routers']->take(6) as $router)
                                    <tr>
                                        <td class="py-2.5 pr-4 font-medium text-gray-900 dark:text-gray-100">{{ $router->name }}</td>
                                        <td class="py-2.5 pr-4 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $router->ip_address }}</td>
                                        <td class="py-2.5 pr-4">
                                            @php
                                                $status = $router->reachability_status ?? 'unknown';
                                                $statusClass = match ($status) {
                                                    'online' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                                                    'offline' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
                                                    'degraded', 'rebooting' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100',
                                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                };
                                            @endphp
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase {{ $statusClass }}">{{ $status }}</span>
                                        </td>
                                        <td class="py-2.5 text-xs text-gray-600 dark:text-gray-300">
                                            {{ (int) $router->last_active_hotspot_users }} HS · {{ (int) $router->last_active_pppoe_users }} PPPoE
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400">No routers provisioned for this tenant.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Recent payments --}}
                @if ($tenantOverview['recentPayments']->isNotEmpty())
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent collections</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Latest completed subscriber payments.</p>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($tenantOverview['recentPayments']->take(5) as $payment)
                                <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-3 text-sm">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $payment->customer?->full_name ?? 'Customer' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $payment->payment_method }} · {{ $payment->created_at?->format('M j, g:i A') }}</p>
                                    </div>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $fmt($payment->amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            {{-- Operator-specific activity --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Operator activity feed</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Actions attributed to <span class="font-mono text-xs">{{ $user->email }}</span>
                            </p>
                        </div>
                        <a href="{{ route('admin.audit.activity', ['search' => $user->email]) }}" class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                            Operator audit →
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    @include('admin.users.partials.activity-timeline', [
                        'logs' => $operatorDashboard['recent_activity'],
                        'emptyMessage' => 'No audit events recorded for this operator yet.',
                    ])
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick actions</h3>
                    <div class="space-y-2">
                        @if ($user->is_active)
                            <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="inactive">
                                <button type="submit" class="w-full px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 font-medium text-sm">
                                    Deactivate
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="w-full px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 dark:bg-green-900/20 dark:text-green-400 font-medium text-sm">
                                    Activate
                                </button>
                            </form>
                        @endif
                        @if ($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="confirmFormSubmit(event, {title: 'Delete User', text: 'Are you sure you want to delete {{ $user->name }}? This action cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, delete', confirmButtonColor: '#ef4444'}); return false;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400 font-medium text-sm">
                                    Delete User
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            @if ($hasTenant360)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Workspace team</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $tenant->users_count }} operators on this tenant</p>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($tenantOverview['teamUsers'] as $member)
                            <li class="flex items-center justify-between gap-2 px-6 py-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                        @if ($member->id === $user->id)
                                            {{ $member->name }}
                                            <span class="text-indigo-600 dark:text-indigo-400">(viewing)</span>
                                        @else
                                            <a href="{{ route('admin.users.show', $member) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ $member->name }}</a>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $member->email }}</p>
                                </div>
                                <span class="shrink-0 text-[10px] font-semibold uppercase text-gray-400">{{ str_replace('isp_', '', $member->role) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-sm text-gray-600 dark:text-gray-300">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Tenant snapshot</h3>
                        <ul class="space-y-2">
                            <li><span class="text-gray-500 dark:text-gray-400">Lifetime revenue:</span> <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $fmt($stats['total_revenue']) }}</span></li>
                            <li><span class="text-gray-500 dark:text-gray-400">Platform commission:</span> <span class="font-semibold">{{ $fmt($stats['platform_commission']) }}</span></li>
                            <li><span class="text-gray-500 dark:text-gray-400">Active subscriptions:</span> <span class="font-semibold">{{ $stats['active_subscriptions'] }}</span></li>
                            <li><span class="text-gray-500 dark:text-gray-400">Expiring (7d):</span> <span class="font-semibold">{{ $stats['expiring_soon'] }}</span></li>
                        </ul>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Role permissions</h3>
                    <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        @switch($user->role)
                            @case('super_admin')
                                <p class="font-medium">Super Admin has full access to:</p>
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>All ISP management</li>
                                    <li>User management</li>
                                    <li>System settings</li>
                                    <li>Reports and analytics</li>
                                </ul>
                                @break
                            @case('isp_admin')
                                <p class="font-medium">ISP Admin has access to:</p>
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>Own ISP management</li>
                                    <li>Customer management</li>
                                    <li>Plan and router management</li>
                                    <li>Billing and payments</li>
                                </ul>
                                @break
                            @case('isp_staff')
                                <p class="font-medium">Staff has access to:</p>
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>Customer management (limited)</li>
                                    <li>View reports</li>
                                    <li>Process payments</li>
                                    <li>Manage tickets</li>
                                </ul>
                                @break
                        @endswitch
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
