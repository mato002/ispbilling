@extends('layouts.admin')

@section('title', 'Provision router')

@section('header')
    <x-isp-page-header
        title="Provision router"
        module="Network"
        description="Add a MikroTik device to the fleet. The first telemetry poll is queued automatically after a successful connection test."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.routers.index') }}" class="pb-btn">Back to fleet</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <form method="POST" action="{{ route('isp.routers.store') }}" id="routerForm" class="space-y-6">
        @csrf

        @include('isp.routers.partials.form', [
            'router' => null,
            'owners' => $owners,
            'routerDefaults' => $routerDefaults ?? [],
        ])

        <div class="pb-card p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Test connection</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Validates credentials without saving. Saved routers are polled every minute automatically.</p>
                </div>
                <button type="button" id="testConnectionBtn" class="pb-btn pb-btn-primary">Test connection</button>
            </div>
            <div id="connectionStatus" class="mt-4 hidden"></div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('isp.routers.index') }}" class="pb-btn">Cancel</a>
            <button type="submit" class="pb-btn pb-btn-primary">Save router</button>
        </div>
    </form>

    <script>
        document.getElementById('testConnectionBtn').addEventListener('click', function () {
            const btn = this;
            const statusDiv = document.getElementById('connectionStatus');

            const payload = {
                ip_address: document.getElementById('ip_address').value,
                port: document.getElementById('port').value,
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
                use_ssl: document.querySelector('[name="use_ssl"]:checked') ? true : false,
            };

            if (!payload.ip_address || !payload.port || !payload.username || !payload.password) {
                statusDiv.classList.remove('hidden');
                statusDiv.innerHTML = '<div class="rounded-xl border border-amber-300/60 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-100">Fill in IP, port, username, and password before testing.</div>';
                return;
            }

            btn.disabled = true;
            const originalLabel = btn.textContent;
            btn.textContent = 'Testing...';
            statusDiv.classList.remove('hidden');
            statusDiv.innerHTML = '<div class="rounded-xl border border-indigo-300/60 bg-indigo-50 px-4 py-3 text-sm text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-100">Opening RouterOS API session...</div>';

            fetch('{{ route('isp.routers.test-connection') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(payload),
            })
                .then((r) => r.json())
                .then((data) => {
                    btn.disabled = false;
                    btn.textContent = originalLabel;
                    if (data.success) {
                        const count = data.hotspot_servers_count || 0;
                        statusDiv.innerHTML = `<div class="rounded-xl border border-emerald-300/60 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-100">
                            <p class="font-semibold">Connection OK.</p>
                            <p>${count} hotspot server(s) detected. They will be imported automatically on save.</p>
                        </div>`;
                    } else {
                        statusDiv.innerHTML = `<div class="rounded-xl border border-rose-300/60 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-100">
                            <p class="font-semibold">Connection failed.</p>
                            <p class="font-mono text-xs mt-1">${data.error || 'Unknown error'}</p>
                        </div>`;
                    }
                })
                .catch((err) => {
                    btn.disabled = false;
                    btn.textContent = originalLabel;
                    statusDiv.innerHTML = `<div class="rounded-xl border border-rose-300/60 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-100">Request error: ${err}</div>`;
                });
        });
    </script>
@endsection
