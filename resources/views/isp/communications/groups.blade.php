@extends('layouts.admin')

@section('title', 'SMS groups')

@section('header')
    <x-isp-page-header
        title="SMS groups"
        module="Communications"
        description="Reusable subscriber segments for broadcast campaigns."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.communications.index') }}" class="pb-btn">Campaigns</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-form-section title="Create group">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ route('isp.communications.groups.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="isp-form-label" for="name">Group name</label>
                    <input id="name" name="name" class="isp-form-input" required maxlength="255" value="{{ old('name') }}">
                </div>
                <div>
                    <label class="isp-form-label" for="description">Description</label>
                    <input id="description" name="description" class="isp-form-input" maxlength="1000" value="{{ old('description') }}">
                </div>
                <div>
                    <label class="isp-form-label" for="customer_ids">Members</label>
                    <select id="customer_ids" name="customer_ids[]" class="isp-form-select" multiple size="10">
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->full_name }} — {{ $customer->phone_number }}</option>
                        @endforeach
                    </select>
</div>
                <button type="submit" class="pb-btn pb-btn-primary">Create group</button>
            </form>
        </x-form-section>

        <x-form-section title="Existing groups">
            @if ($groups->isEmpty())
                <p class="text-sm text-slate-500">No groups yet.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($groups as $group)
                        <li class="flex items-start justify-between gap-3 rounded-lg border border-[var(--pb-border)] p-3">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $group->name }}</p>
                                @if ($group->description)
                                    <p class="text-xs text-slate-500">{{ $group->description }}</p>
                                @endif
                                <p class="mt-1 text-xs text-slate-600">{{ $group->customers_count }} member(s)</p>
                            </div>
                            <form method="POST" action="{{ route('isp.communications.groups.destroy', $group) }}" onsubmit="return confirm('Delete this group?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="pb-btn text-xs text-rose-700">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-form-section>
    </div>
@endsection
