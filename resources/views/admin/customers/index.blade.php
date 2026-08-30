<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('All Customers') }}
            </h2>
        </div>
    </x-slot>

    <x-resource-index
        :filter-action="route('admin.customers.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$customers"
        :empty="$customers->isEmpty()"
        empty-title="No customers found"
        empty-message="Adjust your search filters to find customers across ISPs."
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Name, phone, or username…"
            />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All statuses"
                :options="[
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                    'expired' => 'Expired',
                ]"
            />
            <x-listing-filters.select
                name="isp_id"
                label="ISP"
                placeholder="All ISPs"
                class="min-w-[180px]"
                :options="$isps->pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()"
            />
            <x-listing-filters.per-page :default="20" />
        </x-slot:filters>

        <x-slot:table>
            <x-listing-table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>ISP</th>
                        <th>Username</th>
                        <th>Connection</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $customer->full_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $customer->phone_number }}</div>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $customer->isp->name }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $customer->username }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                    {{ strtoupper($customer->connection_type) }}
                                </span>
                            </td>
                            <td>
                                @if ($customer->status === 'active')
                                    <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-800 dark:text-green-100">Active</span>
                                @elseif ($customer->status === 'suspended')
                                    <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">Suspended</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800 dark:bg-red-800 dark:text-red-100">{{ ucfirst($customer->status) }}</span>
                                @endif
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $customer->location }}</td>
                            <td class="whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $customer->created_at->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-listing-table>
        </x-slot:table>
    </x-resource-index>
</x-admin-layout>
