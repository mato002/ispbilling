@forelse ($isps as $isp)
    <x-listing-table.row
        :href="route('admin.isps.show', $isp->id)"
        :data-search="strtolower(implode(' ', array_filter([$isp->name, $isp->company_name, $isp->email, $isp->phone, $isp->slug])))"
    >
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
                <x-table-actions.form :action="route('admin.isps.workspace.enter', $isp->id)" label="Log in as ISP admin" :danger="false" />
                <x-table-actions.form
                    :action="route('admin.isps.destroy', $isp->id)"
                    method="DELETE"
                    label="Delete"
                    onsubmit="confirmFormSubmit(event, {title: 'Delete ISP', text: 'Are you sure you want to delete {{ $isp->name }}? This action cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, delete', confirmButtonColor: '#ef4444'}); return false;"
                />
            </x-table-actions>
        </td>
    </x-listing-table.row>
@empty
    <tr>
        <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500">No ISPs match your search.</td>
    </tr>
@endforelse
