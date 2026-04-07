@extends('customer.layout')

@section('title', 'Invoices')

@section('content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">My invoices</h2>

    <div class="bg-white rounded-lg shadow p-6">
        @if($invoices->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Issue date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Due date</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoices as $inv)
                            <tr>
                                <td class="px-4 py-2 text-sm font-medium text-gray-800">{{ $inv->invoice_number }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $inv->issue_date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $inv->due_date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm text-right font-semibold text-gray-800">{{ number_format($inv->total_amount, 2) }} KES</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $inv->status === 'paid' ? 'bg-green-100 text-green-800' : ($inv->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        @else
            <p class="text-gray-500">No invoices yet.</p>
        @endif
    </div>

    <p><a href="{{ route('customer.checkout', ['customer' => $customer->id]) }}" class="text-blue-600 hover:underline">Renew or pay →</a></p>
</div>
@endsection
