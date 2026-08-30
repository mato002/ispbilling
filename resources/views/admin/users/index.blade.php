<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('All Users') }}
            </h2>
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Add User
            </a>
        </div>
    </x-slot>

    <x-resource-index
        :filter-action="route('admin.users.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$users"
        :empty="$users->isEmpty()"
        empty-title="No users found"
        empty-message="Create a user or adjust your search filters."
        :empty-cta-href="route('admin.users.create')"
        empty-cta-label="Add User"
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Name, email, or phone…"
            />
            <x-listing-filters.select
                name="role"
                label="Role"
                placeholder="All roles"
                :options="[
                    'super_admin' => 'Super Admin',
                    'isp_admin' => 'ISP Admin',
                    'isp_staff' => 'Staff',
                ]"
            />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All status"
                :options="[
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-listing-table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>ISP</th>
                        <th>Status</th>
                        <th>Created</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <x-listing-table.row :href="route('admin.users.show', $user->id)">
                            <td class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $user->phone ?: '-' }}</td>
                            <td>
                                @switch($user->role)
                                    @case('super_admin')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">Super Admin</span>
                                        @break
                                    @case('isp_admin')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">ISP Admin</span>
                                        @break
                                    @case('isp_staff')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Staff</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $user->isp ? $user->isp->name : '-' }}</td>
                            <td>
                                @if ($user->is_active)
                                    <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-800 dark:text-green-100">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800 dark:bg-red-800 dark:text-red-100">Inactive</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('admin.users.show', $user->id)" label="View" />
                                    <x-table-actions.link :href="route('admin.users.edit', $user->id)" label="Edit" />
                                    <x-table-actions.form
                                        :action="route('admin.users.destroy', $user->id)"
                                        method="DELETE"
                                        label="Delete"
                                        onsubmit="confirmFormSubmit(event, {title: 'Delete User', text: 'Are you sure you want to delete {{ $user->name }}? This action cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, delete', confirmButtonColor: '#ef4444'}); return false;"
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
