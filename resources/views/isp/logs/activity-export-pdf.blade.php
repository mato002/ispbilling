<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity audit export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        .meta { font-size: 8px; color: #444; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; font-size: 8px; text-transform: uppercase; }
        .muted { color: #555; }
        .wrap { word-break: break-word; }
    </style>
</head>
<body>
    <h1>ISP activity audit log</h1>
    <div class="meta">
        Exported {{ $exportedAt->format('Y-m-d H:i:s T') }}
        @if($ispName)
            · {{ $ispName }}
        @endif
        · {{ $logs->count() }} row(s)
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:11%">Time</th>
                <th style="width:7%">Severity</th>
                <th style="width:9%">Module</th>
                <th style="width:10%">User</th>
                <th style="width:10%">Action</th>
                <th>Description</th>
                <th style="width:9%">IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                @php
                    $severity = \App\Support\ActivityLogAudit::severity($log);
                    $module = \App\Support\ActivityLogAudit::moduleKey($log);
                    $moduleLabel = \App\Support\ActivityLogAudit::MODULES[$module] ?? $module;
                @endphp
                <tr>
                    <td class="muted">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ strtoupper($severity) }}</td>
                    <td>{{ $moduleLabel }}</td>
                    <td class="wrap">{{ $log->user?->name ?? '—' }}</td>
                    <td class="wrap">{{ $log->action }}</td>
                    <td class="wrap">{{ $log->description }}</td>
                    <td class="muted wrap">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
