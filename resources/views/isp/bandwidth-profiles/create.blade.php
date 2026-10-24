@extends('layouts.admin')

@section('title', 'Create bandwidth profile')

@section('header')
    <x-isp-page-header title="Create bandwidth profile" module="Network" description="Set subscriber speeds, then add optional queue and burst tuning." />
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <form action="{{ route('isp.bandwidth-profiles.store') }}" method="POST" class="pb-card space-y-6 p-6">
            @csrf
            <div>
                <label class="isp-form-label" for="name">Profile name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" class="isp-form-input" placeholder="10 Mbps Home" required>
                @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="download_limit">Download speed (Mbps)</label>
                    <input id="download_limit" type="number" step="0.1" min="0.1" name="download_limit" value="{{ old('download_limit') }}" class="isp-form-input" required>
                    @error('download_limit')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="upload_limit">Upload speed (Mbps)</label>
                    <input id="upload_limit" type="number" step="0.1" min="0.1" name="upload_limit" value="{{ old('upload_limit') }}" class="isp-form-input" required>
                    @error('upload_limit')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="-mt-3 text-xs text-slate-500">Data caps belong to service plans. IP pools and simultaneous-session limits depend on the router and access method, so they are configured in those settings.</p>

            <details class="rounded-xl border border-[var(--pb-border)] p-4">
                <summary class="cursor-pointer text-sm font-semibold">Queue and QoS settings <span class="font-normal text-slate-500">(optional)</span></summary>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="queue_type">Queue type</label>
                        <select id="queue_type" name="queue_type" class="isp-form-select" required>
                            <option value="queue-simple" @selected(old('queue_type', 'queue-simple') === 'queue-simple')>Simple queue</option>
                            <option value="queue-tree" @selected(old('queue_type') === 'queue-tree')>Queue tree</option>
                        </select>
                        @error('queue_type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="isp-form-label" for="parent_profile_id">Parent profile (grouping)</label>
                        <select id="parent_profile_id" name="parent_profile_id" class="isp-form-select">
                            <option value="">None</option>
                            @foreach ($parentProfiles as $profile)
                                <option value="{{ $profile->id }}" @selected(old('parent_profile_id') == $profile->id)>{{ $profile->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_profile_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="isp-form-label" for="priority">Priority (1 highest, 8 lowest)</label>
                        <select id="priority" name="priority" class="isp-form-select">
                            @foreach (range(1, 8) as $priority)
                                <option value="{{ $priority }}" @selected((int) old('priority', 8) === $priority)>{{ $priority }}{{ $priority === 8 ? ' (default)' : '' }}</option>
                            @endforeach
                        </select>
                        @error('priority')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">Parent profile organizes profiles in this system; RouterOS queue-tree parent and mangle rules are configured on the router.</p>
            </details>

            <details class="rounded-xl border border-[var(--pb-border)] p-4" @if ($errors->hasAny(['download_burst_limit', 'upload_burst_limit', 'download_burst_threshold', 'upload_burst_threshold', 'download_burst_time', 'upload_burst_time'])) open @endif>
                <summary class="cursor-pointer text-sm font-semibold">Burst speeds <span class="font-normal text-slate-500">(optional)</span></summary>
                <p class="mt-1 text-xs text-slate-500">Leave blank for standard speeds. Values are in Mbps and seconds.</p>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    @foreach ([
                        'download_burst_limit' => 'Download burst limit (Mbps)',
                        'upload_burst_limit' => 'Upload burst limit (Mbps)',
                        'download_burst_threshold' => 'Download threshold (Mbps)',
                        'upload_burst_threshold' => 'Upload threshold (Mbps)',
                        'download_burst_time' => 'Download burst time (seconds)',
                        'upload_burst_time' => 'Upload burst time (seconds)',
                    ] as $field => $label)
                        <div>
                            <label class="isp-form-label" for="{{ $field }}">{{ $label }}</label>
                            <input id="{{ $field }}" type="number" name="{{ $field }}" value="{{ old($field) }}" class="isp-form-input" min="{{ str_ends_with($field, '_time') ? 1 : 0.1 }}" step="{{ str_ends_with($field, '_time') ? 1 : 0.1 }}">
                            @error($field)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </details>

            <div>
                <label class="isp-form-label" for="comment">Notes <span class="font-normal text-slate-500">(optional)</span></label>
                <textarea id="comment" name="comment" rows="2" class="isp-form-input">{{ old('comment') }}</textarea>
                @error('comment')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.bandwidth-profiles.index') }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Create profile</button>
            </div>
        </form>
    </div>
@endsection
