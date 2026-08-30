<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Hotspot portal settings') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl">
        <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
            Portal branding is configured per ISP tenant. Select a workspace to continue.
        </p>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse ($isps as $isp)
                    <li>
                        <a
                            href="{{ route('admin.portal-settings.index', ['isp_id' => $isp->id]) }}"
                            class="flex items-center justify-between gap-4 px-4 py-3 text-sm transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
                        >
                            <span>
                                <span class="block font-semibold text-slate-900 dark:text-white">
                                    {{ $isp->company_name ?: $isp->name }}
                                </span>
                                @if ($isp->company_name && $isp->name !== $isp->company_name)
                                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $isp->name }}</span>
                                @endif
                            </span>
                            <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-300">Configure →</span>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        No ISP tenants found yet.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</x-admin-layout>
