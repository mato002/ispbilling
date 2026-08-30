<x-admin-layout title="Edit SaaS plan">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit SaaS plan</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $plan->name }} · <span class="font-mono text-xs">{{ $plan->slug }}</span></p>
            </div>
            <a href="{{ route('admin.platform-saas-plans.index') }}" class="pb-btn pb-btn-secondary">{{ __('Back to plans') }}</a>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-5xl">
        <form method="POST" action="{{ route('admin.platform-saas-plans.update', $plan) }}">
            @csrf
            @method('PUT')
            @include('admin.platform-saas-plans._form', [
                'plan' => $plan,
                'cancelUrl' => route('admin.platform-saas-plans.index'),
            ])
        </form>
    </div>
</x-admin-layout>
