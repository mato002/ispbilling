<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400">Platform security</p>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">
                    {{ __('Security alerts') }}
                </h2>
            </div>
            <a href="{{ route('admin.audit.activity') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">← Global activity</a>
        </div>
    </x-slot>

    <p class="mb-6 text-sm text-slate-600 dark:text-slate-400">
        Failed sign-ins, MikroTik API connection failures, and router automation jobs in <code class="rounded bg-slate-100 px-1 py-0.5 text-xs dark:bg-slate-800">failed</code> status.
    </p>

    <div class="space-y-8">
        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Failed login attempts</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Invalid credential events across all tenants.</p>
            </div>
            <div class="overflow-x-hidden">
                <table class="table-fixed w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50">
                        <tr>
                            <th class="w-[9.5rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Time</th>
                            <th class="w-[8rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">ISP</th>
                            <th class="w-[10rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Account</th>
                            <th class="w-[8rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">IP</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($failedLogins as $log)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-2.5 align-top whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2.5 align-top text-sm text-slate-800 dark:text-slate-200 break-words">{{ $log->isp?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 align-top text-xs text-slate-500 dark:text-slate-400 break-words">{{ $log->metadata['attempted_email'] ?? $log->user?->email ?? '—' }}</td>
                                <td class="px-4 py-2.5 align-top">
                                    @if ($log->ip_address)
                                        <code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded text-rose-600 dark:bg-slate-800 dark:text-rose-400">{{ $log->ip_address }}</code>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 align-top text-sm text-slate-700 dark:text-slate-300">
                                    <span class="block max-w-lg break-words whitespace-normal">{!! \App\Support\SecurityAlertPresenter::formatLogDescription($log->description) !!}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-0">
                                    <div class="flex flex-col items-center justify-center gap-3 rounded-xl bg-slate-50/50 py-12 dark:bg-slate-800/30">
                                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                <path d="m9 12 2 2 4-4"/>
                                            </svg>
                                        </span>
                                        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">No failed authentication events recorded.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($failedLogins->hasPages())
                <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">{{ $failedLogins->links() }}</div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Router API connection failures</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">MikroTik session errors grouped by severity tier.</p>
            </div>
            <div class="overflow-x-hidden">
                <table class="table-fixed w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50">
                        <tr>
                            <th class="w-[8.5rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Severity</th>
                            <th class="w-[9.5rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Time</th>
                            <th class="w-[8rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">ISP</th>
                            <th class="w-[10rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Staff</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($routerApiFailures as $log)
                            @php $severity = \App\Support\SecurityAlertPresenter::routerApiSeverity($log->description); @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-2.5 align-top whitespace-nowrap">
                                    <x-admin.security.severity-badge :tier="$severity" />
                                </td>
                                <td class="px-4 py-2.5 align-top whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2.5 align-top text-sm text-slate-800 dark:text-slate-200 break-words">{{ $log->isp?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 align-top text-xs text-slate-500 dark:text-slate-400 break-words">{{ $log->user?->email ?? '—' }}</td>
                                <td class="px-4 py-2.5 align-top text-sm text-slate-700 dark:text-slate-300">
                                    <span class="block max-w-lg break-words whitespace-normal">{!! \App\Support\SecurityAlertPresenter::formatLogDescription($log->description) !!}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No router API failures recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($routerApiFailures->hasPages())
                <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">{{ $routerApiFailures->links() }}</div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Failed router jobs (automation)</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Provisioner and automation actions that ended in failure.</p>
            </div>
            <div class="overflow-x-hidden">
                <table class="table-fixed w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50">
                        <tr>
                            <th class="w-[9.5rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Time</th>
                            <th class="w-[8rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">ISP</th>
                            <th class="w-[8rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Router</th>
                            <th class="w-[8.5rem] px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Action</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($failedRouterJobs as $job)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-2.5 align-top whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">{{ $job->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2.5 align-top text-sm text-slate-800 dark:text-slate-200 break-words">{{ $job->isp?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 align-top text-sm text-slate-800 dark:text-slate-200 break-words">{{ $job->router?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 align-top font-mono text-xs text-slate-600 dark:text-slate-400 break-words">{{ $job->action }}</td>
                                <td class="px-4 py-2.5 align-top text-xs text-rose-700 dark:text-rose-300">
                                    <span class="block max-w-lg break-words whitespace-normal">{!! \App\Support\SecurityAlertPresenter::formatLogDescription(\Illuminate\Support\Str::limit($job->error_message ?? $job->description, 200)) !!}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No failed router jobs.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($failedRouterJobs->hasPages())
                <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">{{ $failedRouterJobs->links() }}</div>
            @endif
        </section>
    </div>
</x-admin-layout>
