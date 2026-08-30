@extends('layouts.admin')

@section('title', 'My Devices')

@section('header')
    <x-isp-page-header
        title="My devices"
        module="Field inventory"
        description="{{ auth()->user()->name }} · equipment issued for installation"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.technician.inventory.scan') }}" class="pb-btn pb-btn-primary">Scan QR</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="tech-inventory mx-auto max-w-lg space-y-4 pb-8">
        @forelse ($assignments as $assignment)
            @php $item = $assignment->item; @endphp
            <a href="{{ route('isp.technician.inventory.show', $assignment) }}" class="block rounded-2xl border border-[var(--pb-border)] bg-white p-5 shadow-sm transition hover:border-indigo-300 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ $item?->name ?? 'Device' }}</p>
                        <p class="mt-1 font-mono text-sm text-slate-500">{{ $item?->serial_number }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $item?->categoryLabel() }} · Issued {{ $assignment->assigned_at?->diffForHumans() }}</p>
                    </div>
                    @if ($item?->qrCodeUrl())
                        <img src="{{ $item->qrCodeUrl() }}" alt="" class="h-14 w-14 rounded border bg-white p-0.5" loading="lazy">
                    @endif
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-[var(--pb-border)] p-10 text-center">
                <p class="text-lg font-medium text-slate-700 dark:text-slate-200">No devices assigned</p>
                <p class="mt-2 text-sm text-slate-500">Your admin will issue equipment here before installations.</p>
                <a href="{{ route('isp.technician.inventory.scan') }}" class="pb-btn pb-btn-primary mt-6 inline-flex min-h-[3rem] px-6 text-base">Scan device QR</a>
            </div>
        @endforelse
    </div>
@endsection

<style>
    @media (max-width: 640px) {
        .tech-inventory .pb-btn { min-height: 3rem; font-size: 1rem; }
    }
</style>
