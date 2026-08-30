@extends('layouts.admin')

@section('title', 'Record Outage')

@section('header')
    <x-isp-page-header
        title="Record outage"
        module="Network"
        description="Document a downtime event. Compensation is never applied automatically — you control it from the outage detail page."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.outages.index') }}" class="pb-btn">Back to outages</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('isp.outages.store') }}" class="pb-card space-y-5 p-6">
            @csrf

            <div>
                <label class="isp-form-label" for="title">Title</label>
                <input id="title" type="text" name="title" value="{{ old('title') }}" class="isp-form-input" placeholder="Tower 3 fiber cut" required>
                @error('title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="isp-form-label" for="description">Description</label>
                <textarea id="description" name="description" rows="4" class="isp-form-textarea" placeholder="What failed, impact, ETA…">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="isp-form-label" for="router_id">Affected router (optional)</label>
                <select id="router_id" name="router_id" class="isp-form-select">
                    <option value="">— No specific router —</option>
                    @foreach ($routers as $router)
                        <option value="{{ $router->id }}" @selected(old('router_id') == $router->id)>
                            {{ $router->name }} ({{ $router->ip_address }})
                        </option>
                    @endforeach
                </select>
@error('router_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="started_at">Start time</label>
                    <input id="started_at" type="datetime-local" name="started_at" value="{{ old('started_at', now()->format('Y-m-d\TH:i')) }}" class="isp-form-input" required>
                    @error('started_at')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="ended_at">End time (optional)</label>
                    <input id="ended_at" type="datetime-local" name="ended_at" value="{{ old('ended_at') }}" class="isp-form-input">
                    @error('ended_at')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="isp-form-label" for="status">Status</label>
                <select id="status" name="status" class="isp-form-select">
                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                    <option value="resolved" @selected(old('status') === 'resolved')>Resolved</option>
                </select>
                @error('status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.outages.index') }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Save outage</button>
            </div>
        </form>
    </div>
@endsection
