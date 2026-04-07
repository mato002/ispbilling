@extends('layouts.dashboard')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Hotspot Sessions</h2>
                    <div class="flex gap-2">
                        <a href="{{ route('isp.sessions.index', ['status' => 'active']) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Active Sessions
                        </a>
                        <a href="{{ route('isp.sessions.index', ['status' => 'ended']) }}" 
                           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                            History
                        </a>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Active Sessions</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['active_sessions'] ?? 0 }}</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Sessions Today</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['total_today'] ?? 0 }}</p>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Bandwidth Today</p>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ number_format(($stats['total_bandwidth_today'] ?? 0) / 1024 / 1024, 2) }} GB
                        </p>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <select name="router_id" class="w-full border-gray-300 rounded-lg">
                            <option value="">All Routers</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>
                                    {{ $router->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="text" name="search" placeholder="Search..." 
                               value="{{ request('search') }}"
                               class="w-full border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="w-full border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                            Filter
                        </button>
                    </div>
                </form>

                <!-- Sessions Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Router</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">IP Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data Used</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Uptime</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Started</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($sessions as $session)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $session->username }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $session->customer->full_name ?? 'Voucher User' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $session->router->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $session->ip_address }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $session->formatted_total_bytes }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $session->uptime_formatted }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $session->started_at->format('M d, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('isp.sessions.show', $session) }}" 
                                           class="text-blue-600 hover:text-blue-900">View</a>
                                        @if($session->status === 'active')
                                            <form method="POST" action="{{ route('isp.sessions.disconnect', $session) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-900 ml-2">Disconnect</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                        No sessions found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $sessions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection





