@extends('layouts.admin')

@section('title', 'IP Pools')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('IP Pools') }}
        </h2>
        <div class="flex space-x-3">
            <a href="{{ route('isp.ip-pools.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create Pool
            </a>
            <button onclick="openSyncModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-sync mr-2"></i>Sync from Router
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Quick Start Guide -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-3">🌐 IP Pool Configuration</h3>
            <p class="text-blue-700 mb-4">IP pools assign addresses to users connecting to the hotspot:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg p-4 border border-blue-200">
                    <h4 class="font-semibold text-gray-800 mb-2">Example Pool</h4>
                    <table class="text-sm w-full">
                        <tr class="border-b">
                            <td class="py-2 font-medium">Pool Name:</td>
                            <td class="py-2">hotspot_pool</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 font-medium">Range:</td>
                            <td class="py-2">192.168.10.2 – 192.168.10.200</td>
                        </tr>
                        <tr>
                            <td class="py-2 font-medium">Total IPs:</td>
                            <td class="py-2">199 addresses</td>
                        </tr>
                    </table>
                </div>
                
                <div class="bg-white rounded-lg p-4 border border-blue-200">
                    <h4 class="font-semibold text-gray-800 mb-2">Configuration Steps</h4>
                    <ol class="text-sm space-y-2 list-decimal list-inside">
                        <li>Click <strong>Create Pool</strong> or <strong>Sync from Router</strong></li>
                        <li>Define pool name and IP range</li>
                        <li>Set gateway and netmask (optional)</li>
                        <li>Configure lease time and DNS servers</li>
                        <li>Activate pool for hotspot use</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="GET" action="{{ route('isp.ip-pools.index') }}" class="flex gap-4">
                    <div>
                        <select name="is_active" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">All Status</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Filter
                    </button>
                </form>
            </div>
        </div>

        <!-- IP Pools List -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Your IP Pools</h3>
                
                @if($ipPools->isEmpty())
                    <div class="text-center py-8">
                        <i class="fas fa-network-wired text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">No IP pools found.</p>
                        <p class="text-sm text-gray-400 mt-2">Create or sync IP pools to manage user addresses.</p>
                        <div class="mt-4 space-x-3">
                            <a href="{{ route('isp.ip-pools.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Create Pool
                            </a>
                            <button onclick="openSyncModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                <i class="fas fa-sync mr-2"></i>Sync from Router
                            </button>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Pool Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Range
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Router
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Total IPs
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($ipPools as $pool)
                                    <tr class="{{ $pool->is_active ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fas fa-network-wired text-blue-600 mr-2"></i>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $pool->name }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                {{ $pool->formatted_range }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                {{ $pool->router->name }}
                                                <div class="text-xs text-gray-500">{{ $pool->router->ip_address }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                {{ $pool->total_ips }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $pool->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                                {{ $pool->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="{{ route('isp.ip-pools.show', $pool) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('isp.ip-pools.edit', $pool) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($pool->is_active)
                                                    <form action="{{ route('isp.ip-pools.deactivate', $pool) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" title="Deactivate">
                                                            <i class="fas fa-pause"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('isp.ip-pools.activate', $pool) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Activate">
                                                            <i class="fas fa-play"></i>
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
                    
                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $ipPools->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sync Modal -->
    <div id="syncModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                    Sync IP Pools
                </h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Select a router to sync IP pools from:
                    </p>
                    
                    <form method="POST" action="{{ route('isp.ip-pools.sync') }}" class="mt-4">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Router *</label>
                            <select name="router_id" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="">Select Router</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->ip_address }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeSyncModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                <i class="fas fa-sync mr-2"></i>Sync Pools
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openSyncModal() {
            document.getElementById('syncModal').classList.remove('hidden');
        }
        
        function closeSyncModal() {
            document.getElementById('syncModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('syncModal');
            if (event.target == modal) {
                closeSyncModal();
            }
        }
    </script>
@endsection
