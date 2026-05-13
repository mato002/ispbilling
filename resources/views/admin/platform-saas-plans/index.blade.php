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
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Slug</th>
                    <th class="px-4 py-2">Max customers</th>
                    <th class="px-4 py-2">Monthly</th>
                    <th class="px-4 py-2">Trial</th>
                    <th class="px-4 py-2">Active</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-800 dark:text-gray-100">
                @foreach ($plans as $row)
                    <tr>
                        <td class="px-4 py-2 font-semibold">{{ $row->name }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $row->slug }}</td>
                        <td class="px-4 py-2">{{ $row->max_customers ? number_format($row->max_customers) : '∞' }}</td>
                        <td class="px-4 py-2">KES {{ number_format($row->monthly_price, 0) }}</td>
                        <td class="px-4 py-2">{{ $row->trial_days }}d</td>
                        <td class="px-4 py-2">{{ $row->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <a href="{{ route('admin.platform-saas-plans.edit', $row) }}" class="text-indigo-600 dark:text-indigo-400">Edit</a>
                            <form action="{{ route('admin.platform-saas-plans.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Delete this plan?');">@csrf @method('DELETE')<button type="submit" class="text-red-600 ml-2">Delete</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $plans->links() }}</div>
    </div>
</x-admin-layout>
