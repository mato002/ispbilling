<x-admin-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ __('Edit promo') }} {{ $code->code }}</h2></x-slot>
    <form method="POST" action="{{ route('admin.promo-codes.update', $code) }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 max-w-xl space-y-4 text-sm">
        @csrf @method('PUT')
        <div><label class="text-xs text-gray-500">Code</label><input name="code" value="{{ old('code', $code->code) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 font-mono" /></div>
        <div><label class="text-xs text-gray-500">Kind</label>
            <select name="kind" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['first_month_free' => 'First month free', 'referral' => 'Referral', 'percent_off' => 'Percent off'] as $k => $label)
                    <option value="{{ $k }}" @selected(old('kind', $code->kind) === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="text-xs text-gray-500">Title</label><input name="title" value="{{ old('title', $code->title) }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
        <div><label class="text-xs text-gray-500">Percent off</label><input type="number" step="0.01" name="percent_off" value="{{ old('percent_off', $code->percent_off) }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
        <div><label class="text-xs text-gray-500">Max redemptions</label><input type="number" name="max_redemptions" value="{{ old('max_redemptions', $code->max_redemptions) }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs text-gray-500">Starts</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($code->starts_at)->format('Y-m-d\TH:i')) }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
            <div><label class="text-xs text-gray-500">Ends</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($code->ends_at)->format('Y-m-d\TH:i')) }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" /></div>
        </div>
        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $code->is_active)) /> Active</label>
        <button class="rounded-md bg-indigo-600 px-4 py-2 text-white">Update</button>
    </form>
</x-admin-layout>
