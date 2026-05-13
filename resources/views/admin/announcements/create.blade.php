<x-admin-layout title="New announcement">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-slate-100">{{ __('New announcement') }}</h2>
    </x-slot>

    <div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        @include('admin.announcements._form', ['announcement' => null])
    </div>
</x-admin-layout>
