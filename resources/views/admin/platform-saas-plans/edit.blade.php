<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit SaaS plan</h2>
    </x-slot>
    <div class="max-w-2xl bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('admin.platform-saas-plans.update', $plan) }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="name" value="{{ old('name', $plan->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $plan->slug) }}" required pattern="[a-z0-9\-]+" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                @error('slug')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $plan->description) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max customers (empty = unlimited)</label>
                    <input type="number" name="max_customers" value="{{ old('max_customers', $plan->max_customers) }}" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sort order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monthly price (KES)</label>
                    <input type="number" step="0.01" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price) }}" required min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Setup fee (KES)</label>
                    <input type="number" step="0.01" name="setup_fee" value="{{ old('setup_fee', $plan->setup_fee) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Trial days</label>
                <input type="number" name="trial_days" value="{{ old('trial_days', $plan->trial_days) }}" required min="0" max="90" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            </div>
            @php
                $f = $plan->features ?? [];
            @endphp
            <div class="flex gap-6">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="advanced_analytics" value="1" {{ old('advanced_analytics', !empty($f['advanced_analytics'])) ? 'checked' : '' }}> Advanced analytics
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="sms_automation" value="1" {{ old('sms_automation', !empty($f['sms_automation'])) ? 'checked' : '' }}> SMS automation
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}> Active
                </label>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Update</button>
                <a href="{{ route('admin.platform-saas-plans.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg text-sm">Cancel</a>
            </div>
        </form>
    </div>
</x-admin-layout>
