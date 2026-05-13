<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Global network controller') }}</h2>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
        Bulk jobs enqueue per-router work and write to each ISP’s <strong>router action log</strong>. “Execute” still only verifies API connectivity today — extend <code class="text-xs">MikroTikService</code> to apply RouterOS payloads when your change window allows.
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">NAS maintenance dispatch</h3>
            <form method="POST" action="{{ route('admin.network.dispatch') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Operation label</label>
                    <input name="operation" required placeholder="config_sync / banner / health_check"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Script name (log reference)</label>
                    <input name="script_name" required maxlength="64" placeholder="pb-global-2026-05-14"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Script / notes payload</label>
                    <textarea name="script_body" required rows="5" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 font-mono text-xs" placeholder="/system script ..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Vendor contains (optional)</label>
                        <input name="vendor" placeholder="MikroTik / Huawei"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                            <input type="checkbox" name="execute" value="1" class="rounded border-gray-300" />
                            Execute mode (API check + logged payload)
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Limit to ISP IDs (comma-separated, optional)</label>
                    <input name="isp_ids_text" type="text" placeholder="1,2,3" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    <p class="text-[10px] text-gray-500 mt-1">Leave blank for all active routers.</p>
                </div>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Queue jobs</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Routers & circuit breaker</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">When consecutive polls fail, the NAS is marked <strong>broken</strong>, reconcile skips it, and a platform announcement is raised. Clear the circuit after the device is repaired.</p>
            <div class="max-h-96 overflow-y-auto text-xs">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-1 pr-2">ISP</th>
                            <th class="py-1 pr-2">Router</th>
                            <th class="py-1 pr-2">Status</th>
                            <th class="py-1 pr-2">Fails</th>
                            <th class="py-1 pr-2">Last error</th>
                            <th class="py-1">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
                        @foreach ($routers as $r)
                            <tr>
                                <td class="py-1 pr-2">{{ $r->isp?->name }}</td>
                                <td class="py-1 pr-2">{{ $r->name }}</td>
                                <td class="py-1 pr-2">
                                    <span class="font-mono">{{ $r->reachability_status ?? '—' }}</span>
                                </td>
                                <td class="py-1 pr-2">{{ (int) ($r->poll_failure_count ?? 0) }}</td>
                                <td class="py-1 pr-2 max-w-[200px] truncate" title="{{ $r->last_poll_error }}">{{ $r->last_poll_error ? \Illuminate\Support\Str::limit($r->last_poll_error, 48) : '—' }}</td>
                                <td class="py-1">
                                    @if (($r->reachability_status ?? '') === 'broken' || (int) ($r->poll_failure_count ?? 0) >= 10)
                                        <form method="POST" action="{{ route('admin.network.routers.reset-circuit', $r) }}" class="inline" onsubmit="return confirm('Clear circuit breaker for this router?');">
                                            @csrf
                                            <button type="submit" class="rounded bg-amber-600 px-2 py-0.5 text-[10px] font-semibold text-white hover:bg-amber-500">Reset circuit</button>
                                        </form>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Aggregated throughput (router metrics)</h3>
            <form method="GET" class="text-sm">
                <select name="traffic_hours" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" onchange="this.form.submit()">
                    @foreach ([6, 12, 24, 48, 72] as $h)
                        <option value="{{ $h }}" @selected($trafficHours == $h)>{{ $h }}h window</option>
                    @endforeach
                </select>
            </form>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Peak = max(rx+tx) across all polled routers per hour — useful for upstream capacity reviews.</p>
        <div class="space-y-2 max-h-64 overflow-y-auto">
            @forelse ($traffic as $row)
                @php $w = $maxPeak > 0 ? min(100, ((float) $row->peak_bps / $maxPeak) * 100) : 0; @endphp
                <div>
                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-300 mb-0.5">
                        <span>{{ $row->bucket }}</span>
                        <span>{{ number_format((float) $row->peak_bps / 1024, 1) }} KB/s peak · avg {{ number_format((float) $row->avg_bps / 1024, 1) }} KB/s</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                        <div class="h-full rounded-full bg-cyan-500" style="width: {{ $w }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No router_metrics rows in this window. Ensure PollRouterHealthJob / telemetry is writing metrics.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">IP pool overlap map</h3>
        @if (count($overlap['overlaps']) === 0)
            <p class="text-sm text-emerald-700 dark:text-emerald-300">No overlapping IPv4 segments detected across pools.</p>
        @else
            <p class="text-sm text-red-700 dark:text-red-300 mb-4">{{ count($overlap['overlaps']) }} overlap pair(s) — resolve ranges before onboarding new tenants.</p>
            <ul class="text-sm space-y-2 text-gray-800 dark:text-gray-200">
                @foreach ($overlap['overlaps'] as $pair)
                    @php $a = $overlap['segments'][$pair['a']]; $b = $overlap['segments'][$pair['b']]; @endphp
                    <li class="border border-red-200 dark:border-red-900/50 rounded-md p-3">
                        <strong>{{ $a['isp_name'] }}</strong> / {{ $a['pool_name'] }} ({{ $a['label'] }})
                        <span class="text-red-600">↔</span>
                        <strong>{{ $b['isp_name'] }}</strong> / {{ $b['pool_name'] }} ({{ $b['label'] }})
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-admin-layout>
