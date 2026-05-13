<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Security alerts') }}
            </h2>
            <a href="{{ route('admin.audit.activity') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">← Global activity</a>
        </div>
    </x-slot>

    <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
        Failed sign-ins (invalid credentials), MikroTik API connection failures, and router automation jobs that ended in <code class="text-xs">failed</code> status.
    </p>

    <div class="space-y-10">
        <section>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Failed login attempts</h3>
            <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Time</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">ISP</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Account</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">IP</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($failedLogins as $log)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2">{{ $log->isp?->name ?? '—' }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $log->metadata['attempted_email'] ?? $log->user?->email ?? '—' }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No failed logins in this page range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $failedLogins->links() }}</div>
            </div>
        </section>

        <section>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Router API connection failures</h3>
            <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Time</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">ISP</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Staff</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($routerApiFailures as $log)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2">{{ $log->isp?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-xs">{{ $log->user?->email ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No router API failures recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $routerApiFailures->links() }}</div>
            </div>
        </section>

        <section>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Failed router jobs (automation)</h3>
            <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Time</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">ISP</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Router</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Action</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($failedRouterJobs as $job)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $job->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2">{{ $job->isp?->name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $job->router?->name ?? '—' }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $job->action }}</td>
                                <td class="px-4 py-2 text-red-700 dark:text-red-300 text-xs">{{ \Illuminate\Support\Str::limit($job->error_message ?? $job->description, 120) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No failed router jobs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $failedRouterJobs->links() }}</div>
            </div>
        </section>
    </div>
</x-admin-layout>
