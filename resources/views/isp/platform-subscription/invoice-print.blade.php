<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>PrimeBilling — platform invoice</h1>
    <p><strong>{{ $invoice->isp->company_name }}</strong><br>
        {{ $invoice->isp->email }}</p>
    <p>Invoice <strong>{{ $invoice->invoice_number }}</strong><br>
        Status: {{ $invoice->status }}<br>
        @if ($invoice->paid_at)Paid {{ $invoice->paid_at->format('Y-m-d H:i') }}@endif
    </p>
    <table>
        <thead><tr><th>Description</th><th>Amount ({{ $invoice->currency }})</th></tr></thead>
        <tbody>
            <tr><td>Platform subscription</td><td>{{ number_format($invoice->subscription_amount, 2) }}</td></tr>
            @if ($invoice->setup_fee_amount > 0)
                <tr><td>White-label setup (one-time)</td><td>{{ number_format($invoice->setup_fee_amount, 2) }}</td></tr>
            @endif
            <tr><th>Total</th><th>{{ number_format($invoice->totalAmount(), 2) }}</th></tr>
        </tbody>
    </table>
    <p style="margin-top:24px;">Period: {{ $invoice->period_start?->format('Y-m-d') }} to {{ $invoice->period_end?->format('Y-m-d') }}</p>
    <p style="margin-top:32px;"><button type="button" onclick="window.print()">Print / Save as PDF</button></p>
</body>
</html>
