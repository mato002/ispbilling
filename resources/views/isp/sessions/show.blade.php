@extends('layouts.dashboard')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Session Details</h2>
                    <a href="{{ route('isp.sessions.index') }}" class="text-blue-600 hover:text-blue-700">
                        ← Back to Sessions
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Session Info -->
                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold mb-4">Session Information</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Username</dt>
                                <dd class="text-lg font-medium">{{ $session->username }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Status</dt>
                                <dd class="text-lg">
                                    <span class="px-2 py-1 rounded {{ $session->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($session->status) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">IP Address</dt>
                                <dd class="text-lg font-mono">{{ $session->ip_address }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">MAC Address</dt>
                                <dd class="text-lg font-mono">{{ $session->mac_address ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Router</dt>
                                <dd class="text-lg">{{ $session->router->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Started At</dt>
                                <dd class="text-lg">{{ $session->started_at->format('M d, Y H:i:s') }}</dd>
                            </div>
                            @if($session->ended_at)
                                <div>
                                    <dt class="text-sm text-gray-600 dark:text-gray-400">Ended At</dt>
                                    <dd class="text-lg">{{ $session->ended_at->format('M d, Y H:i:s') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Usage Stats -->
                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold mb-4">Usage Statistics</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Data Uploaded</dt>
                                <dd class="text-lg font-medium">{{ $session->formatted_uploaded }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Data Downloaded</dt>
                                <dd class="text-lg font-medium">{{ $session->formatted_downloaded }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Total Data</dt>
                                <dd class="text-lg font-medium text-blue-600">{{ $session->formatted_total_bytes }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Uptime</dt>
                                <dd class="text-lg font-medium">{{ $session->uptime_formatted }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                @if($session->customer)
                    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold mb-4">Customer Information</h3>
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Name</dt>
                                <dd class="text-lg">{{ $session->customer->full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Phone</dt>
                                <dd class="text-lg">{{ $session->customer->phone_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Status</dt>
                                <dd class="text-lg">{{ ucfirst($session->customer->status) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600 dark:text-gray-400">Connection Type</dt>
                                <dd class="text-lg">{{ strtoupper($session->customer->connection_type) }}</dd>
                            </div>
                        </dl>
                        <div class="mt-4">
                            <a href="{{ route('isp.customers.show', $session->customer) }}" 
                               class="text-blue-600 hover:text-blue-700">
                                View Customer Details →
                            </a>
                        </div>
                    </div>
                @endif

                @if($session->status === 'active')
                    <div class="mt-6">
                        <form method="POST" action="{{ route('isp.sessions.disconnect', $session) }}">
                            @csrf
                            <button type="submit" 
                                    class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Force Disconnect
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection





