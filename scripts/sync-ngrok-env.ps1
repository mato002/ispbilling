# Sync APP_URL + M-Pesa callback URLs from a running ngrok tunnel (local dev).
# Prerequisites: `ngrok http 8000` (or same port your app uses) already running.
# Usage (from project root): powershell -ExecutionPolicy Bypass -File scripts/sync-ngrok-env.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $root '.env'

try {
    $t = Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 5
} catch {
    Write-Error 'Cannot reach ngrok API (http://127.0.0.1:4040). Start ngrok first, e.g. ngrok http 8000'
    exit 1
}

$https = @($t.tunnels | Where-Object { $_.proto -eq 'https' } | Select-Object -ExpandProperty public_url -First 1)
if (-not $https) {
    Write-Error 'No HTTPS tunnel found in ngrok response.'
    exit 1
}

$cb = "$https/api/payments/mpesa/callback"
$to = "$https/api/payments/mpesa/timeout"

$lines = Get-Content -LiteralPath $envFile
$out = foreach ($line in $lines) {
    if ($line -match '^\s*APP_URL=') { "APP_URL=$https" }
    elseif ($line -match '^\s*MPESA_CALLBACK_URL=') { "MPESA_CALLBACK_URL=$cb" }
    elseif ($line -match '^\s*MPESA_TIMEOUT_URL=') { "MPESA_TIMEOUT_URL=$to" }
    else { $line }
}

$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllLines($envFile, $out, $utf8NoBom)

Set-Location $root
& php artisan config:clear
Write-Host "Updated .env: APP_URL, MPESA_CALLBACK_URL, MPESA_TIMEOUT_URL -> $https"
