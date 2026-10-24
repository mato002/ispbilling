@extends('layouts.admin')

@section('title', 'Edit '.$bandwidthProfile->name)

@section('header')
    <x-isp-page-header
        :title="'Edit '.$bandwidthProfile->name"
        module="Network"
        description="Update subscriber speeds and optional queue tuning. Redeploy to routers after saving."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.bandwidth-profiles.show', $bandwidthProfile) }}" class="pb-btn">View profile</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('isp.bandwidth-profiles.update', $bandwidthProfile) }}" class="pb-card space-y-5 p-6">
            @csrf
            @method('PUT')

            <div>
                <label class="isp-form-label" for="name">Profile name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $bandwidthProfile->name) }}" class="isp-form-input" required>
                @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="download_limit">Download (Mbps)</label>
                    <input id="download_limit" type="number" step="0.1" min="0.1" name="download_limit"
                           value="{{ old('download_limit', $bandwidthProfile->download_limit / 1000) }}"
                           class="isp-form-input" required>
                    @error('download_limit')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="isp-form-label" for="upload_limit">Upload (Mbps)</label>
                    <input id="upload_limit" type="number" step="0.1" min="0.1" name="upload_limit"
                           value="{{ old('upload_limit', $bandwidthProfile->upload_limit / 1000) }}"
                           class="isp-form-input" required>
                    @error('upload_limit')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="-mt-3 text-xs text-slate-500">Data caps belong to service plans. IP pools and simultaneous-session limits depend on the router and access method, so they are configured in those settings.</p>

            <details class="rounded-xl border border-[var(--pb-border)] p-4">
                <summary class="cursor-pointer text-sm font-semibold">Queue and QoS settings</summary>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="isp-form-label" for="queue_type">Queue type</label>
                    <select id="queue_type" name="queue_type" class="isp-form-select" required>
                        <option value="queue-simple" @selected(old('queue_type', $bandwidthProfile->queue_type) === 'queue-simple')>Queue simple</option>
                        <option value="queue-tree" @selected(old('queue_type', $bandwidthProfile->queue_type) === 'queue-tree')>Queue tree</option>
                    </select>
                </div>
                <div>
                    <label class="isp-form-label" for="parent_profile_id">Parent profile (grouping)</label>
                    <select id="parent_profile_id" name="parent_profile_id" class="isp-form-select">
                        <option value="">None</option>
                        @foreach ($parentProfiles as $profile)
                            <option value="{{ $profile->id }}" @selected(old('parent_profile_id', $bandwidthProfile->parent_profile_id) == $profile->id)>
                                {{ $profile->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="isp-form-label" for="priority">Priority (1 highest, 8 lowest)</label>
                    <select id="priority" name="priority" class="isp-form-select">
                        @foreach (range(1, 8) as $priority)
                            <option value="{{ $priority }}" @selected((int) old('priority', $bandwidthProfile->priority ?? 8) === $priority)>{{ $priority }}{{ $priority === 8 ? ' (default)' : '' }}</option>
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
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="isp-form-label" for="download_burst_limit">Download burst (Mbps)</label>
                        <input id="download_burst_limit" type="number" step="0.1" min="1" name="download_burst_limit"
                               value="{{ old('download_burst_limit', $bandwidthProfile->download_burst_limit ? $bandwidthProfile->download_burst_limit / 1000 : '') }}"
                               class="isp-form-input">
                    </div>
                    <div>
                        <label class="isp-form-label" for="upload_burst_limit">Upload burst (Mbps)</label>
                        <input id="upload_burst_limit" type="number" step="0.1" min="1" name="upload_burst_limit"
                               value="{{ old('upload_burst_limit', $bandwidthProfile->upload_burst_limit ? $bandwidthProfile->upload_burst_limit / 1000 : '') }}"
                               class="isp-form-input">
                    </div>
                    <div>
                        <label class="isp-form-label" for="download_burst_threshold">Download threshold (Mbps)</label>
                        <input id="download_burst_threshold" type="number" step="0.1" min="1" name="download_burst_threshold"
                               value="{{ old('download_burst_threshold', $bandwidthProfile->download_burst_threshold ? $bandwidthProfile->download_burst_threshold / 1000 : '') }}"
                               class="isp-form-input">
                    </div>
                    <div>
                        <label class="isp-form-label" for="upload_burst_threshold">Upload threshold (Mbps)</label>
                        <input id="upload_burst_threshold" type="number" step="0.1" min="1" name="upload_burst_threshold"
                               value="{{ old('upload_burst_threshold', $bandwidthProfile->upload_burst_threshold ? $bandwidthProfile->upload_burst_threshold / 1000 : '') }}"
                               class="isp-form-input">
                    </div>
                    <div>
                        <label class="isp-form-label" for="download_burst_time">Download burst time (s)</label>
                        <input id="download_burst_time" type="number" min="1" name="download_burst_time"
                               value="{{ old('download_burst_time', $bandwidthProfile->download_burst_time) }}"
                               class="isp-form-input">
                    </div>
                    <div>
                        <label class="isp-form-label" for="upload_burst_time">Upload burst time (s)</label>
                        <input id="upload_burst_time" type="number" min="1" name="upload_burst_time"
                               value="{{ old('upload_burst_time', $bandwidthProfile->upload_burst_time) }}"
                               class="isp-form-input">
                    </div>
                </div>
            </details>

            <div>
                <label class="isp-form-label" for="comment">Comment</label>
                <input id="comment" type="text" name="comment" value="{{ old('comment', $bandwidthProfile->comment) }}" class="isp-form-input">
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--pb-border)] pt-4">
                <a href="{{ route('isp.bandwidth-profiles.show', $bandwidthProfile) }}" class="pb-btn">Cancel</a>
                <button type="submit" class="pb-btn pb-btn-primary">Save changes</button>
            </div>
        </form>
    </div>
@endsection
