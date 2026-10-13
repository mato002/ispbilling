<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment statement</title>
    <style>
        @page{margin:28px 30px}body{font:9px DejaVu Sans,sans-serif;color:#172033}
        h1{font-size:19px;margin:0;color:#312e81}.muted{color:#64748b}.summary{margin:15px 0;padding:10px;background:#eef2ff;border-radius:7px;font-size:12px}
        table{width:100%;border-collapse:collapse}th{background:#e2e8f0;text-align:left;color:#334155}th,td{padding:6px 5px;border-bottom:1px solid #e2e8f0;vertical-align:top}.right{text-align:right}.nowrap{white-space:nowrap}
        .foot{margin-top:12px;color:#64748b;font-size:8px}
    </style>
</head>
<body>
    <h1>{{ $isp->name }} · Payments statement</h1>
    <p class="muted">Range: {{ $rangeLabel }} · Generated {{ $exportedAt->format('M j, Y H:i T') }}</p>
    <div class="summary"><strong>Completed posted receipts:</strong> {{ $payments->count() }} &nbsp; · &nbsp; <strong>Total:</strong> KES {{ number_format($total, 2) }}</div>
    @if ($truncated)<p class="muted">Showing the first 500 matching receipts. Use CSV or Excel for the full filtered set.</p>@endif
    <table>
        <thead><tr><th>Paid at</th><th>Reference</th><th>Gateway ref</th><th>Customer / phone</th><th>Service / plan</th><th>Method</th><th class="right">Amount</th></tr></thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td class="nowrap">{{ $payment->processed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $payment->payment_reference }}</td>
                    <td>{{ $payment->transaction_id ?? '—' }}</td>
                    <td>{{ $payment->customer?->full_name ?? '—' }}<br><span class="muted">{{ $payment->phone_number ?? $payment->customer?->phone_number }}</span></td>
                    <td>{{ $payment->serviceTypeLabel() }}<br><span class="muted">{{ $payment->invoice?->subscription?->plan?->name ?? '—' }}</span></td>
                    <td>{{ strtoupper($payment->payment_method) }}</td>
                    <td class="right nowrap">KES {{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No completed payments match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="foot">Confidential accounting record · {{ $isp->name }}</p>
</body>
</html>
