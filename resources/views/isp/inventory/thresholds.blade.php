@extends('layouts.admin')

@section('title', 'Low Stock Thresholds')

@section('header')
    <x-isp-page-header
        title="Low stock thresholds"
        module="Inventory"
        description="Set minimum available quantity per category. Alerts appear on the inventory dashboard."
    />
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('isp.inventory.thresholds.update') }}" class="pb-card space-y-5 p-6">
            @csrf
            @foreach (\App\Support\InventoryCatalog::CATEGORIES as $category => $label)
                <div class="flex items-center justify-between gap-4">
                    <label for="threshold_{{ $category }}" class="text-sm font-medium">{{ $label }}</label>
                    <input
                        id="threshold_{{ $category }}"
                        type="number"
                        min="0"
                        name="thresholds[{{ $category }}]"
                        value="{{ old('thresholds.'.$category, $thresholds[$category] ?? 5) }}"
                        class="isp-form-input w-28 text-right"
                    >
                </div>
            @endforeach
            <div class="flex justify-end border-t border-[var(--pb-border)] pt-4">
                <button type="submit" class="pb-btn pb-btn-primary">Save thresholds</button>
            </div>
        </form>
    </div>
@endsection
