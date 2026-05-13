<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Global activity log') }}
            </h2>
            <a href="{{ route('admin.audit.security') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Security alerts →</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">Events (24h)</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($snapshot['events_24h']) }}</p>
        </div>
        <div class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">Failed logins (24h)</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($snapshot['failed_logins_24h']) }}</p>
        </div>
        <div class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">Router API failures (24h)</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($snapshot['router_api_failures_24h']) }}</p>
        </div>
        <div class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm flex items-center">
            <p class="text-xs text-gray-600 dark:text-gray-300">Includes all ISPs: staff changes, billing, routers, and platform-scoped events where logged.</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-6">
        <form method="GET" action="{{ route('admin.audit.activity') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 text-sm">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Description, action, IP…"
                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">ISP</label>
                <select name="isp_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">All</option>
                    @foreach ($isps as $isp)
                        <option value="{{ $isp->id }}" @selected(request('isp_id') == $isp->id)>{{ $isp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Severity</label>
                <select name="severity" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">Any</option>
                    @foreach (\App\Support\ActivityLogAudit::SEVERITIES as $sev)
                        <option value="{{ $sev }}" @selected(request('severity') === $sev)>{{ ucfirst($sev) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Module</label>
                <select name="module" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">Any</option>
                    @foreach (\App\Support\ActivityLogAudit::MODULES as $key => $label)
                        <option value="{{ $key }}" @selected(request('module') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-white text-sm font-semibold hover:bg-indigo-500">Filter</button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Time</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">ISP</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">User</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Action</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Description</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($logs as $log)
                        @php
                            $severity = \App\Support\ActivityLogAudit::severity($log);
                            $moduleKey = \App\Support\ActivityLogAudit::moduleKey($log);
                        @endphp
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $log->isp?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $log->user?->email ?? '—' }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-800 dark:text-gray-100">{{ $log->action }}</td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-200 max-w-md truncate" title="{{ $log->description }}">{{ $log->description }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200">{{ $severity }}</span>
                                <span class="ml-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold bg-indigo-50 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-200">{{ \App\Support\ActivityLogAudit::MODULES[$moduleKey] ?? $moduleKey }}</span>
                                <a href="{{ route('admin.audit.activity.show', $log) }}" class="ml-2 text-indigo-600 dark:text-indigo-400 hover:underline text-xs">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</x-admin-layout>
