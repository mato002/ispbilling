@extends('layouts.admin')

@section('title', $technicianAssignment->item?->name ?? 'Device')

@section('header')
    <x-isp-page-header
        :title="$technicianAssignment->item?->name ?? 'Device'"
        module="Field inventory"
        :description="$technicianAssignment->item?->serial_number"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.technician.inventory.index') }}" class="pb-btn">Back</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @php $item = $technicianAssignment->item; @endphp

    <div class="tech-inventory mx-auto max-w-lg space-y-5 pb-10">
        <div class="rounded-2xl border border-[var(--pb-border)] bg-white p-5 dark:bg-slate-900">
            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Serial</dt><dd class="font-mono font-medium">{{ $item?->serial_number }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Category</dt><dd>{{ $item?->categoryLabel() }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Brand / model</dt><dd>{{ trim(($item?->brand ?? '').' '.($item?->model ?? '')) ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Issued</dt><dd>{{ $technicianAssignment->assigned_at?->format('M d, Y H:i') }}</dd></div>
            </dl>
            @if ($item?->qrCodeUrl())
                <div class="mt-4 flex justify-center">
                    <img src="{{ $item->qrCodeUrl() }}" alt="QR" class="h-36 w-36 rounded border bg-white p-1">
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-indigo-200 bg-indigo-50/80 p-5 dark:border-indigo-900 dark:bg-indigo-950/30">
            <h2 class="text-base font-semibold text-indigo-950 dark:text-indigo-100">Install at customer</h2>
            <form method="POST" action="{{ route('isp.technician.inventory.install', $technicianAssignment) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="isp-form-label" for="customer_id">Customer</label>
                    <select id="customer_id" name="customer_id" class="isp-form-select text-base" required>
                        <option value="">Select customer…</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id) data-router="{{ $customer->router_id }}">
                                {{ $customer->full_name }} · {{ $customer->phone_number }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="router_id">Network router (optional)</label>
                    <select id="router_id" name="router_id" class="isp-form-select text-base">
                        <option value="">— None —</option>
                        @foreach ($routers as $router)
                            <option value="{{ $router->id }}" @selected(old('router_id') == $router->id)>{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="isp-form-label" for="installation_location">Installation location</label>
                    <input id="installation_location" type="text" name="installation_location" value="{{ old('installation_location') }}" class="isp-form-input text-base" placeholder="Customer address or landmark">
                </div>
                <div>
                    <label class="isp-form-label" for="installation_gps">GPS (optional)</label>
                    <input id="installation_gps" type="text" name="installation_gps" value="{{ old('installation_gps') }}" class="isp-form-input text-base" placeholder="-1.2921, 36.8219">
                </div>
                <div>
                    <label class="isp-form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="isp-form-textarea text-base">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="pb-btn pb-btn-primary flex w-full min-h-[3.25rem] items-center justify-center text-lg font-semibold">
                    Mark installed
                </button>
            </form>
        </div>

        <div class="grid gap-3">
            <form method="POST" action="{{ route('isp.technician.inventory.return', $technicianAssignment) }}" onsubmit="return confirm('Return this device to the warehouse?');">
                @csrf
                <button type="submit" class="pb-btn flex w-full min-h-[3.25rem] items-center justify-center text-lg font-semibold">
                    Return to warehouse
                </button>
            </form>

            <form method="POST" action="{{ route('isp.technician.inventory.faulty', $technicianAssignment) }}" onsubmit="return confirm('Mark this device as faulty?');" class="space-y-2">
                @csrf
                <textarea name="notes" rows="2" class="isp-form-textarea text-base" placeholder="Describe the fault (optional)"></textarea>
                <button type="submit" class="pb-btn flex w-full min-h-[3.25rem] items-center justify-center text-lg font-semibold text-rose-700">
                    Mark faulty
                </button>
            </form>
        </div>
    </div>
@endsection
