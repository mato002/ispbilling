<x-admin-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ __('Create promo code') }}</h2></x-slot>
    <form method="POST" action="{{ route('admin.promo-codes.store') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 max-w-xl space-y-4 text-sm">
        @csrf
        <div><label class="text-xs text-gray-500">Code (optional auto)</label><input name="code" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
        <div><label class="text-xs text-gray-500">Kind</label>
            <select name="kind" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="first_month_free">First month free</option>
                <option value="referral">Referral</option>
                <option value="percent_off">Percent off</option>
            </select>
        </div>
        <div><label class="text-xs text-gray-500">Title</label><input name="title" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
        <div><label class="text-xs text-gray-500">Percent off (for percent_off)</label><input type="number" step="0.01" name="percent_off" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
        <div><label class="text-xs text-gray-500">Max redemptions</label><input type="number" name="max_redemptions" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs text-gray-500">Starts</label><input type="datetime-local" name="starts_at" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
            <div><label class="text-xs text-gray-500">Ends</label><input type="datetime-local" name="ends_at" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
        </div>
        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" checked /> Active</label>
        <button class="rounded-md bg-indigo-600 px-4 py-2 text-white">Save</button>
    </form>
</x-admin-layout>
