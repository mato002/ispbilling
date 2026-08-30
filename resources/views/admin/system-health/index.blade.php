<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Fleet health (all routers)') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">
            Live reachability comes from <code class="text-xs">PollRouterHealthJob</code> (scheduled every minute). Use this page for a cross-tenant NOC view; ISP admins still manage routers under their own workspace.
        </div>

        <div class="flex flex-wrap gap-3 text-sm">
            @foreach ($summary as $label => $count)
                <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-3 py-1 dark:border-gray-700 dark:bg-gray-800">
                    <span class="font-medium text-gray-600 dark:text-gray-300">{{ $label ?: 'unknown' }}</span>
                    <span class="tabular-nums text-gray-900 dark:text-gray-100">{{ $count }}</span>
                </span>
            @endforeach
        </div>

        <x-resource-index
            :filter-action="route('admin.system-health.index')"
            :filter-chips="$filterChips ?? []"
            :export-formats="$exportFormats ?? []"
            :paginator="$routers"
            :empty="$routers->isEmpty()"
            empty-title="No routers found"
            empty-message="No routers match the current reachability filter."
        >
            <x-slot:filters>
                <x-listing-filters.search
                    placeholder="ISP, router, or IP address…"
                />
                <x-listing-filters.select
                    name="status"
                    label="Reachability"
                    placeholder="All statuses"
                    class="min-w-[160px]"
                    :options="collect($summary->keys())->mapWithKeys(fn ($key) => [(string) $key => ($key !== '' ? (string) $key : 'unknown')])->all()"
                />
                <x-listing-filters.per-page :default="40" />
            </x-slot:filters>

            <x-slot:table>
                <x-listing-table>
                    <thead>
                        <tr>
                            <th>ISP</th>
                            <th>Router</th>
                            <th>Address</th>
                            <th>Active</th>
                            <th>Reachability</th>
                            <th>Last seen</th>
                            <th>Latency</th>
                            <th>Poll error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($routers as $r)
                            <tr>
                                <td>{{ $r->isp?->name ?? '—' }}</td>
                                <td class="font-medium">{{ $r->name }}</td>
                                <td class="font-mono text-xs">{{ $r->ip_address }}</td>
                                <td>{{ $r->is_active ? __('Yes') : __('No') }}</td>
                                <td>{{ $r->reachability_status ?? '—' }}</td>
                                <td class="text-xs whitespace-nowrap">{{ optional($r->last_seen_at)->diffForHumans() ?? '—' }}</td>
                                <td class="tabular-nums">{{ $r->last_latency_ms !== null ? $r->last_latency_ms.' ms' : '—' }}</td>
                                <td class="max-w-xs truncate text-xs text-amber-800 dark:text-amber-200" title="{{ $r->last_poll_error }}">{{ $r->last_poll_error ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-listing-table>
            </x-slot:table>
        </x-resource-index>
    </div>
</x-admin-layout>
