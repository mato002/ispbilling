<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .company-info {
            flex: 1;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #1e40af;
        }
        .company-details {
            font-size: 10px;
            color: #666;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .billing-box {
            flex: 1;
            padding: 15px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .billing-box h3 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #1e40af;
            text-transform: uppercase;
        }
        .billing-box p {
            font-size: 11px;
            margin-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead {
            background-color: #1e40af;
            color: white;
        }
        .items-table th {
            padding: 12px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .totals {
            margin-top: 20px;
            margin-left: auto;
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .total-row:last-child {
            border-bottom: 2px solid #333;
            font-weight: bold;
            font-size: 14px;
            padding-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-paid {
            background-color: #10b981;
            color: white;
        }
        .status-pending {
            background-color: #f59e0b;
            color: white;
        }
        .status-overdue {
            background-color: #ef4444;
            color: white;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .notes {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9fafb;
            border-left: 4px solid #1e40af;
        }
        .notes h4 {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #1e40af;
        }
        .notes p {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">{{ $invoice->isp->company_name ?? $invoice->isp->name ?? 'ISP Billing System' }}</div>
                <div class="company-details">
                    @if($invoice->isp->email)
                        Email: {{ $invoice->isp->email }}<br>
                    @endif
                    @if($invoice->isp->phone)
                        Phone: {{ $invoice->isp->phone }}<br>
                    @endif
                    @if(isset($invoice->isp->settings['address']))
                        {{ $invoice->isp->settings['address'] }}
                    @endif
                </div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                <div style="margin-top: 10px;">
                    @if($invoice->status === 'paid')
                        <span class="status-badge status-paid">PAID</span>
                    @elseif($invoice->status === 'overdue')
                        <span class="status-badge status-overdue">OVERDUE</span>
                    @else
                        <span class="status-badge status-pending">PENDING</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Billing Information -->
        <div class="billing-section">
            <div class="billing-box">
                <h3>Bill To:</h3>
                <p><strong>{{ $invoice->customer->full_name }}</strong></p>
                <p>{{ $invoice->customer->phone_number }}</p>
                @if($invoice->customer->location)
                    <p>{{ $invoice->customer->location }}</p>
                @endif
            </div>
            <div class="billing-box" style="margin-left: 20px;">
                <h3>Invoice Details:</h3>
                <p><strong>Issue Date:</strong> {{ $invoice->issue_date->format('F d, Y') }}</p>
                <p><strong>Due Date:</strong> {{ $invoice->due_date->format('F d, Y') }}</p>
                @if($invoice->paid_date)
                    <p><strong>Paid Date:</strong> {{ $invoice->paid_date->format('F d, Y') }}</p>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left">Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @if($invoice->subscription)
                            Subscription: {{ $invoice->subscription->plan->name }}
                            @if($invoice->subscription->plan->duration_days)
                                ({{ $invoice->subscription->plan->duration_days }} days)
                            @endif
                        @else
                            Service Charge
                        @endif
                    </td>
                    <td class="text-right">KES {{ number_format($invoice->amount, 2) }}</td>
                </tr>
                @if($invoice->tax > 0)
                    <tr>
                        <td>Tax</td>
                        <td class="text-right">KES {{ number_format($invoice->tax, 2) }}</td>
                    </tr>
                @endif
                @if(isset($invoice->penalty) && $invoice->penalty > 0)
                    <tr>
                        <td>Penalty</td>
                        <td class="text-right">KES {{ number_format($invoice->penalty, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>KES {{ number_format($invoice->amount, 2) }}</span>
            </div>
            @if($invoice->tax > 0)
                <div class="total-row">
                    <span>Tax:</span>
                    <span>KES {{ number_format($invoice->tax, 2) }}</span>
                </div>
            @endif
            @if(isset($invoice->penalty) && $invoice->penalty > 0)
                <div class="total-row">
                    <span>Penalty:</span>
                    <span>KES {{ number_format($invoice->penalty, 2) }}</span>
                </div>
            @endif
            <div class="total-row">
                <span>TOTAL:</span>
                <span>KES {{ number_format($invoice->total_amount, 2) }}</span>
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
            <div class="notes">
                <h4>Notes:</h4>
                <p>{{ $invoice->notes }}</p>
            </div>
        @endif

        <!-- Payments -->
        @if($invoice->payments && $invoice->payments->count() > 0)
            <div style="margin-top: 30px;">
                <h3 style="font-size: 12px; font-weight: bold; margin-bottom: 10px; color: #1e40af;">Payment History:</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="text-left">Reference</th>
                            <th class="text-left">Method</th>
                            <th class="text-right">Amount</th>
                            <th class="text-left">Date</th>
                            <th class="text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_reference }}</td>
                                <td>{{ strtoupper($payment->payment_method) }}</td>
                                <td class="text-right">KES {{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if($payment->status === 'completed')
                                        <span class="status-badge status-paid">Completed</span>
                                    @else
                                        <span class="status-badge status-pending">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice. No signature required.</p>
        </div>
    </div>
</body>
</html>





