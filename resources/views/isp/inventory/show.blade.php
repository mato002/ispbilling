@extends('layouts.admin')

@section('title', $item->name)

@section('header')
    <x-isp-page-header
        :title="$item->name"
        module="Inventory"
        :description="$item->serial_number . ' · ' . $item->categoryLabel()"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.inventory.index') }}" class="pb-btn">All items</a>
            @if ($item->status !== 'assigned')
                <a href="{{ route('isp.inventory.edit', $item) }}" class="pb-btn">Edit</a>
            @endif
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="pb-card p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Device details</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Serial</dt><dd class="font-mono font-medium">{{ $item->serial_number }}</dd></div>
                    <div><dt class="text-xs text-slate-500">MAC</dt><dd>{{ $item->mac_address ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Brand / Model</dt><dd>{{ trim(($item->brand ?? '').' '.($item->model ?? '')) ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Category</dt><dd>{{ $item->categoryLabel() }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Status</dt><dd><x-status-badge :status="$item->status" /></dd></div>
                    <div><dt class="text-xs text-slate-500">Location</dt><dd>{{ $item->locationLabel() }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Purchase price</dt><dd>{{ $item->purchase_price ? number_format($item->purchase_price, 2) : '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Purchase date</dt><dd>{{ $item->purchase_date?->format('M d, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Warranty expiry</dt><dd>{{ $item->warranty_expiry?->format('M d, Y') ?? '—' }}</dd></div>
                    @if ($item->purchase)
                        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Purchase record</dt><dd>{{ $item->purchase->vendor_name }} @if($item->purchase->invoice_number) ({{ $item->purchase->invoice_number }}) @endif</dd></div>
                    @endif
                    @if ($item->notes)
                        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Notes</dt><dd class="whitespace-pre-wrap text-sm">{{ $item->notes }}</dd></div>
                    @endif
                </dl>
            </div>

            @if ($item->activeAssignment)
                <div class="pb-card p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Active assignment</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-500">Customer</dt><dd><a href="{{ route('isp.customers.show', $item->activeAssignment->customer) }}" class="font-medium text-indigo-600">{{ $item->activeAssignment->customer->full_name }}</a></dd></div>
                        <div><dt class="text-xs text-slate-500">Router</dt><dd>{{ $item->activeAssignment->router?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Technician</dt><dd>{{ $item->activeAssignment->technician?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Installation</dt><dd>{{ $item->activeAssignment->installationStatusLabel() }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Assigned</dt><dd>{{ $item->activeAssignment->assigned_at?->format('M d, Y H:i') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Installation location</dt><dd>{{ $item->activeAssignment->installation_location ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('isp.inventory.assignments.show', $item->activeAssignment) }}" class="pb-btn pb-btn-sm">Manage assignment</a>
                    </div>
                </div>
            @endif

            <div class="pb-card p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Recent history</h2>
                @if ($item->transactions->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">No transactions yet.</p>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($item->transactions as $tx)
                            <li class="flex items-start justify-between gap-4 border-b border-[var(--pb-border)] pb-3 last:border-0">
                                <div>
                                    <p class="text-sm font-medium">{{ $tx->actionLabel() }}</p>
                                    <p class="text-xs text-slate-500">{{ $tx->created_at?->format('M d, Y H:i') }} · {{ $tx->performedBy?->name ?? 'System' }}</p>
                                    @if ($tx->customer)<p class="text-xs text-slate-500">Customer: {{ $tx->customer->full_name }}</p>@endif
                                    @if ($tx->notes)<p class="mt-1 text-xs text-slate-600">{{ $tx->notes }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('isp.inventory.transactions', ['q' => $item->serial_number]) }}" class="mt-4 inline-block text-sm text-indigo-600">View full history</a>
            </div>
        </div>

        <div class="space-y-4">
            <div class="pb-card p-5 text-center">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Device QR code</h3>
                <div class="mt-4 flex justify-center">
                    @include('isp.inventory.partials.qr-code', ['item' => $item, 'size' => 180])
                </div>
                @if ($item->qrCodeUrl())
                    <a href="{{ $item->qrCodeUrl() }}" download="inventory-{{ $item->serial_number }}.png" class="mt-3 inline-block text-sm text-indigo-600 hover:text-indigo-700">Download PNG</a>
                @endif
                <p class="mt-3 font-mono text-xs text-slate-500">{{ $item->serial_number }}</p>
            </div>

            <div class="pb-card p-5">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Technician issue</h3>
                @if ($item->activeTechnicianAssignment)
                    <p class="mt-2 text-sm text-slate-600">With <strong>{{ $item->activeTechnicianAssignment->technician?->name }}</strong> since {{ $item->activeTechnicianAssignment->assigned_at?->format('M d, Y') }}.</p>
                @elseif ($item->isIssuableToTechnician() && ($technicians ?? collect())->isNotEmpty())
                    <form method="POST" action="{{ route('isp.inventory.assign-technician', $item) }}" class="mt-4 space-y-3">
                        @csrf
                        <select name="technician_user_id" class="isp-form-select" required>
                            <option value="">Select technician…</option>
                            @foreach ($technicians as $tech)
                                <option value="{{ $tech->id }}" @selected(old('technician_user_id') == $tech->id)>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                        <textarea name="notes" rows="2" class="isp-form-textarea text-sm" placeholder="Issue notes (optional)"></textarea>
                        <button type="submit" class="pb-btn w-full justify-center">Issue to technician</button>
                    </form>
                @else
                    <p class="mt-2 text-sm text-slate-500">Not available for technician issue.</p>
                @endif
            </div>

            <div class="pb-card p-5">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Actions</h3>
                <div class="mt-4 space-y-2">
                    @if ($item->isAssignable())
                        <a href="{{ route('isp.inventory.assignments.create', ['inventory_item_id' => $item->id]) }}" class="pb-btn pb-btn-primary w-full justify-center">Assign to customer</a>
                    @endif

                    @if ($item->status === 'assigned' && $item->activeAssignment)
                        <form method="POST" action="{{ route('isp.inventory.return', $item) }}" onsubmit="return confirm('Return this device to warehouse?');">
                            @csrf
                            <button type="submit" class="pb-btn w-full justify-center">Return to warehouse</button>
                        </form>
                    @endif

                    @if (in_array($item->status, ['available', 'assigned', 'faulty']))
                        <form method="POST" action="{{ route('isp.inventory.mark-faulty', $item) }}" onsubmit="return confirm('Mark this device as faulty?');">
                            @csrf
                            <textarea name="notes" rows="2" class="isp-form-textarea mb-2 text-sm" placeholder="Fault description (optional)"></textarea>
                            <button type="submit" class="pb-btn w-full justify-center text-rose-700">Mark faulty</button>
                        </form>
                    @endif

                    @if ($item->status !== 'assigned' && $item->status !== 'retired')
                        <form method="POST" action="{{ route('isp.inventory.retire', $item) }}" onsubmit="return confirm('Retire this device permanently?');">
                            @csrf
                            <button type="submit" class="pb-btn w-full justify-center">Retire device</button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($item->assignments->isNotEmpty())
                <div class="pb-card p-5">
                    <h3 class="text-sm font-semibold">Past assignments</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($item->assignments as $assignment)
                            <li>
                                <a href="{{ route('isp.inventory.assignments.show', $assignment) }}" class="text-indigo-600">{{ $assignment->customer->full_name }}</a>
                                <span class="text-xs text-slate-500"> · {{ $assignment->assigned_at?->format('M d, Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection
