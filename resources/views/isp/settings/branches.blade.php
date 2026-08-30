@extends('layouts.admin')

@section('title', 'Branches · ISP System Settings')

@section('header')
    @include('isp.settings.partials.shell-header')
@endsection

@section('content')
    @php
        $rows = old('branch_name') !== null
            ? collect(old('branch_name', []))->keys()->map(fn ($i) => [
                'name' => old('branch_name.'.$i, ''),
                'code' => old('branch_code.'.$i, ''),
                'location' => old('branch_location.'.$i, ''),
                'phone' => old('branch_phone.'.$i, ''),
                'is_active' => (bool) old('branch_active.'.$i, false),
            ])->values()->all()
            : collect($branches)->map(fn ($b) => [
                'name' => $b['name'] ?? '',
                'code' => $b['code'] ?? '',
                'location' => $b['location'] ?? '',
                'phone' => $b['phone'] ?? '',
                'is_active' => ! empty($b['is_active']),
            ])->values()->all();
        if ($rows === []) {
            $rows = [['name' => '', 'code' => '', 'location' => '', 'phone' => '', 'is_active' => true]];
        }
    @endphp

    <x-isp-settings-workspace
        meta-title="Branches &amp; sites"
>
        <form method="POST" action="{{ route('isp.settings.branches.update') }}" x-data='{ rows: @json($rows), saving: false }' @submit="saving = true">
            @csrf
            <div class="pb-card isp-settings-panel overflow-hidden p-0">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--pb-border)] px-4 py-3 sm:px-5">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Branch footprint</h3>
</div>
                    <button type="button" class="pb-btn pb-btn-primary !py-1.5 !text-xs" @click="rows.push({ name: '', code: '', location: '', phone: '', is_active: true })">Add branch</button>
                </div>
                <div class="overflow-x-auto p-4 sm:p-5">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="mb-4 grid gap-3 rounded-xl border border-[var(--pb-border)] p-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="isp-form-label">Name</label>
                                <input type="text" name="branch_name[]" x-model="row.name" class="isp-form-input" placeholder="Nairobi CBD" />
                            </div>
                            <div>
                                <label class="isp-form-label">Code</label>
                                <input type="text" name="branch_code[]" x-model="row.code" class="isp-form-input font-mono text-sm" placeholder="NRB-01" />
                            </div>
                            <div>
                                <label class="isp-form-label">Location</label>
                                <input type="text" name="branch_location[]" x-model="row.location" class="isp-form-input" placeholder="City / estate" />
                            </div>
                            <div>
                                <label class="isp-form-label">Phone</label>
                                <input type="text" name="branch_phone[]" x-model="row.phone" class="isp-form-input" placeholder="+254…" />
                            </div>
                            <div class="flex items-center justify-between sm:col-span-2 lg:col-span-4">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="branch_active[]" :value="index" class="h-4 w-4 rounded border-slate-300 text-indigo-600" :checked="row.is_active" @change="row.is_active = $event.target.checked" />
                                    <span class="font-medium text-slate-700 dark:text-slate-200">Active</span>
                                </label>
                                <button type="button" class="text-sm font-semibold text-rose-600 hover:underline dark:text-rose-400" @click="rows.splice(index, 1)" x-show="rows.length > 1">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            @include('isp.settings.partials.save-footer', ['cancelRoute' => 'isp.settings.branches', 'saveLabel' => 'Save branches'])
        </form>
    </x-isp-settings-workspace>
@endsection
