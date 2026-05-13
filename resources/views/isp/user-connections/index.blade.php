@extends('layouts.admin')

@section('title', 'User Connection Management')

@section('header')
    <x-isp-page-header
        title="User connection management"
        module="Automation"
        description="Disconnect, suspend, and reconnect subscribers with MikroTik-backed actions."
    >
        <x-slot name="actions">
            <button type="button" onclick="runAutomaticManagement()" class="pb-btn pb-btn-primary">Run automation</button>
            <button type="button" onclick="refreshStatistics()" class="pb-btn">Refresh stats</button>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-dashboard.kpi-card title="Total customers" :value="number_format($stats['total_customers'])" valueId="total-customers" subtext="In scope" iconBg="from-indigo-500 to-cyan-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Connected" :value="number_format($stats['active_customers'])" valueId="connected-customers" subtext="Active accounts" iconBg="from-emerald-500 to-teal-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14 0"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Suspended" :value="number_format($stats['suspended_customers'])" valueId="suspended-customers" subtext="Needs action" iconBg="from-amber-500 to-orange-500">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
            <x-dashboard.kpi-card title="Disconnected" :value="number_format($stats['expired_customers'])" valueId="disconnected-customers" subtext="Expired / off" iconBg="from-rose-500 to-red-600">
                <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg></x-slot:icon>
            </x-dashboard.kpi-card>
        </section>

        <!-- Alert Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-red-800 mb-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Issues Requiring Attention
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-red-700">Expired Subscriptions:</span>
                        <span class="font-semibold text-red-800">{{ $stats['expired_subscriptions'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-red-700">Unpaid Invoices (7+ days):</span>
                        <span class="font-semibold text-red-800">{{ $stats['unpaid_invoices'] ?? 0 }}</span>
                    </div>
                </div>
                @if($stats['expired_subscriptions'] > 0 || ($stats['unpaid_invoices'] ?? 0) > 0)
                    <div class="mt-4">
                        <button onclick="runAutomaticManagement()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                            <i class="fas fa-robot mr-2"></i>Run Automatic Management
                        </button>
                    </div>
                @endif
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-800 mb-3">
                    <i class="fas fa-info-circle mr-2"></i>Automatic Management Status
                </h3>
                <div class="text-sm text-blue-700 space-y-2">
                    <p><strong>Disconnect Expired Users:</strong> Automatically disconnects users with expired subscriptions</p>
                    <p><strong>Suspend Unpaid Users:</strong> Suspends users with unpaid invoices (7+ days overdue)</p>
                    <p><strong>Reconnect Paid Users:</strong> Reconnects users after successful payment</p>
                    <p><strong>RouterOS API:</strong> All commands sent through MikroTik RouterOS API</p>
                </div>
                <div class="mt-4">
                    <div class="text-xs text-blue-600">
                        <i class="fas fa-clock mr-1"></i>
                        Last run: {{ now()->format('Y-m-d H:i:s') }}
                    </div>
                </div>
            </div>
        </div>

        <div>
            <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Recent connection activity</h3>
            @if($recentActivities->isEmpty())
                <div class="pb-card p-8 text-center text-slate-500 dark:text-slate-400">No recent activity.</div>
            @else
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Router</th>
                            <th>Updated</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivities as $customer)
                            <tr>
                                <td>
                                    <div class="font-mono font-semibold text-slate-900 dark:text-white">{{ $customer->username }}</div>
                                    <div class="text-sm text-slate-500">{{ $customer->full_name }}</div>
                                </td>
                                <td><x-status-badge :status="ucfirst($customer->status)" /></td>
                                <td>{{ $customer->router->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap text-sm">{{ $customer->updated_at->format('M j, Y H:i') }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <button type="button" onclick="checkUserStatus({{ $customer->id }})" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Status</button>
                                    @if($customer->status === 'active')
                                        <form action="{{ route('isp.user-connections.suspend', $customer) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Suspend this user?')">
                                            @csrf
                                            <button type="submit" class="text-sm font-semibold text-amber-600 dark:text-amber-400">Suspend</button>
                                        </form>
                                        <form action="{{ route('isp.user-connections.disconnect', $customer) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Disconnect this user?')">
                                            @csrf
                                            <button type="submit" class="text-sm font-semibold text-rose-600 dark:text-rose-400">Disconnect</button>
                                        </form>
                                    @else
                                        <form action="{{ route('isp.user-connections.reconnect', $customer) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Reconnect this user?')">
                                            @csrf
                                            <button type="submit" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Reconnect</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </div>
    </div>

    <!-- Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                    User Connection Status
                </h3>
                <div class="mt-2 px-7 py-3">
                    <div id="statusContent" class="text-sm text-gray-500 dark:text-gray-400">
                        Loading...
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function getStatusColor(status) {
            const colors = {
                'connected': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'active': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'suspended': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'disabled': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'disconnected': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'not_found': 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
                'error': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'unknown': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
            };
            return colors[status] || colors['unknown'];
        }

        function runAutomaticManagement() {
            if (confirm('Run automatic user management now? This will disconnect expired users, suspend unpaid users, and reconnect paid users.')) {
                fetch('{{ route("isp.user-connections.run-automatic") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Automatic user management job has been dispatched. Please wait a moment and refresh statistics.');
                        refreshStatistics();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while running automatic management.');
                });
            }
        }

        function refreshStatistics() {
            fetch('{{ route("isp.user-connections.statistics") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-customers').textContent = data.stats.total_customers;
                        document.getElementById('connected-customers').textContent = data.stats.active_customers;
                        document.getElementById('suspended-customers').textContent = data.stats.suspended_customers;
                        document.getElementById('disconnected-customers').textContent = data.stats.expired_customers;
                    }
                })
                .catch(error => {
                    console.error('Error refreshing statistics:', error);
                });
        }

        function checkUserStatus(customerId) {
            document.getElementById('statusContent').innerHTML = 'Loading...';
            document.getElementById('statusModal').classList.remove('hidden');

            const url = '{{ url('/isp/user-connections') }}/' + encodeURIComponent(customerId) + '/status';
            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then(async (response) => {
                    const text = await response.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error(response.status + ' ' + response.statusText + (text ? ': ' + text.slice(0, 120) : ''));
                    }
                    if (!response.ok) {
                        throw new Error(data.message || data.error || ('HTTP ' + response.status));
                    }
                    return data;
                })
                .then(data => {
                    if (data.success) {
                        const status = data.status;
                        let html = `
                            <div class="space-y-2">
                                <div><strong>Overall Status:</strong> <span class="px-2 py-1 rounded text-xs ${getStatusColor(status.overall)}">${status.overall}</span></div>
                                <div><strong>Hotspot:</strong> <span class="px-2 py-1 rounded text-xs ${getStatusColor(status.hotspot)}">${status.hotspot}</span></div>
                                <div><strong>PPPoE:</strong> <span class="px-2 py-1 rounded text-xs ${getStatusColor(status.pppoe)}">${status.pppoe}</span></div>
                            </div>
                        `;
                        if (status.message) {
                            html += '<p class="mt-2 text-xs text-slate-600 dark:text-slate-400">' + status.message + '</p>';
                        }
                        document.getElementById('statusContent').innerHTML = html;
                    } else {
                        document.getElementById('statusContent').innerHTML = '<div class="text-red-600">Error: ' + (data.message || 'Unknown') + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                    document.getElementById('statusContent').innerHTML = '<div class="text-red-600">Error checking user status: ' + (error.message || error) + '</div>';
                });
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        // Auto-refresh statistics every 30 seconds
        setInterval(refreshStatistics, 30000);
    </script>
@endsection
