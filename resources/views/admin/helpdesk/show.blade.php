<x-admin-layout title="Helpdesk #{{ $ticket->id }}">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-slate-100">
            Helpdesk #{{ $ticket->id }} — {{ $ticket->isp->name ?? 'ISP' }}
        </h2>
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.helpdesk.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-300">← Back to queue</a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Thread</h3>
                <div class="mt-4 space-y-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <div class="flex gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-xs font-bold text-white">
                            {{ strtoupper(substr(optional($ticket->createdBy)->name ?? 'U', 0, 1)) }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                {{ optional($ticket->createdBy)->name }}
                                <span class="text-xs font-normal text-slate-400">· {{ $ticket->created_at->format('Y-m-d H:i') }}</span>
                            </p>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ $ticket->message }}</p>
                        </div>
                    </div>
                    @foreach ($ticket->replies as $reply)
                        <div class="flex gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-600 text-xs font-bold text-white">
                                {{ strtoupper(substr(optional($reply->user)->name ?? 'R', 0, 1)) }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ optional($reply->user)->name ?? 'User' }}
                                    <span class="text-xs font-normal text-slate-400">· {{ $reply->created_at->format('Y-m-d H:i') }}</span>
                                </p>
                                <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ $reply->message }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Reply to ISP</h3>
                <form method="POST" action="{{ route('admin.helpdesk.reply', $ticket) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="message" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950" required placeholder="Your response…">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Send reply</button>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Update</h3>
                <form method="POST" action="{{ route('admin.helpdesk.update', $ticket) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Status</label>
                        <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950">
                            @foreach (['open', 'in_progress', 'resolved', 'closed'] as $st)
                                <option value="{{ $st }}" @selected($ticket->status === $st)>{{ str_replace('_', ' ', $st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Priority</label>
                        <select name="priority" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950">
                            @foreach (['low', 'normal', 'high'] as $pr)
                                <option value="{{ $pr }}" @selected($ticket->priority === $pr)>{{ ucfirst($pr) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Assign to user ID</label>
                        <input type="number" name="assigned_to_user_id" value="{{ old('assigned_to_user_id', $ticket->assigned_to_user_id) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950" placeholder="Optional">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500">Save</button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
