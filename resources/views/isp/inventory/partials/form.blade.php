@php
    use App\Support\InventoryCatalog;
@endphp

<div class="space-y-5">
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="isp-form-label" for="name">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="isp-form-input" placeholder="Customer CPE — Tower A" required>
            @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="isp-form-label" for="serial_number">Serial number</label>
            <input id="serial_number" type="text" name="serial_number" value="{{ old('serial_number', $item->serial_number ?? '') }}" class="isp-form-input" required>
            @error('serial_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label class="isp-form-label" for="category">Category</label>
            <select id="category" name="category" class="isp-form-select" required>
                @foreach (InventoryCatalog::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $item->category ?? 'cpe') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="isp-form-label" for="brand">Brand</label>
            <select id="brand" name="brand" class="isp-form-select">
                <option value="">— Select —</option>
                @foreach (InventoryCatalog::BRANDS as $brand)
                    <option value="{{ $brand }}" @selected(old('brand', $item->brand ?? '') === $brand)>{{ $brand }}</option>
                @endforeach
            </select>
            @error('brand')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="isp-form-label" for="model">Model</label>
            <input id="model" type="text" name="model" value="{{ old('model', $item->model ?? '') }}" class="isp-form-input" placeholder="hAP ac²">
            @error('model')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="isp-form-label" for="mac_address">MAC address (optional)</label>
            <input id="mac_address" type="text" name="mac_address" value="{{ old('mac_address', $item->mac_address ?? '') }}" class="isp-form-input" placeholder="AA:BB:CC:DD:EE:FF">
            @error('mac_address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="isp-form-label" for="location">Location</label>
            <select id="location" name="location" class="isp-form-select">
                @foreach (InventoryCatalog::LOCATIONS as $value => $label)
                    <option value="{{ $value }}" @selected(old('location', $item->location ?? 'warehouse') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('location')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label class="isp-form-label" for="purchase_price">Purchase price</label>
            <input id="purchase_price" type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $item->purchase_price ?? '') }}" class="isp-form-input">
            @error('purchase_price')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="isp-form-label" for="purchase_date">Purchase date</label>
            <input id="purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date', isset($item) && $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '') }}" class="isp-form-input">
            @error('purchase_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="isp-form-label" for="warranty_expiry">Warranty expiry</label>
            <input id="warranty_expiry" type="date" name="warranty_expiry" value="{{ old('warranty_expiry', isset($item) && $item->warranty_expiry ? $item->warranty_expiry->format('Y-m-d') : '') }}" class="isp-form-input">
            @error('warranty_expiry')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    @if (! empty($purchases))
        <div>
            <label class="isp-form-label" for="inventory_purchase_id">Link to purchase record (optional)</label>
            <select id="inventory_purchase_id" name="inventory_purchase_id" class="isp-form-select">
                <option value="">— None —</option>
                @foreach ($purchases as $purchase)
                    <option value="{{ $purchase->id }}" @selected(old('inventory_purchase_id', $item->inventory_purchase_id ?? '') == $purchase->id)>
                        {{ $purchase->vendor_name }} — {{ $purchase->purchase_date?->format('M d, Y') }}
                        @if ($purchase->invoice_number) ({{ $purchase->invoice_number }}) @endif
                    </option>
                @endforeach
            </select>
            @error('inventory_purchase_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    @endif

    <div>
        <label class="isp-form-label" for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="3" class="isp-form-textarea" placeholder="Condition, accessories, supplier remarks…">{{ old('notes', $item->notes ?? '') }}</textarea>
        @error('notes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>
