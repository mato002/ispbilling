<x-admin-layout title="ISP helpdesk">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-slate-100">
            {{ __('ISP helpdesk') }}
        </h2>
    </x-slot>

    <x-resource-index
        :filter-action="route('admin.helpdesk.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$tickets"
        :empty="$tickets->isEmpty()"
        empty-title="No platform tickets"
        empty-message="ISP admins open tickets from their dashboard helpdesk."
    >
        <x-slot:filters>
            <x-listing-filters.search
                name="q"
                placeholder="Subject, body…"
            />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All"
                :options="[
                    'open' => 'Open',
                    'in_progress' => 'In progress',
                    'resolved' => 'Resolved',
                    'closed' => 'Closed',
                ]"
            />
            <x-listing-filters.select
                name="isp_id"
                label="ISP"
                placeholder="All"
                class="min-w-[180px]"
                :options="$isps->pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()"
            />
            <x-listing-filters.per-page :default="25" />
        </x-slot:filters>

        <x-slot:table>
            <x-listing-table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ISP</th>
                        <th>Subject</th>
                        <th>From</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <x-listing-table.row :href="route('admin.helpdesk.show', $ticket)">
                            <td class="font-mono text-xs">#{{ $ticket->id }}</td>
                            <td>{{ $ticket->isp->name ?? '—' }}</td>
                            <td class="font-medium text-slate-900 dark:text-slate-100">{{ \Illuminate\Support\Str::limit($ticket->subject, 60) }}</td>
                            <td class="text-slate-600 dark:text-slate-300">{{ optional($ticket->createdBy)->name ?? '—' }}</td>
                            <td class="capitalize">{{ $ticket->priority }}</td>
                            <td class="capitalize">{{ str_replace('_', ' ', $ticket->status) }}</td>
                            <td class="text-right">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('admin.helpdesk.show', $ticket)" label="Open" />
                                </x-table-actions>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-listing-table>
        </x-slot:table>
    </x-resource-index>
</x-admin-layout>
