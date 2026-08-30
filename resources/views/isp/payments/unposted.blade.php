@extends('layouts.admin')

@section('title', 'Unassigned payments')

@section('header')
    <x-isp-page-header
        title="Unassigned payments"
        module="Billing"
        primaryLabel="Posted payments"
        :primaryHref="route('isp.payments.index')"
    />
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.payments.unposted')"
        :paginator="$payments"
        :empty="$payments->isEmpty()"
        empty-title="No unassigned payments"
        empty-message="All received funds are posted to customer accounts."
    >
        <x-slot:filters>
            <x-listing-filters.search placeholder="M-Pesa ref, phone, reference…" />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Received</th>
                        <th>M-Pesa ref</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Assign to customer</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        @php
                            $payload = $payment->metadata['c2b_payload'] ?? [];
                            $reason = $payment->metadata['unreconciled_reason'] ?? 'Unmatched paybill payment';
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap text-sm">{{ $payment->processed_at?->format('M j, Y H:i') ?? $payment->created_at->format('M j, Y H:i') }}</td>
                            <td class="font-mono text-sm">{{ $payment->transaction_id ?? '—' }}</td>
                            <td class="text-sm">{{ $payment->phone_number ?? ($payload['MSISDN'] ?? '—') }}</td>
                            <td class="font-mono font-semibold">KES {{ number_format($payment->amount, 2) }}</td>
                            <td class="max-w-xs truncate text-sm text-slate-600 dark:text-slate-400" title="{{ $reason }}">{{ $reason }}</td>
                            <td>
                                <form method="POST" action="{{ route('isp.payments.assign', $payment) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    <select name="customer_id" required class="isp-form-select min-w-[160px] text-xs">
                                        <option value="">Select customer…</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->full_name }} · {{ $customer->phone_number }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="pb-btn pb-btn-primary text-xs">Post payment</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
