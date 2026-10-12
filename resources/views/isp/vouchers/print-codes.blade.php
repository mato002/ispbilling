<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Voucher codes · {{ $plan->name }}</title>
    <style>
        body{font:14px/1.45 system-ui,-apple-system,Segoe UI,sans-serif;color:#0f172a;margin:24px}
        header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
        h1{font-size:22px;margin:0}p{color:#64748b;margin:4px 0}
        button{border:0;border-radius:8px;background:#4f46e5;color:#fff;padding:10px 15px;font-weight:700;cursor:pointer}
        .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        article{border:1px solid #cbd5e1;border-radius:10px;padding:12px;break-inside:avoid}
        code{font:700 18px/1.3 ui-monospace,SFMono-Regular,Consolas,monospace;letter-spacing:.08em}
        small{display:block;color:#64748b;margin-top:5px}
        @media print{body{margin:8mm}button{display:none}.grid{gap:6px}article{padding:8px}}
    </style>
</head>
<body>
    <header>
        <div><h1>{{ $plan->name }} · Voucher codes</h1><p>{{ $vouchers->count() }} codes · Generated {{ now()->format('M j, Y H:i') }}</p></div>
        <button onclick="window.print()">Print codes</button>
    </header>
    <main class="grid">
        @forelse($vouchers as $voucher)
            <article><code>{{ $voucher->voucher_code }}</code><small>{{ ucfirst($voucher->status) }} · {{ $voucher->router?->name ?? 'Router unavailable' }}</small></article>
        @empty
            <p>No vouchers exist for this plan yet.</p>
        @endforelse
    </main>
</body>
</html>
