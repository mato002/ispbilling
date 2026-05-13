<x-admin-layout title="Announcements">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-slate-100">{{ __('System announcements') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Broadcast to all ISP dashboards (top banner).</p>
            </div>
            <a href="{{ route('admin.announcements.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">New announcement</a>
        </div>
    </x-slot>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80">
                <tr>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Severity</th>
                    <th class="px-4 py-3">Window</th>
                    <th class="px-4 py-3">Active</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($announcements as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $row->title }}</td>
                        <td class="px-4 py-3 capitalize">{{ $row->severity }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            {{ $row->starts_at?->format('M j, H:i') ?? '—' }}
                            <span class="text-slate-400">→</span>
                            {{ $row->ends_at?->format('M j, H:i') ?? 'open' }}
                        </td>
                        <td class="px-4 py-3">{{ $row->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.announcements.edit', $row) }}" class="font-semibold text-indigo-600 dark:text-indigo-300">Edit</a>
                            <form action="{{ route('admin.announcements.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Delete this announcement?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-semibold text-rose-600 dark:text-rose-400">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-500">No announcements yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $announcements->links() }}</div>
</x-admin-layout>
