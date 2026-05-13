<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    PrimeBilling SaaS onboarding · {{ $isp->name }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Same steps as self-service registration: plan, company billing profile, payment method, and optional white-label. Data is saved on the ISP record.
                </p>
            </div>
            <a href="{{ route('admin.isps.show', $isp) }}" class="inline-flex justify-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-sm font-medium">
                Back to ISP
            </a>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('admin.isps.saas-onboarding.store', $isp) }}" class="space-y-8">
                @csrf
                @include('isp.saas-onboarding.partials.onboarding-form-legacy', [
                    'isp' => $isp,
                    'plans' => $plans,
                    'submitLabel' => 'Save onboarding',
                ])
            </form>
        </div>
    </div>
</x-admin-layout>
