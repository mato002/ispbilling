@extends('layouts.admin')

@section('title', 'Payments')

@section('header')
    <x-isp-page-header
        title="Posted payments"
        module="Billing"
        primaryLabel="Unassigned"
        :primaryHref="route('isp.payments.unposted')"
    />
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.payments.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$payments"
        :empty="$payments->isEmpty()"
        empty-title="No payments"
        empty-message="No payments match the current filters."
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Reference, transaction ID, customer…"
            />
            <x-listing-filters.select
                name="payment_method"
                label="Method"
                placeholder="All"
                :options="[
                    'mpesa' => 'M-Pesa',
                    'selcom' => 'Selcom',
                    'manual' => 'Manual',
                    'voucher' => 'Voucher',
                ]"
            />
            <x-listing-filters.date name="date_from" label="From" />
            <x-listing-filters.date name="date_to" label="To" />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Paid at</th>
                        <th>Payment</th>
                        <th>Internet</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <x-listing-table.row :href="route('isp.payments.show', $payment)">
                            <td class="font-mono text-sm font-medium">{{ $payment->payment_reference }}</td>
                            <td>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $payment->customer->full_name ?? 'N/A' }}</div>
                                @if ($payment->customer)
                                    <div class="text-sm text-slate-500">{{ $payment->customer->phone_number }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($payment->invoice)
                                    <a href="{{ route('isp.invoices.show', $payment->invoice) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">#{{ $payment->invoice->id }}</a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="font-mono font-semibold whitespace-nowrap">KES {{ number_format($payment->amount, 2) }}</td>
                            <td><span class="pb-pill pb-pill-info">{{ strtoupper($payment->payment_method) }}</span></td>
                            <td class="whitespace-nowrap text-sm">{{ $payment->processed_at?->format('M j, Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($payment->status === 'completed')
                                    <x-status-badge status="paid" />
                                @elseif ($payment->status === 'pending')
                                    <x-status-badge status="pending" />
                                @else
                                    <span class="pb-pill pb-pill-bad">Failed</span>
                                @endif
                            </td>
                            <td>
                                @php $isActive = $payment->customer && $payment->customer->isActive(); @endphp
                                @if ($isActive)
                                    <span class="pb-pill pb-pill-ok">Active</span>
                                @elseif ($payment->status === 'completed')
                                    <span class="pb-pill pb-pill-warn">Pending</span>
                                @else
                                    <span class="pb-pill">N/A</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <x-table-actions width="w-52">
                                    <x-table-actions.link :href="route('isp.payments.show', $payment)" label="View" />
                                    @if ($payment->payment_method === 'manual' && $payment->status === 'completed' && $payment->customer && ! $payment->customer->isActive())
                                        <x-table-actions.form
                                            :action="route('isp.payments.activate-internet', $payment)"
                                            label="Activate internet"
                                            :danger="false"
                                        />
                                    @endif
                                </x-table-actions>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
