@extends('layouts.admin')

@section('title', 'IP Pools')

@section('header')
    <x-isp-page-header
        title="IP pools"
        module="Network"
        description="DHCP-style ranges for hotspot and PPPoE client addressing."
        primaryLabel="Create pool"
        :primaryHref="route('isp.ip-pools.create')"
    >
        <x-slot name="actions">
            <button type="button" onclick="openSyncModal()" class="pb-btn">Sync from router</button>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
<div class="space-y-6">
    <div class="pb-card p-4 sm:p-5 text-sm text-slate-600 dark:text-slate-300">
        <p class="font-semibold text-slate-900 dark:text-white">Addressing</p>
        <p class="mt-1">Create pools manually or sync from RouterOS. Link pools to hotspot profiles and plans as needed.</p>
    </div>

    <x-filter-bar :action="route('isp.ip-pools.index')">
        <div class="min-w-[160px]">
            <label class="isp-form-label">Status</label>
            <select name="is_active" class="isp-form-select">
                <option value="">All</option>
                <option value="1" @selected(request('is_active') === '1')>Active</option>
                <option value="0" @selected(request('is_active') === '0')>Inactive</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.ip-pools.index') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($ipPools->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state title="No IP pools" message="Create a pool or sync from a router to get started." />
            <div class="flex flex-wrap justify-center gap-2 border-t border-slate-100 px-6 pb-6 dark:border-slate-800">
                <a href="{{ route('isp.ip-pools.create') }}" class="pb-btn pb-btn-primary">Create pool</a>
                <button type="button" onclick="openSyncModal()" class="pb-btn">Sync from router</button>
            </div>
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Pool</th>
                    <th>Range</th>
                    <th>Router</th>
                    <th>IPs</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ipPools as $pool)
                    <tr>
                        <td class="font-semibold text-slate-900 dark:text-white">{{ $pool->name }}</td>
                        <td class="font-mono text-sm">{{ $pool->formatted_range }}</td>
                        <td>
                            <div>{{ $pool->router->name }}</div>
                            <div class="text-xs text-slate-500 font-mono">{{ $pool->router->ip_address }}</div>
                        </td>
                        <td class="tabular-nums">{{ $pool->total_ips }}</td>
                        <td>
                            @if($pool->is_active)
                                <x-status-badge status="active" />
                            @else
                                <x-status-badge status="inactive" />
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('isp.ip-pools.show', $pool) }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">View</a>
                            <a href="{{ route('isp.ip-pools.edit', $pool) }}" class="ml-3 text-sm font-semibold text-slate-600 dark:text-slate-300">Edit</a>
                            @if($pool->is_active)
                                <form action="{{ route('isp.ip-pools.deactivate', $pool) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Deactivate?');">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold text-amber-600 dark:text-amber-400">Pause</button>
                                </form>
                            @else
                                <form action="{{ route('isp.ip-pools.activate', $pool) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Activate?');">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Activate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $ipPools->withQueryString()->links() }}</div>
    @endif
</div>

<div id="syncModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-full items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" onclick="if(event.target===this) closeSyncModal()">
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Sync IP pools</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Select a router to import pool definitions.</p>
            <form method="POST" action="{{ route('isp.ip-pools.sync') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="isp-form-label">Router</label>
                    <select name="router_id" required class="isp-form-select">
                        <option value="">Select router</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->ip_address }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeSyncModal()" class="pb-btn">Cancel</button>
                    <button type="submit" class="pb-btn pb-btn-primary">Sync</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openSyncModal() { document.getElementById('syncModal').classList.remove('hidden'); }
    function closeSyncModal() { document.getElementById('syncModal').classList.add('hidden'); }
</script>
@endsection
