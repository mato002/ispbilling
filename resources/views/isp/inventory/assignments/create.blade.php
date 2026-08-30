@extends('layouts.admin')

@section('title', 'Assign Device')

@section('header')
    <x-isp-page-header
        title="Assign device"
        module="Inventory"
        description="Link available stock to a customer and record installation details."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.inventory.assignments.index') }}" class="pb-btn">All assignments</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('isp.inventory.assignments.store') }}" class="pb-card space-y-5 p-6">
            @csrf

            <div>
                <label class="isp-form-label" for="inventory_item_id">Device</label>
                <select id="inventory_item_id" name="inventory_item_id" class="isp-form-select" required>
                    <option value="">— Select available device —</option>
                    @foreach ($availableItems as $device)
                        <option value="{{ $device->id }}" @selected(old('inventory_item_id', request('inventory_item_id')) == $device->id)>
                            {{ $device->name }} · {{ $device->serial_number }} ({{ \App\Support\InventoryCatalog::categoryLabel($device->category) }})
                        </option>
                    @endforeach
                </select>
                @error('inventory_item_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="isp-form-label" for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" class="isp-form-select" required>
                    <option value="">— Select customer —</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id', request('customer_id')) == $customer->id) data-router="{{ $customer->router_id }}">
                            {{ $customer->full_name }} · {{ $customer->phone_number }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="router_id">PPPoE / Network router (optional)</label>
                    <select id="router_id" name="router_id" class="isp-form-select">
                        <option value="">— None —</option>
                        @foreach ($routers as $router)
                            <option value="{{ $router->id }}" @selected(old('router_id') == $router->id)>{{ $router->name }} ({{ $router->ip_address }})</option>
                        @endforeach
                    </select>
                    @error('router_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="technician_user_id">Technician (optional)</label>
                    <select id="technician_user_id" name="technician_user_id" class="isp-form-select">
                        <option value="">— Unassigned —</option>
                        @foreach ($technicians as $tech)
                            <option value="{{ $tech->id }}" @selected(old('technician_user_id') == $tech->id)>{{ $tech->name }}</option>
                        @endforeach
                    </select>
                    @error('technician_user_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="installation_date">Installation date</label>
                    <input id="installation_date" type="date" name="installation_date" value="{{ old('installation_date', now()->format('Y-m-d')) }}" class="isp-form-input">
                    @error('installation_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="installation_status">Installation status</label>
                    <select id="installation_status" name="installation_status" class="isp-form-select">
                        @foreach (\App\Support\InventoryCatalog::INSTALLATION_STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('installation_status', 'pending') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('installation_status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="isp-form-label" for="installation_location">Installation location</label>
                <input id="installation_location" type="text" name="installation_location" value="{{ old('installation_location') }}" class="isp-form-input" placeholder="Customer premises, GPS coordinates, landmark…">
                @error('installation_location')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="isp-form-label" for="installation_gps">GPS coordinates (optional)</label>
                <input id="installation_gps" type="text" name="installation_gps" value="{{ old('installation_gps') }}" class="isp-form-input" placeholder="-1.2921, 36.8219">
                @error('installation_gps')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="isp-form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="isp-form-textarea">{{ old('notes') }}</textarea>
                @error('notes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.inventory.assignments.index') }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Assign device</button>
            </div>
        </form>
    </div>
@endsection
