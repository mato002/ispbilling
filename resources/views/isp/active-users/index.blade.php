@extends('layouts.admin')

@section('title', 'Active users')

@section('header')
    <x-isp-page-header
        title="Active users"
        module="Network operations"
        description="Live hotspot and PPPoE sessions polled from your routers."
    >
        <x-slot name="actions">
            <button type="button" onclick="refreshUsers()" class="pb-btn">Refresh</button>
            <button type="button" onclick="toggleAutoRefresh()" id="autoRefreshBtn" class="pb-btn pb-btn-primary">Auto: ON</button>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
<div class="space-y-6">
    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard.kpi-card title="Active users" :value="number_format($stats['total_active_users'] ?? 0)" valueId="total-users" subtext="Live sessions" iconBg="from-indigo-500 to-cyan-500">
            <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Routers online" :value="number_format($stats['connected_routers'] ?? 0)" subtext="With active sessions" iconBg="from-emerald-500 to-teal-500">
            <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14 0"/><path d="M2 9.32a16 16 0 0 1 20 0"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Avg duration" :value="round($stats['average_duration'] ?? 0).' min'" subtext="Session length" iconBg="from-amber-500 to-orange-500">
            <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Total routers" :value="number_format($stats['total_routers'] ?? 0)" subtext="In tenant" iconBg="from-violet-500 to-indigo-500">
            <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
    </section>

    <x-filter-bar :action="route('isp.active-users.index')">
        <div class="min-w-[220px] flex-1">
            <label class="isp-form-label">Router</label>
            <select name="router_id" class="isp-form-select">
                <option value="">All routers</option>
                @foreach($routers as $router)
                    <option value="{{ $router->id }}" @selected(request('router_id') == $router->id)>{{ $router->name }} ({{ $router->ip_address }})</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <div>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Live sessions</p>
            <p class="text-xs text-slate-500">Updated <span id="last-update">{{ now()->format('H:i:s') }}</span></p>
        </div>
        <x-data-table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>IP</th>
                    <th>Device</th>
                    <th>Duration</th>
                    <th>Type</th>
                    <th>Router</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="users-table-body">
                <tr id="loading-row">
                    <td colspan="7" class="py-10 text-center text-slate-500">
                        <span class="inline-flex items-center gap-2">
                            <svg class="h-5 w-5 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            Loading…
                        </span>
                    </td>
                </tr>
            </tbody>
        </x-data-table>
    </div>
</div>

<div id="disconnectModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-full items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" onclick="if(event.target===this) closeDisconnectModal()">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Disconnect user</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Terminate session for <strong id="disconnect-username"></strong>?</p>
            <input type="hidden" id="disconnect-user-id">
            <input type="hidden" id="disconnect-router-id">
            <input type="hidden" id="disconnect-connection-type">
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeDisconnectModal()" class="pb-btn">Cancel</button>
                <button type="button" onclick="confirmDisconnect()" class="pb-btn border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">Disconnect</button>
            </div>
        </div>
    </div>
</div>

<script>
    let autoRefresh = true;
    let refreshInterval;

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

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
    }

    function updateUsersTable(users) {
        const tbody = document.getElementById('users-table-body');
        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="py-10 text-center text-slate-500">No active users for this filter.</td></tr>';
            return;
        }
        tbody.innerHTML = users.map(user => {
            const un = escapeHtml(user.username);
            const mac = escapeHtml(user.mac_address || 'N/A');
            const ip = escapeHtml(user.ip_address || 'N/A');
            const dev = escapeHtml(user.device || 'Unknown');
            const dur = escapeHtml(user.duration || '0 min');
            const ctype = escapeHtml(user.connection_type || '');
            const rtr = escapeHtml(user.router || '');
            const uid = escapeHtml(String(user.id));
            const rid = escapeHtml(String(user.router_id || ''));
            const ctLower = String(user.connection_type || '').toLowerCase();
            const typePill = user.connection_type === 'Hotspot'
                ? 'pb-pill pb-pill-cyan'
                : 'pb-pill pb-pill-info';
            const uEnc = encodeURIComponent(user.username || '');
            return `<tr>
                <td><div class="font-semibold text-slate-900 dark:text-white">${un}</div><div class="font-mono text-xs text-slate-500">${mac}</div></td>
                <td class="font-mono text-sm">${ip}</td>
                <td><span class="pb-pill pb-pill-info text-xs">${dev}</span></td>
                <td>${dur}</td>
                <td><span class="${typePill} text-xs">${ctype}</span></td>
                <td>${rtr}</td>
                <td class="text-right"><button type="button" class="text-sm font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400 js-disconnect" data-uid="${uid}" data-rid="${rid}" data-ct="${encodeURIComponent(ctLower)}" data-un="${uEnc}">Disconnect</button></td>
            </tr>`;
        }).join('');
    }

    document.getElementById('users-table-body').addEventListener('click', function(e) {
        const btn = e.target.closest('.js-disconnect');
        if (!btn) return;
        const username = decodeURIComponent(btn.getAttribute('data-un') || '');
        const ct = decodeURIComponent(btn.getAttribute('data-ct') || '');
        openDisconnectModal(username, btn.getAttribute('data-uid') || '', ct, btn.getAttribute('data-rid') || '');
    });

    function updateStats(data) {
        document.getElementById('total-users').textContent = data.count || 0;
    }

    function refreshUsers() { loadUsers(); }

    function startAutoRefresh() {
        clearInterval(refreshInterval);
        if (autoRefresh) {
            refreshInterval = setInterval(loadUsers, 10000);
        }
    }

    function toggleAutoRefresh() {
        autoRefresh = !autoRefresh;
        const btn = document.getElementById('autoRefreshBtn');
        if (autoRefresh) {
            btn.textContent = 'Auto: ON';
            btn.classList.add('pb-btn-primary');
            startAutoRefresh();
        } else {
            btn.textContent = 'Auto: OFF';
            btn.classList.remove('pb-btn-primary');
            clearInterval(refreshInterval);
        }
    }

    function openDisconnectModal(username, userId, connectionType, routerId) {
        document.getElementById('disconnect-username').textContent = username;
        document.getElementById('disconnect-user-id').value = userId;
        document.getElementById('disconnect-connection-type').value = connectionType;
        document.getElementById('disconnect-router-id').value = routerId || '';
        document.getElementById('disconnectModal').classList.remove('hidden');
    }

    function closeDisconnectModal() {
        document.getElementById('disconnectModal').classList.add('hidden');
    }

    function confirmDisconnect() {
        const userId = document.getElementById('disconnect-user-id').value;
        const connectionType = document.getElementById('disconnect-connection-type').value;
        const routerId = document.getElementById('disconnect-router-id').value
            || new URLSearchParams(window.location.search).get('router_id')
            || '';
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
                loadUsers();
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

    function showError(message) { alert('Error: ' + message); }
    function showSuccess(message) { alert('Success: ' + message); }
</script>
@endsection
