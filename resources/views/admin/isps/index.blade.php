<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('All ISPs') }}
            </h2>
            <x-form-modal-link :href="route('admin.isps.create')" class="px-4 py-2">
                Add ISP
            </x-form-modal-link>
        </div>
    </x-slot>

    <x-resource-index
        :filter-action="route('admin.isps.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$isps"
        :empty="$isps->isEmpty()"
        empty-title="No ISPs found"
        empty-message="Create an ISP or adjust your search filters."
        :empty-cta-href="route('admin.isps.create')"
        empty-cta-label="Add ISP"
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Name, company, email, phone…"
                data-pb-auto-search
            />
            <x-listing-filters.select
                name="is_active"
                label="Status"
                placeholder="All status"
                :options="['1' => 'Active', '0' => 'Inactive']"
            />
            <x-listing-filters.select
                name="saas_status"
                label="SaaS billing"
                placeholder="All SaaS states"
                class="min-w-[150px]"
                :options="[
                    'none' => 'None',
                    'active' => 'Active',
                    'trialing' => 'Trialing',
                    'past_due' => 'Past due',
                    'cancelled' => 'Cancelled',
                    'suspended' => 'Suspended',
                ]"
            />
            <x-listing-filters.select
                name="onboarding"
                label="Onboarding"
                placeholder="All onboarding"
                class="min-w-[150px]"
                :options="[
                    'complete' => 'Complete',
                    'pending' => 'Pending',
                ]"
            />
            <x-listing-filters.date name="created_from" label="Created from" />
            <x-listing-filters.date name="created_to" label="Created to" />
            <x-listing-filters.select
                name="sort"
                label="Sort by"
                class="min-w-[150px]"
                :value="request('sort', 'latest')"
                :options="[
                    'latest' => 'Newest first',
                    'oldest' => 'Oldest first',
                    'name_az' => 'Name A–Z',
                    'name_za' => 'Name Z–A',
                    'customers_desc' => 'Most customers',
                ]"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-listing-table>
                <thead>
                    <tr>
                        <th>ISP Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Customers</th>
                        <th>Routers</th>
                        <th>Plans</th>
                        <th>Status</th>
                        <th>SaaS</th>
                        <th>Created</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($isps as $isp)
                        <x-listing-table.row :href="route('admin.isps.show', $isp->id)">
                            <td class="font-medium text-gray-900 dark:text-gray-100">{{ $isp->name }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $isp->company_name }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $isp->email }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ number_format($isp->customers_count) }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ number_format($isp->routers_count) }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ number_format($isp->plans_count) }}</td>
                            <td>
                                @if ($isp->is_active)
                                    <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-800 dark:text-green-100">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800 dark:bg-red-800 dark:text-red-100">Inactive</span>
                                @endif
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', ucfirst($isp->saas_subscription_status ?? 'none')) }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $isp->created_at->format('M d, Y') }}</td>
                            <td class="text-right">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('admin.isps.show', $isp->id)" label="View" />
                                    <x-table-actions.link :href="route('admin.isps.edit', $isp->id)" label="Edit" />
                                    <x-table-actions.form
                                        :action="route('admin.isps.destroy', $isp->id)"
                                        method="DELETE"
                                        label="Delete"
                                        onsubmit="confirmFormSubmit(event, {title: 'Delete ISP', text: 'Are you sure you want to delete {{ $isp->name }}? This action cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, delete', confirmButtonColor: '#ef4444'}); return false;"
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
