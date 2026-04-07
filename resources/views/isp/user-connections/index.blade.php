@extends('layouts.admin')

@section('title', 'User Connection Management')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('User Connection Management') }}
        </h2>
        <div class="flex space-x-3">
            <button onclick="runAutomaticManagement()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-robot mr-2"></i>Run Automatic Management
            </button>
            <button onclick="refreshStatistics()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-sync mr-2"></i>Refresh Statistics
            </button>
        </div>
    </div>
@endsection

@php
    function getStatusColor($status) {
        return match($status) {
            'active' => 'bg-green-100 text-green-800',
            'suspended' => 'bg-yellow-100 text-yellow-800',
            'expired' => 'bg-red-100 text-red-800',
            'inactive' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
@endphp

@section('content')
    <div class="space-y-6">
        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Customers</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" id="total-customers">{{ $stats['total_customers'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-wifi text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Connected</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" id="connected-customers">{{ $stats['active_customers'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-pause-circle text-2xl text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Suspended</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" id="suspended-customers">{{ $stats['suspended_customers'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-unlink text-2xl text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Disconnected</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" id="disconnected-customers">{{ $stats['expired_customers'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        <!-- Recent Activities -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Connection Activities</h3>
                
                @if($recentActivities->isEmpty())
                    <div class="text-center py-8">
                        <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">No recent activities found.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Customer
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Router
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Last Updated
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentActivities as $customer)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $customer->username }}
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $customer->full_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ getStatusColor($customer->status) }}">
                                                {{ ucfirst($customer->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $customer->router->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $customer->updated_at->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <button onclick="checkUserStatus({{ $customer->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="Check Status">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                                
                                                @if($customer->status === 'active')
                                                    <form action="{{ route('isp.user-connections.suspend', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Suspend this user?')">
                                                        @csrf
                                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" title="Suspend">
                                                            <i class="fas fa-pause"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('isp.user-connections.disconnect', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Disconnect this user?')">
                                                        @csrf
                                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="Disconnect">
                                                            <i class="fas fa-unlink"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('isp.user-connections.reconnect', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Reconnect this user?')">
                                                        @csrf
                                                        <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Reconnect">
                                                            <i class="fas fa-link"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
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
                'suspended': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'disconnected': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
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

            fetch(`/isp/user-connections/status/${customerId}`)
                .then(response => response.json())
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
                        document.getElementById('statusContent').innerHTML = html;
                    } else {
                        document.getElementById('statusContent').innerHTML = '<div class="text-red-600">Error: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                    document.getElementById('statusContent').innerHTML = '<div class="text-red-600">Error checking user status</div>';
                });
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        // Auto-refresh statistics every 30 seconds
        setInterval(refreshStatistics, 30000);
    </script>
@endsection
