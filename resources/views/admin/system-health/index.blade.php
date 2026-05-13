<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Fleet health (all routers)') }}</h2>
    </x-slot>

    <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">
        Live reachability comes from <code class="text-xs">PollRouterHealthJob</code> (scheduled every minute). Use this page for a cross-tenant NOC view; ISP admins still manage routers under their own workspace.
    </div>

    <div class="mb-6 flex flex-wrap gap-3 text-sm">
        @foreach ($summary as $label => $count)
            <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-3 py-1 dark:border-gray-700 dark:bg-gray-800">
                <span class="font-medium text-gray-600 dark:text-gray-300">{{ $label ?: 'unknown' }}</span>
                <span class="tabular-nums text-gray-900 dark:text-gray-100">{{ $count }}</span>
            </span>
        @endforeach
    </div>

    <div class="mb-4">
        <form method="get" class="flex flex-wrap items-center gap-2 text-sm">
            <label class="text-gray-600 dark:text-gray-400">Filter</label>
            <select name="status" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" onchange="this.form.submit()">
                <option value="">{{ __('All statuses') }}</option>
                @foreach ($summary->keys() as $key)
                    <option value="{{ $key }}" @selected($statusFilter === (string) $key)>{{ $key ?: 'unknown' }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">ISP</th>
                    <th class="px-4 py-3">Router</th>
                    <th class="px-4 py-3">Address</th>
                    <th class="px-4 py-3">Active</th>
                    <th class="px-4 py-3">Reachability</th>
                    <th class="px-4 py-3">Last seen</th>
                    <th class="px-4 py-3">Latency</th>
                    <th class="px-4 py-3">Poll error</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($routers as $r)
                    <tr class="text-gray-800 dark:text-gray-200">
                        <td class="px-4 py-2">{{ $r->isp?->name ?? '—' }}</td>
                        <td class="px-4 py-2 font-medium">{{ $r->name }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $r->ip_address }}</td>
                        <td class="px-4 py-2">{{ $r->is_active ? __('Yes') : __('No') }}</td>
                        <td class="px-4 py-2">{{ $r->reachability_status ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ optional($r->last_seen_at)->diffForHumans() ?? '—' }}</td>
                        <td class="px-4 py-2 tabular-nums">{{ $r->last_latency_ms !== null ? $r->last_latency_ms.' ms' : '—' }}</td>
                        <td class="max-w-xs truncate px-4 py-2 text-xs text-amber-800 dark:text-amber-200" title="{{ $r->last_poll_error }}">{{ $r->last_poll_error ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No routers found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $routers->links() }}
    </div>
</x-admin-layout>
