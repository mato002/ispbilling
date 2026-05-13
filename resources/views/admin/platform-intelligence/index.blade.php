<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Intelligence & churn') }}</h2>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <p class="text-xs text-gray-500 dark:text-gray-400">Forecast basis</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Trailing {{ $forecast['sample_days'] }} days</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Daily average uses total completed revenue ÷ {{ $forecast['sample_days'] }} (simple baseline; refine with seasonality when you have more history).</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <p class="text-xs text-gray-500 dark:text-gray-400">Projected collections (completed payments)</p>
            <ul class="mt-2 space-y-1 text-sm text-gray-800 dark:text-gray-100">
                <li>30 days: <strong>KES {{ number_format($forecast['d30'], 2) }}</strong></li>
                <li>60 days: <strong>KES {{ number_format($forecast['d60'], 2) }}</strong></li>
                <li>90 days: <strong>KES {{ number_format($forecast['d90'], 2) }}</strong></li>
            </ul>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <p class="text-xs text-gray-500 dark:text-gray-400">Implied daily average</p>
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">KES {{ number_format($forecast['daily_avg'], 2) }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-8">
        <form method="GET" class="flex flex-wrap items-end gap-4 text-sm mb-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Inactive threshold (hotspot sessions)</label>
                <input type="number" name="inactive_days" value="{{ $inactiveDays }}" min="1" max="90" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 w-24" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Expiring within (days)</label>
                <input type="number" name="expire_within" value="{{ $expireWithin }}" min="1" max="90" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 w-24" />
            </div>
            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-white dark:bg-gray-100 dark:text-gray-900">Apply</button>
        </form>

        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Churn risk — active customers without recent hotspot session</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Uses last <code class="text-xs">hotspot_sessions</code> activity; PPPoE-only tenants may appear here until you add PPP session telemetry.</p>
        <div class="overflow-x-auto max-h-72 overflow-y-auto">
            <table class="min-w-full text-xs divide-y divide-gray-200 dark:divide-gray-700">
                <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th class="py-2 pr-2">ISP</th><th class="py-2 pr-2">Customer</th><th class="py-2 pr-2">Last session</th><th class="py-2">Plan</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
                    @foreach ($churnCandidates as $c)
                        @php $sub = $c->subscriptions->first(); @endphp
                        <tr>
                            <td class="py-2 pr-2">{{ $c->isp?->name }}</td>
                            <td class="py-2 pr-2">{{ $c->full_name }}</td>
                            <td class="py-2 pr-2">{{ $c->churn_last_session_at ? \Illuminate\Support\Carbon::parse($c->churn_last_session_at)->format('Y-m-d H:i') : 'Never' }}</td>
                            <td class="py-2">{{ $sub?->plan?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Subscriptions expiring soon</h3>
        <div class="overflow-x-auto max-h-72 overflow-y-auto text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th class="py-2 pr-2">Ends</th><th class="py-2 pr-2">ISP</th><th class="py-2 pr-2">Customer</th><th class="py-2">Plan</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
                    @foreach ($expiringSubscriptions as $s)
                        <tr>
                            <td class="py-2 pr-2 whitespace-nowrap">{{ $s->end_date?->format('Y-m-d') }}</td>
                            <td class="py-2 pr-2">{{ $s->customer?->isp?->name }}</td>
                            <td class="py-2 pr-2">{{ $s->customer?->full_name }}</td>
                            <td class="py-2">{{ $s->plan?->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
