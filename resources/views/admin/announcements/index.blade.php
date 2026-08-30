<x-admin-layout title="Announcements">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-slate-100">{{ __('System announcements') }}</h2>
            </div>
            <a href="{{ route('admin.announcements.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">New announcement</a>
        </div>
    </x-slot>

    <x-resource-index
        :filter-action="route('admin.announcements.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$announcements"
        :empty="$announcements->isEmpty()"
        empty-title="No announcements yet"
        empty-message="Create a banner to show on all ISP dashboards."
        empty-cta-label="New announcement"
        :empty-cta-href="route('admin.announcements.create')"
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Title or body…"
            />
            <x-listing-filters.select
                name="severity"
                label="Severity"
                placeholder="All"
                :options="[
                    'info' => 'Info',
                    'warning' => 'Warning',
                    'critical' => 'Critical',
                ]"
            />
            <x-listing-filters.select
                name="is_active"
                label="Active"
                placeholder="All"
                :options="['1' => 'Yes', '0' => 'No']"
            />
            <x-listing-filters.per-page :default="20" />
        </x-slot:filters>

        <x-slot:table>
            <x-listing-table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Window</th>
                        <th>Active</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($announcements as $row)
                        <tr>
                            <td class="font-medium text-slate-900 dark:text-slate-100">{{ $row->title }}</td>
                            <td class="capitalize">{{ $row->severity }}</td>
                            <td class="text-slate-600 dark:text-slate-300">
                                {{ $row->starts_at?->format('M j, H:i') ?? '—' }}
                                <span class="text-slate-400">→</span>
                                {{ $row->ends_at?->format('M j, H:i') ?? 'open' }}
                            </td>
                            <td>{{ $row->is_active ? 'Yes' : 'No' }}</td>
                            <td class="text-right">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('admin.announcements.edit', $row)" label="Edit" />
                                    <x-table-actions.form
                                        :action="route('admin.announcements.destroy', $row)"
                                        method="DELETE"
                                        label="Delete"
                                        onsubmit="return confirm('Delete this announcement?');"
                                    />
                                </x-table-actions>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-listing-table>
        </x-slot:table>
    </x-resource-index>
</x-admin-layout>
