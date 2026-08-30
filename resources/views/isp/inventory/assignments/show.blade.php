@extends('layouts.admin')

@section('title', 'Assignment')

@section('header')
    <x-isp-page-header
        :title="$assignment->item?->name ?? 'Assignment'"
        module="Inventory"
        :description="'Assigned to '.$assignment->customer?->full_name"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.inventory.assignments.index') }}" class="pb-btn">All assignments</a>
            <a href="{{ route('isp.inventory.show', $assignment->item) }}" class="pb-btn">View device</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="pb-card p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Assignment details</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Device</dt><dd><a href="{{ route('isp.inventory.show', $assignment->item) }}" class="font-medium text-indigo-600">{{ $assignment->item?->serial_number }}</a></dd></div>
                    <div><dt class="text-xs text-slate-500">Customer</dt><dd><a href="{{ route('isp.customers.show', $assignment->customer) }}" class="font-medium text-indigo-600">{{ $assignment->customer?->full_name }}</a></dd></div>
                    <div><dt class="text-xs text-slate-500">Network router</dt><dd>{{ $assignment->router?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Technician</dt><dd>{{ $assignment->technician?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Assigned by</dt><dd>{{ $assignment->assignedBy?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Assigned at</dt><dd>{{ $assignment->assigned_at?->format('M d, Y H:i') }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Installation status</dt><dd>{{ $assignment->installationStatusLabel() }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Installation date</dt><dd>{{ $assignment->installation_date?->format('M d, Y') ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Location</dt><dd>{{ $assignment->installation_location ?? '—' }}</dd></div>
                    @if ($assignment->installation_gps)
                        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">GPS</dt><dd>{{ $assignment->installation_gps }}</dd></div>
                    @endif
                    @if ($assignment->notes)
                        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Notes</dt><dd class="whitespace-pre-wrap text-sm">{{ $assignment->notes }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="space-y-4">
            @if ($assignment->is_active)
                <div class="pb-card p-5 space-y-3">
                    <h3 class="text-sm font-semibold">Actions</h3>

                    @if ($assignment->installation_status === 'pending')
                        <form method="POST" action="{{ route('isp.inventory.assignments.complete-installation', $assignment) }}">
                            @csrf
                            <button type="submit" class="pb-btn pb-btn-primary w-full justify-center">Mark installation complete</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('isp.inventory.assignments.return', $assignment) }}" onsubmit="return confirm('Return device to warehouse?');">
                        @csrf
                        <textarea name="notes" rows="2" class="isp-form-textarea mb-2 text-sm" placeholder="Return notes (optional)"></textarea>
                        <button type="submit" class="pb-btn w-full justify-center">Return to warehouse</button>
                    </form>
                </div>

                @if ($replacementItems->isNotEmpty())
                    <div class="pb-card p-5">
                        <h3 class="text-sm font-semibold">Replace device</h3>
                        <form method="POST" action="{{ route('isp.inventory.assignments.replace', $assignment) }}" class="mt-3 space-y-3">
                            @csrf
                            <select name="replacement_item_id" class="isp-form-select" required>
                                <option value="">— Replacement device —</option>
                                @foreach ($replacementItems as $device)
                                    <option value="{{ $device->id }}">{{ $device->name }} · {{ $device->serial_number }}</option>
                                @endforeach
                            </select>
                            <textarea name="notes" rows="2" class="isp-form-textarea text-sm" placeholder="Replacement reason"></textarea>
                            <button type="submit" class="pb-btn w-full justify-center" onclick="return confirm('Replace this device for the customer?');">Replace device</button>
                        </form>
                    </div>
                @endif
            @else
                <div class="pb-card p-5">
                    <p class="text-sm text-slate-600">This assignment was closed on {{ $assignment->returned_at?->format('M d, Y H:i') ?? '—' }}.</p>
                    @if ($assignment->replacedByAssignment)
                        <a href="{{ route('isp.inventory.assignments.show', $assignment->replacedByAssignment) }}" class="mt-2 inline-block text-sm text-indigo-600">View replacement assignment</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
