@extends('layouts.admin')

@section('title', 'Add Inventory Item')

@section('header')
    <x-isp-page-header
        title="Add inventory item"
        module="Inventory"
        description="Register new equipment in stock."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.inventory.index') }}" class="pb-btn">Back to inventory</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('isp.inventory.store') }}" class="pb-card space-y-5 p-6">
            @csrf
            @include('isp.inventory.partials.form', ['purchases' => $purchases])
            <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.inventory.index') }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Save item</button>
            </div>
        </form>
    </div>
@endsection
