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
            </x-table-actions>
        </td>
    </x-listing-table.row>
@empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No invoices match these filters. <button type="button" class="ml-1 font-semibold text-indigo-600" @click="$dispatch('open-invoice-modal')">Create an invoice</button></td></tr>
@endforelse
