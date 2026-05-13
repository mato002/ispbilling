<x-admin-layout title="ISP helpdesk">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-slate-100">
            {{ __('ISP helpdesk') }}
        </h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Platform tickets opened by ISP administrators.</p>
    </x-slot>

    <div class="space-y-6">
        <form method="GET" action="{{ route('admin.helpdesk.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                <select name="status" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                    <option value="">All</option>
                    @foreach (['open', 'in_progress', 'resolved', 'closed'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ str_replace('_', ' ', $st) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">ISP</label>
                <select name="isp_id" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                    <option value="">All</option>
                    @foreach ($isps as $isp)
                        <option value="{{ $isp->id }}" @selected((string) request('isp_id') === (string) $isp->id)>{{ $isp->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button>
            <a href="{{ route('admin.helpdesk.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-600 dark:text-slate-200">Reset</a>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/80">
                    <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">ISP</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">From</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">#{{ $ticket->id }}</td>
                            <td class="px-4 py-3">{{ $ticket->isp->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ \Illuminate\Support\Str::limit($ticket->subject, 60) }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ optional($ticket->createdBy)->name ?? '—' }}</td>
                            <td class="px-4 py-3 capitalize">{{ $ticket->priority }}</td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $ticket->status) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.helpdesk.show', $ticket) }}" class="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-300">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">No platform tickets.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pb-card p-4">{{ $tickets->links() }}</div>
    </div>
</x-admin-layout>
