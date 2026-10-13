<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->payment_reference }}</title>
    <style>
        body{font:12px DejaVu Sans,sans-serif;color:#172033;margin:38px}
        .top{border-bottom:3px solid #4338ca;padding-bottom:16px;margin-bottom:24px}
        h1{font-size:24px;margin:0 0 6px;color:#312e81}.muted{color:#64748b}
        .paid{display:inline-block;background:#dcfce7;color:#166534;padding:6px 11px;border-radius:14px;font-weight:bold}
        table{width:100%;border-collapse:collapse;margin-top:22px}td{padding:11px 8px;border-bottom:1px solid #e2e8f0}td:first-child{color:#64748b;width:38%}td:last-child{text-align:right;font-weight:bold}
        .amount{font-size:21px;color:#2563eb}.foot{margin-top:34px;padding-top:14px;border-top:1px solid #e2e8f0;color:#64748b;font-size:10px}
    </style>
</head>
<body>
    <header class="top">
        <h1>{{ $isp->name }}</h1>
        <div class="muted">Payment receipt</div>
    </header>
    <p><span class="paid">PAID</span></p>
    <table>
        <tr><td>Receipt reference</td><td>{{ $payment->payment_reference }}</td></tr>
        <tr><td>Gateway / M-Pesa reference</td><td>{{ $payment->transaction_id ?: '—' }}</td></tr>
        <tr><td>Paid at</td><td>{{ $payment->processed_at?->format('M j, Y H:i') ?? '—' }}</td></tr>
        <tr><td>Customer</td><td>{{ $payment->customer?->full_name ?? '—' }}</td></tr>
        <tr><td>Phone</td><td>{{ $payment->phone_number ?: ($payment->customer?->phone_number ?? '—') }}</td></tr>
        <tr><td>Service</td><td>{{ $payment->serviceTypeLabel() }}</td></tr>
        <tr><td>Package / plan</td><td>{{ $payment->invoice?->subscription?->plan?->name ?? 'Internet service' }}</td></tr>
        <tr><td>Invoice</td><td>{{ $payment->invoice?->invoice_number ?? '—' }}</td></tr>
        <tr><td>Payment method</td><td>{{ strtoupper(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
        <tr><td>Tax included</td><td>KES {{ number_format((float) ($payment->invoice?->tax ?? 0), 2) }}</td></tr>
        <tr><td>Total paid</td><td class="amount">KES {{ number_format((float) $payment->amount, 2) }}</td></tr>
    </table>
    <div class="foot">Generated {{ now()->format('M j, Y H:i T') }} · Keep this receipt for your records.</div>
</body>
</html>
