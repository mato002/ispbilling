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

        <x-resource-index
            :filter-action="route('isp.bandwidth-profiles.index')"
            :filter-chips="$filterChips ?? []"
            :export-formats="$exportFormats ?? []"
            :paginator="$bandwidthProfiles"
            :empty="$bandwidthProfiles->isEmpty()"
            empty-title="No bandwidth profiles"
            empty-message="Create profiles to enforce speeds on the router."
            empty-cta-label="Create profile"
            :empty-cta-href="route('isp.bandwidth-profiles.create')"
        >
            <x-slot:filters>
                <x-listing-filters.select
                    name="is_active"
                    label="Status"
                    placeholder="All"
                    class="min-w-[160px]"
                    :options="['1' => 'Active', '0' => 'Inactive']"
                />
                <x-listing-filters.per-page />
            </x-slot:filters>

            <x-slot:table>
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Download</th>
                            <th>Upload</th>
                            <th>Queue</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <x-table-actions.header />
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bandwidthProfiles as $profile)
                            <x-listing-table.row :href="route('isp.bandwidth-profiles.show', $profile)">
                                <td>
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ $profile->name }}</div>
                                    @if ($profile->parentProfile)
                                        <div class="text-xs text-slate-500">Child of {{ $profile->parentProfile->name }}</div>
                                    @endif
                                </td>
                                <td>{{ $profile->formatted_download_speed }}</td>
                                <td>{{ $profile->formatted_upload_speed }}</td>
                                <td><span class="pb-pill pb-pill-info">{{ $profile->queue_type }}</span></td>
                                <td class="tabular-nums">{{ $profile->priority }}</td>
                                <td>
                                    @if ($profile->is_active)
                                        <x-status-badge status="active" />
                                    @else
                                        <x-status-badge status="inactive" />
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <x-table-actions>
                                        <x-table-actions.link :href="route('isp.bandwidth-profiles.show', $profile)" label="View" />
                                        <x-table-actions.link :href="route('isp.bandwidth-profiles.edit', $profile)" label="Edit" />
                                        @if ($profile->is_active)
                                            <x-table-actions.form
                                                :action="route('isp.bandwidth-profiles.deactivate', $profile)"
                                                label="Pause"
                                                :danger="false"
                                                onsubmit="return confirm('Deactivate?');"
                                            />
                                        @else
                                            <x-table-actions.form
                                                :action="route('isp.bandwidth-profiles.activate', $profile)"
                                                label="Activate"
                                                :danger="false"
                                                onsubmit="return confirm('Activate?');"
                                            />
                                        @endif
                                    </x-table-actions>
                                </td>
                            </x-listing-table.row>
                        @endforeach
                    </tbody>
                </x-data-table>
            </x-slot:table>
        </x-resource-index>
    </div>
@endsection
