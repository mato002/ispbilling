@extends('layouts.admin')

@section('title', 'Failed & pending payments')

@section('header')
    <x-isp-page-header
        title="Failed & pending"
        module="Billing"
        primaryLabel="Webhook logs"
        :primaryHref="route('isp.payments.transaction-logs')"
    >
        <x-slot:actions>
            <x-export-dropdown :formats="$exportFormats ?? []" />
        </x-slot:actions>
    </x-isp-page-header>
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.payments.failed')"
        :paginator="$payments"
        :empty="$payments->isEmpty()"
        empty-title="No failed or pending payments"
        empty-message="All recent payment attempts completed successfully."
    >
        <x-slot:filters>
            <x-listing-filters.search placeholder="Reference, transaction ID…" />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All"
                :options="['pending' => 'Pending', 'failed' => 'Failed']"
            />
            <x-listing-filters.select
                name="connection_type"
                label="Service"
                placeholder="All"
                :options="['hotspot' => 'Hotspot', 'pppoe' => 'PPPoE']"
            />
            <x-listing-filters.date name="date_from" label="Attempt from" />
            <x-listing-filters.date name="date_to" label="Attempt to" />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Created</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <x-listing-table.row :href="route('isp.payments.show', $payment)">
                            <td class="font-mono text-sm">{{ $payment->payment_reference }}</td>
                            <td>{{ $payment->customer?->full_name ?? '—' }}</td>
                            <td><span class="pb-pill {{ $payment->serviceTypeLabel() === 'Hotspot' ? 'pb-pill-cyan' : 'pb-pill-info' }}">{{ $payment->serviceTypeLabel() }}</span></td>
                            <td class="font-mono font-semibold">KES {{ number_format($payment->amount, 2) }}</td>
                            <td><span class="pb-pill pb-pill-info">{{ strtoupper($payment->payment_method) }}</span></td>
                            <td>
                                @if ($payment->status === 'pending')
                                    <x-status-badge status="pending" />
                                @else
                                    <span class="pb-pill pb-pill-bad">Failed</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-sm">{{ $payment->created_at->format('M j, Y H:i') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <x-table-actions width="w-48">
                                    <x-table-actions.link :href="route('isp.payments.show', $payment)" label="View" />
                                    @if ($payment->status === 'completed')
                                        <x-table-actions.form
                                            :action="route('isp.payments.force-sync', $payment)"
                                            label="Force sync"
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
