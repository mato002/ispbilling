<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Global promo codes') }}</h2>
            <a href="{{ route('admin.promo-codes.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Create</a>
        </div>
    </x-slot>
    @if (session('success'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-300"><tr><th class="px-4 py-2">Code</th><th class="px-4 py-2">Kind</th><th class="px-4 py-2">Active</th><th class="px-4 py-2">Redemptions</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-800 dark:text-gray-100">
                @foreach ($codes as $row)
                    <tr>
                        <td class="px-4 py-2 font-mono font-semibold">{{ $row->code }}</td>
                        <td class="px-4 py-2">{{ $row->kind }}</td>
                        <td class="px-4 py-2">{{ $row->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2">{{ $row->redemption_count }}@if($row->max_redemptions) / {{ $row->max_redemptions }} @endif</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <a href="{{ route('admin.promo-codes.edit', $row) }}" class="text-indigo-600 dark:text-indigo-400">Edit</a>
                            <form action="{{ route('admin.promo-codes.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Delete this code?');">@csrf @method('DELETE')<button type="submit" class="text-red-600 ml-2">Delete</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $codes->links() }}</div>
    </div>
</x-admin-layout>
