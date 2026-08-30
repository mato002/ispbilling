@extends('layouts.admin')

@section('title', 'Edit Inventory Item')

@section('header')
    <x-isp-page-header
        title="Edit {{ $item->name }}"
        module="Inventory"
        description="Update equipment details."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.inventory.show', $item) }}" class="pb-btn">Back to item</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @include('isp.inventory.partials.nav')

    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('isp.inventory.update', $item) }}" class="pb-card space-y-5 p-6">
            @csrf
            @method('PUT')
            @include('isp.inventory.partials.form', ['item' => $item, 'purchases' => $purchases])
            <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.inventory.show', $item) }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Update item</button>
            </div>
        </form>
    </div>
@endsection
