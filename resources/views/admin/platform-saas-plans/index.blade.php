<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Platform SaaS plans</h2>
            <a href="{{ route('admin.platform-saas-plans.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Create plan</a>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-900/30 dark:text-rose-200">{{ session('error') }}</div>
    @endif

    <x-resource-index
        :filter-action="route('admin.platform-saas-plans.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$plans"
        :empty="$plans->isEmpty()"
        empty-title="No SaaS plans"
        empty-message="Define platform tiers ISPs subscribe to."
        empty-cta-label="Create plan"
        :empty-cta-href="route('admin.platform-saas-plans.create')"
        :show-flash="false"
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Name or slug…"
            />
            <x-listing-filters.select
                name="is_active"
                label="Active"
                placeholder="All"
                :options="['1' => 'Yes', '0' => 'No']"
            />
            <x-listing-filters.per-page :default="30" />
        </x-slot:filters>

        <x-slot:table>
            <x-listing-table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Max customers</th>
                        <th>Monthly</th>
                        <th>Trial</th>
                        <th>Active</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plans as $row)
                        <x-listing-table.row :href="route('admin.platform-saas-plans.show', $row)">
                            <td class="font-semibold">{{ $row->name }}</td>
                            <td class="font-mono text-xs">{{ $row->slug }}</td>
                            <td>{{ $row->max_customers ? number_format($row->max_customers) : '∞' }}</td>
                            <td>KES {{ number_format($row->monthly_price, 0) }}</td>
                            <td>{{ $row->trial_days }}d</td>
                            <td>{{ $row->is_active ? 'Yes' : 'No' }}</td>
                            <td class="whitespace-nowrap text-right">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('admin.platform-saas-plans.show', $row)" label="View" />
                                    <x-table-actions.link :href="route('admin.platform-saas-plans.edit', $row)" label="Edit" />
                                    <x-table-actions.form
                                        :action="route('admin.platform-saas-plans.destroy', $row)"
                                        method="DELETE"
                                        label="Delete"
                                        onsubmit="return confirm('Delete this plan?');"
                                    />
                                </x-table-actions>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-listing-table>
        </x-slot:table>
    </x-resource-index>
</x-admin-layout>
