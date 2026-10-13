<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Voucher codes · {{ $plan->name }}</title>
    <style>
        @page{margin:8mm}body{font:12px/1.4 system-ui,-apple-system,Segoe UI,sans-serif;color:#0f172a;margin:24px}
        header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
        h1{font-size:22px;margin:0}p{color:#64748b;margin:4px 0}
        button{border:0;border-radius:8px;background:#4f46e5;color:#fff;padding:10px 15px;font-weight:700;cursor:pointer}
        .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
        article{border:1px dashed #94a3b8;border-radius:7px;padding:9px;break-inside:avoid;display:flex;align-items:center;gap:7px;min-height:76px}
        article img{width:62px;height:62px}
        code{font:700 15px/1.3 ui-monospace,SFMono-Regular,Consolas,monospace;letter-spacing:.06em;overflow-wrap:anywhere}
        small{display:block;color:#64748b;margin-top:3px;font-size:9px}
        @media print{body{margin:0}button{display:none}.grid{gap:5px}article{padding:6px}}
    </style>
</head>
<body>
    <header>
        <div><h1>{{ $plan->name }} · Voucher codes</h1><p>{{ $vouchers->count() }} codes · Batch {{ $vouchers->first()?->batch_reference ?? '—' }} · Generated {{ now()->format('M j, Y H:i') }}</p></div>
        <button onclick="window.print()">Print codes</button>
    </header>
    <main class="grid">
        @forelse($vouchers as $voucher)
            <article>
                <img src="{{ $voucher->print_qr_data_uri }}" alt="QR code for voucher {{ $voucher->voucher_code }}">
                <div><code>{{ $voucher->voucher_code }}</code><small>{{ $voucher->reportStatusLabel() }} · {{ $voucher->plan?->name ?? $plan->name }}</small><small>{{ $voucher->router?->name ?? 'Router unavailable' }} · Expires {{ $voucher->expires_at?->format('M j, Y') ?? 'Never' }}</small></div>
            </article>
        @empty
            <p>No vouchers exist for this plan yet.</p>
        @endforelse
    </main>
</body>
</html>
