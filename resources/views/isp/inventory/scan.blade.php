@extends('layouts.admin')

@section('title', 'Scan Device')

@section('header')
    <x-isp-page-header
        title="Scan device"
        module="Inventory"
        description="Point your mobile camera at an equipment QR label to open the device record instantly."
    >
        <x-slot name="actions">
            <a href="{{ $backUrl ?? route('isp.inventory.index') }}" class="pb-btn">Back</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@push('scripts')
    <x-vite-assets :assets="['resources/js/inventory-scanner.js']" />
@endpush

@section('content')
    @unless(request()->routeIs('isp.technician.*'))
        @include('isp.inventory.partials.nav')
    @endunless

    <div class="mx-auto max-w-lg">
        <div
            id="inventory-scan-root"
            class="pb-card space-y-4 p-6"
            data-resolve-url="{{ $resolveUrl }}"
        >
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Allow camera access when prompted. The scanner reads device QR codes generated for your inventory items.
            </p>

            <div id="inventory-scan-reader" class="overflow-hidden rounded-xl border border-[var(--pb-border)] bg-black/5"></div>

            <p id="inventory-scan-status" class="hidden text-center text-sm font-medium"></p>

            <div class="rounded-lg bg-slate-50 p-4 text-xs text-slate-600 dark:bg-slate-900/40 dark:text-slate-300">
                <p class="font-semibold text-slate-800 dark:text-slate-100">QR payload includes</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li>Inventory item ID</li>
                    <li>Serial number</li>
                    <li>MAC address (when set)</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
