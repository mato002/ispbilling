@extends('layouts.admin')

@section('title', 'Active Sessions')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Active Sessions') }}
        </h2>
        <div class="flex space-x-3">
            <button onclick="refreshUsers()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-sync mr-2"></i>Refresh
            </button>
            <button onclick="toggleAutoRefresh()" id="autoRefreshBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-clock mr-2"></i>Auto Refresh: ON
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Users</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" id="total-users">{{ $stats['total_active_users'] }}</p>
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
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Connected Routers</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" id="connected-routers">{{ $stats['connected_routers'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-2xl text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Duration</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" id="avg-duration">{{ round($stats['average_duration']) }} min</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-server text-2xl text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Routers</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['total_routers'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="GET" action="{{ route('isp.active-users.index') }}" class="flex gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filter by Router</label>
                        <select name="router_id" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">All Routers</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>
                                    {{ $router->name }} ({{ $router->ip_address }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Filter
                    </button>
                </form>
            </div>
        </div>

        <!-- Active Users Table -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Real-time Active Users</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Last updated: <span id="last-update">{{ now()->format('H:i:s') }}</span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    User
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    IP Address
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Device
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Duration
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Router
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Users will be loaded here via JavaScript -->
                            <tr id="loading-row">
                                <td colspan="7" class="px-6 py-8 text-center">
                                    <div class="flex justify-center items-center">
                                        <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="ml-2 text-gray-600">Loading active users...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Disconnect Confirmation Modal -->
    <div id="disconnectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                    Disconnect User
                </h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to disconnect <strong id="disconnect-username"></strong>?
                    </p>
                    <input type="hidden" id="disconnect-user-id">
                    <input type="hidden" id="disconnect-router-id">
                    <input type="hidden" id="disconnect-connection-type">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDisconnectModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmDisconnect()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Disconnect
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let autoRefresh = true;
        let refreshInterval;

        // Load users on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            startAutoRefresh();
        });

        function loadUsers() {
            const routerId = new URLSearchParams(window.location.search).get('router_id');
            
            fetch('{{ route("isp.active-users.realtime") }}' + (routerId ? '?router_id=' + routerId : ''))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateUsersTable(data.users);
                        updateStats(data);
                        document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
                    } else {
                        console.error('Error loading users:', data.error);
                        showError('Failed to load active users');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Network error while loading users');
                });
        }

        function updateUsersTable(users) {
            const tbody = document.getElementById('users-table-body');
            
            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-user-slash text-4xl mb-4"></i>
                            <p>No active users found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = users.map(user => `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <i class="fas fa-user-circle text-gray-400 mr-2"></i>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${user.username}</div>
                                <div class="text-xs text-gray-500">${user.mac_address || 'N/A'}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                            <i class="fas fa-network-wired text-gray-400 mr-1"></i>
                            ${user.ip_address || 'N/A'}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <i class="fas fa-mobile-alt text-gray-400 mr-2"></i>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                ${user.device || 'Unknown'}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                            <i class="fas fa-clock text-gray-400 mr-1"></i>
                            ${user.duration || '0 min'}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.connection_type === 'Hotspot' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800'}">
                            ${user.connection_type}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                            <i class="fas fa-server text-gray-400 mr-1"></i>
                            ${user.router}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="openDisconnectModal('${user.username}', '${user.id}', '${user.connection_type}')" 
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" 
                                title="Disconnect user">
                            <i class="fas fa-unlink"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function updateStats(data) {
            document.getElementById('total-users').textContent = data.count || 0;
        }

        function refreshUsers() {
            loadUsers();
        }

        function startAutoRefresh() {
            if (autoRefresh) {
                refreshInterval = setInterval(loadUsers, 10000); // Refresh every 10 seconds
            }
        }

        function toggleAutoRefresh() {
            autoRefresh = !autoRefresh;
            const btn = document.getElementById('autoRefreshBtn');
            
            if (autoRefresh) {
                btn.innerHTML = '<i class="fas fa-clock mr-2"></i>Auto Refresh: ON';
                btn.className = 'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700';
                startAutoRefresh();
            } else {
                btn.innerHTML = '<i class="fas fa-pause mr-2"></i>Auto Refresh: OFF';
                btn.className = 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700';
                clearInterval(refreshInterval);
            }
        }

        function openDisconnectModal(username, userId, connectionType) {
            document.getElementById('disconnect-username').textContent = username;
            document.getElementById('disconnect-user-id').value = userId;
            document.getElementById('disconnect-connection-type').value = connectionType;
            document.getElementById('disconnectModal').classList.remove('hidden');
        }

        function closeDisconnectModal() {
            document.getElementById('disconnectModal').classList.add('hidden');
        }

        function confirmDisconnect() {
            const userId = document.getElementById('disconnect-user-id').value;
            const connectionType = document.getElementById('disconnect-connection-type').value;
            const routerId = new URLSearchParams(window.location.search).get('router_id') || '';

            fetch('{{ route("isp.active-users.disconnect") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    user_id: userId,
                    connection_type: connectionType.toLowerCase(),
                    router_id: routerId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDisconnectModal();
                    loadUsers(); // Refresh the table
                    showSuccess('User disconnected successfully');
                } else {
                    showError('Failed to disconnect user: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error while disconnecting user');
            });
        }

        function showError(message) {
            // Simple error notification (you can replace with a better notification system)
            alert('Error: ' + message);
        }

        function showSuccess(message) {
            // Simple success notification (you can replace with a better notification system)
            alert('Success: ' + message);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('disconnectModal');
            if (event.target == modal) {
                closeDisconnectModal();
            }
        }
    </script>
@endsection
