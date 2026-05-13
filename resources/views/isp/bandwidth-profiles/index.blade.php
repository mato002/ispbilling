@extends('layouts.admin')

@section('title', 'Bandwidth Profiles')

@section('header')
    <x-isp-page-header
        title="Bandwidth profiles"
        module="Network"
        description="MikroTik queue profiles for download, upload, burst, and priority."
        primaryLabel="Create profile"
        :primaryHref="route('isp.bandwidth-profiles.create')"
    />
@endsection

@section('content')
<div class="space-y-6">
    <div class="pb-card p-4 sm:p-5 text-sm text-slate-600 dark:text-slate-300">
        <p class="font-semibold text-slate-900 dark:text-white">QoS tiers</p>
        <p class="mt-1">Define speeds and queue types, then reference profiles from plans and hotspot user profiles.</p>
    </div>

    <x-filter-bar :action="route('isp.bandwidth-profiles.index')">
        <div class="min-w-[160px]">
            <label class="isp-form-label">Status</label>
            <select name="is_active" class="isp-form-select">
                <option value="">All</option>
                <option value="1" @selected(request('is_active') === '1')>Active</option>
                <option value="0" @selected(request('is_active') === '0')>Inactive</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.bandwidth-profiles.index') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($bandwidthProfiles->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state
                title="No bandwidth profiles"
                message="Create profiles to enforce speeds on the router."
                ctaLabel="Create profile"
                :ctaHref="route('isp.bandwidth-profiles.create')"
            />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Profile</th>
                    <th>Download</th>
                    <th>Upload</th>
                    <th>Queue</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bandwidthProfiles as $profile)
                    <tr>
                        <td>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ $profile->name }}</div>
                            @if($profile->parentProfile)
                                <div class="text-xs text-slate-500">Child of {{ $profile->parentProfile->name }}</div>
                            @endif
                        </td>
                        <td>{{ $profile->formatted_download_speed }}</td>
                        <td>{{ $profile->formatted_upload_speed }}</td>
                        <td><span class="pb-pill pb-pill-info">{{ $profile->queue_type }}</span></td>
                        <td class="tabular-nums">{{ $profile->priority }}</td>
                        <td>
                            @if($profile->is_active)
                                <x-status-badge status="active" />
                            @else
                                <x-status-badge status="inactive" />
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('isp.bandwidth-profiles.show', $profile) }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">View</a>
                            <a href="{{ route('isp.bandwidth-profiles.edit', $profile) }}" class="ml-3 text-sm font-semibold text-slate-600 dark:text-slate-300">Edit</a>
                            @if($profile->is_active)
                                <form action="{{ route('isp.bandwidth-profiles.deactivate', $profile) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Deactivate?');">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold text-amber-600 dark:text-amber-400">Pause</button>
                                </form>
                            @else
                                <form action="{{ route('isp.bandwidth-profiles.activate', $profile) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Activate?');">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Activate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $bandwidthProfiles->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
