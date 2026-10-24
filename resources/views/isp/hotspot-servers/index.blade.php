@extends('layouts.admin')

@section('title', 'Hotspot Servers')

@section('header')
    <x-isp-page-header
        title="Hotspot servers"
        module="Network"
        description="Interfaces and login methods synced from MikroTik for voucher and subscriber access."
    >
        <x-slot name="actions">
            <button type="button" onclick="openSyncModal()" class="pb-btn pb-btn-primary">Sync from router</button>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="space-y-3">

        <x-resource-index
            :filter-action="route('isp.hotspot-servers.index')"
            :filter-chips="$filterChips ?? []"
            :export-formats="$exportFormats ?? []"
            :paginator="$hotspotServers"
            :empty="$hotspotServers->isEmpty()"
            empty-title="No hotspot servers"
            empty-message="Sync from a configured router to import hotspot server definitions."
        >
            <x-slot:filters>
                <x-listing-filters.select
                    name="is_active"
                    label="Status"
                    placeholder="All"
                    class="min-w-[160px]"
                    :options="['1' => 'Active', '0' => 'Inactive']"
                />
                <x-listing-filters.per-page />
            </x-slot:filters>

            <x-slot:emptySlot>
                <div class="pb-card p-0">
                    <x-empty-state
                        title="No hotspot servers"
                        message="Sync from a configured router to import hotspot server definitions."
                    />
                    <div class="border-t border-slate-100 px-6 pb-6 text-center dark:border-slate-800">
                        <button type="button" onclick="openSyncModal()" class="pb-btn pb-btn-primary">Sync from router</button>
                    </div>
                </div>
            </x-slot:emptySlot>

            <x-slot:table>
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Interface</th>
                            <th>Router</th>
                            <th>Status</th>
                            <th>Login</th>
                            <x-table-actions.header />
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hotspotServers as $server)
                            <x-listing-table.row :href="route('isp.hotspot-servers.show', $server)">
                                <td class="font-semibold text-slate-900 dark:text-white">{{ $server->name }}</td>
                                <td class="font-mono text-sm">{{ $server->interface }}</td>
                                <td>
                                    <div>{{ $server->router->name }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ $server->router->ip_address }}</div>
                                </td>
                                <td>
                                    @if ($server->is_active)
                                        <x-status-badge status="active" />
                                    @else
                                        <x-status-badge status="inactive" />
                                    @endif
                                </td>
                                <td class="text-sm">
                                    {{ ucfirst($server->login_by) }}
                                    @if ($server->use_radius)
                                        <span class="ml-1 pb-pill pb-pill-info text-[10px]">RADIUS</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <x-table-actions>
                                        <x-table-actions.link :href="route('isp.hotspot-servers.show', $server)" label="View" />
                                        <x-table-actions.link :href="route('isp.hotspot-servers.edit', $server)" label="Edit" />
                                        @if ($server->is_active)
                                            <x-table-actions.form
                                                :action="route('isp.hotspot-servers.deactivate', $server)"
                                                label="Pause"
                                                :danger="false"
                                                onsubmit="return confirm('Deactivate this server?');"
                                            />
                                        @else
                                            <x-table-actions.form
                                                :action="route('isp.hotspot-servers.activate', $server)"
                                                label="Activate"
                                                :danger="false"
                                                onsubmit="return confirm('Activate this server?');"
                                            />
                                        @endif
                                    </x-table-actions>
                                </td>
                            </x-listing-table.row>
                        @endforeach
                    </tbody>
                </x-data-table>
            </x-slot:table>
        </x-resource-index>
    </div>

    <div id="syncModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex min-h-full items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" onclick="if(event.target===this) closeSyncModal()">
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900" onclick="event.stopPropagation()">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Sync hotspot servers</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Select a router to pull hotspot server definitions from RouterOS.</p>
                <form method="POST" action="{{ route('isp.hotspot-servers.sync') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="isp-form-label">Router</label>
                        <select name="router_id" required class="isp-form-select">
                            <option value="">Select router</option>
                            @foreach ($routers as $router)
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
        function openSyncModal() {
            document.getElementById('syncModal').classList.remove('hidden');
        }
        function closeSyncModal() {
            document.getElementById('syncModal').classList.add('hidden');
        }
    </script>
@endsection
