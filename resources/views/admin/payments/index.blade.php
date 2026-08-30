<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('All Payments') }}
        </h2>
    </x-slot>

    <x-resource-index
        :filter-action="route('admin.payments.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$payments"
        :empty="$payments->isEmpty()"
        empty-title="No payments found"
        empty-message="Adjust your filters to find payments across ISPs."
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Reference, customer, or phone…"
            />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All statuses"
                :options="[
                    'completed' => 'Completed',
                    'pending' => 'Pending',
                    'failed' => 'Failed',
                ]"
            />
            <x-listing-filters.select
                name="payment_method"
                label="Method"
                placeholder="All methods"
                :options="[
                    'mpesa' => 'MPesa',
                    'selcom' => 'Selcom',
                    'manual' => 'Manual',
                ]"
            />
            <x-listing-filters.select
                name="isp_id"
                label="ISP"
                placeholder="All ISPs"
                class="min-w-[180px]"
                :options="$isps->pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()"
            />
            <x-listing-filters.per-page :default="20" />
        </x-slot:filters>

        <x-slot:table>
            <x-listing-table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>ISP</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td class="font-medium text-gray-900 dark:text-gray-100">{{ $payment->payment_reference }}</td>
                            <td>
                                <div class="text-gray-900 dark:text-gray-100">{{ $payment->customer->full_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->customer->phone_number }}</div>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $payment->isp->name }}</td>
                            <td class="font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $payment->isp->currency }} {{ number_format($payment->amount, 2) }}
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ strtoupper($payment->payment_method) }}
                                </span>
                            </td>
                            <td>
                                @if ($payment->status === 'completed')
                                    <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-800 dark:text-green-100">Completed</span>
                                @elseif ($payment->status === 'pending')
                                    <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">Pending</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800 dark:bg-red-800 dark:text-red-100">Failed</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-listing-table>
        </x-slot:table>
    </x-resource-index>
</x-admin-layout>
