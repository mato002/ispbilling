@forelse ($invoices as $invoice)
    <x-listing-table.row :href="route('isp.invoices.show', $invoice)">
        <td class="font-mono font-semibold">{{ $invoice->invoice_number }}</td>
        <td>
            <div class="font-medium text-slate-900 dark:text-white">{{ $invoice->customer->full_name }}</div>
            <div class="text-sm text-slate-500">{{ $invoice->customer->phone_number }}</div>
        </td>
        <td class="font-mono whitespace-nowrap font-semibold">KES {{ number_format($invoice->total_amount, 2) }}</td>
        <td class="whitespace-nowrap">{{ $invoice->issue_date->format('M j, Y') }}</td>
        <td class="whitespace-nowrap">
            {{ $invoice->due_date->format('M j, Y') }}
            @if ($invoice->isOverdue())
                <span class="ml-1 text-xs font-semibold text-rose-600 dark:text-rose-400">Overdue</span>
            @endif
        </td>
        <td>
            @if ($invoice->status === 'paid')
                <x-status-badge status="paid" />
            @elseif ($invoice->status === 'cancelled')
                <x-status-badge status="cancelled" />
            @elseif ($invoice->status === 'overdue')
                <x-status-badge status="overdue" />
            @else
                <x-status-badge status="pending" />
            @endif
        </td>
        <td class="text-right whitespace-nowrap">
            <x-table-actions>
                <x-table-actions.link :href="route('isp.invoices.show', $invoice)" label="View" />
                <x-table-actions.link :href="route('isp.invoices.show', $invoice).'?pdf=1'" label="Download PDF" target="_blank" />
                @if(in_array($invoice->status, ['pending', 'overdue'], true))
                    <button type="button" @click="$dispatch('open-invoice-payment', {url: '{{ route('isp.invoices.mark-paid', $invoice) }}', amount: '{{ number_format((float) $invoice->total_amount, 2, '.', '') }}', reference: '{{ addslashes($invoice->invoice_number) }}'})" class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Record payment</button>
                    <button type="button" data-invoice-stk data-url="{{ route('isp.payments.initiate.store') }}" data-invoice-id="{{ $invoice->id }}" data-phone="{{ $invoice->customer->phone_number }}" class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Send M-Pesa STK push</button>
                    <x-table-actions.form :action="route('isp.invoices.remind', $invoice)" label="Send reminder" :danger="false" />
                    <button type="button" @click="$dispatch('open-invoice-adjustment', {url: '{{ route('isp.invoices.adjust', $invoice) }}', balance: '{{ number_format((float) $invoice->total_amount, 2, '.', '') }}', credit: '{{ number_format((float) $invoice->customer->account_credit, 2, '.', '') }}'})" class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Apply credit / discount</button>
                    <div class="my-1 border-t border-slate-200 dark:border-slate-700"></div>
                    <x-table-actions.form :action="route('isp.invoices.cancel', $invoice)" label="Cancel invoice" :danger="true" onsubmit="confirmFormSubmit(event, {title: 'Cancel invoice', text: 'Cancel this invoice? Pending payment attempts will be marked failed.', icon: 'warning', confirmButtonText: 'Cancel invoice', confirmButtonColor: '#ef4444'}); return false;" />
                    <x-table-actions.form :action="route('isp.invoices.destroy', $invoice)" method="DELETE" label="Delete unpaid invoice" :danger="true" onsubmit="confirmFormSubmit(event, {title: 'Delete invoice', text: 'Permanently delete this unpaid invoice? Its audit history will be retained.', icon: 'warning', confirmButtonText: 'Delete invoice', confirmButtonColor: '#ef4444'}); return false;" />
                @elseif($invoice->status === 'cancelled' && !$invoice->payments()->where('status', 'completed')->exists())
                    <x-table-actions.form :action="route('isp.invoices.destroy', $invoice)" method="DELETE" label="Delete unpaid invoice" :danger="true" onsubmit="confirmFormSubmit(event, {title: 'Delete invoice', text: 'Permanently delete this unpaid invoice? Its audit history will be retained.', icon: 'warning', confirmButtonText: 'Delete invoice', confirmButtonColor: '#ef4444'}); return false;" />
                @endif
            </x-table-actions>
        </td>
    </x-listing-table.row>
@empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No invoices match these filters. <button type="button" class="ml-1 font-semibold text-indigo-600" @click="$dispatch('open-invoice-modal')">Create an invoice</button></td></tr>
@endforelse
