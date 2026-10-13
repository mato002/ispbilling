<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Revenue statement</title>
    <style>
        @page{margin:26px 28px}body{font:9px DejaVu Sans,sans-serif;color:#172033}
        h1{font-size:18px;color:#312e81;margin:0}.muted{color:#64748b}.summary{font-size:12px;background:#eef2ff;padding:10px;margin:14px 0}
        table{border-collapse:collapse;width:100%}th{background:#e2e8f0;text-align:left}th,td{padding:6px;border-bottom:1px solid #cbd5e1}.right{text-align:right}
    </style>
</head>
<body>
    <h1>{{ $isp->name }} · Revenue statement</h1>
    <p class="muted">{{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }} · Completed payments · Generated {{ $exportedAt->format('M j, Y H:i T') }}</p>
    <div class="summary"><strong>{{ number_format($paymentCount) }} payments</strong> · Total collected <strong>KES {{ number_format($total, 2) }}</strong></div>
    <table>
        <thead><tr><th>Date</th><th>Gateway</th><th>Service</th><th>Package / plan</th><th>Router / location</th><th class="right">Payments</th><th class="right">Collected (KES)</th></tr></thead>
        <tbody>
            @forelse ($groups as $row)
                <tr><td>{{ $row['day'] }}</td><td>{{ $row['method'] }}</td><td>{{ $row['service'] }}</td><td>{{ $row['plan'] }}</td><td>{{ $row['location'] }}</td><td class="right">{{ $row['count'] }}</td><td class="right">{{ number_format($row['amount'], 2) }}</td></tr>
            @empty
                <tr><td colspan="7">No completed payments in this date range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
