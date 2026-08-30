@extends('layouts.admin')

@section('title', 'Record Purchase')

@section('header')
    <x-isp-page-header
        title="Record purchase"
        module="Inventory"
        description="Log a vendor invoice. Link individual items when adding stock."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.inventory.purchases') }}" class="pb-btn">Back to purchases</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('isp.inventory.purchases.store') }}" class="pb-card space-y-5 p-6">
            @csrf
            <div>
                <label class="isp-form-label" for="vendor_name">Vendor name</label>
                <input id="vendor_name" type="text" name="vendor_name" value="{{ old('vendor_name') }}" class="isp-form-input" required>
                @error('vendor_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="invoice_number">Invoice number</label>
                    <input id="invoice_number" type="text" name="invoice_number" value="{{ old('invoice_number') }}" class="isp-form-input">
                    @error('invoice_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="purchase_date">Purchase date</label>
                    <input id="purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date', now()->format('Y-m-d')) }}" class="isp-form-input" required>
                    @error('purchase_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="isp-form-label" for="total_cost">Total cost</label>
                <input id="total_cost" type="number" step="0.01" min="0" name="total_cost" value="{{ old('total_cost') }}" class="isp-form-input">
                @error('total_cost')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="isp-form-textarea">{{ old('notes') }}</textarea>
                @error('notes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.inventory.purchases') }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Save purchase</button>
            </div>
        </form>
    </div>
@endsection
