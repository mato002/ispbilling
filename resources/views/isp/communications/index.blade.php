@extends('layouts.admin')

@section('title', 'Communications')

@section('header')
    <x-isp-page-header
        title="Communications & Broadcast"
        module="Messaging"
        description="Queue-based SMS and email campaigns for subscribers. All delivery runs through the message worker — never inline from this UI."
        primaryLabel="New campaign"
        :primaryHref="route('isp.communications.create')"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.communications.groups.index') }}" class="pb-btn">SMS groups</a>
            <a href="{{ route('isp.settings.notifications') }}" class="pb-btn">SMS settings</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.communications.index')"
        :filter-chips="$filterChips ?? []"
        :paginator="$campaigns"
        :empty="$campaigns->isEmpty()"
        empty-title="No campaigns yet"
        empty-message="Create a broadcast to message subscribers by plan, router, status, or custom SMS group."
        empty-cta-label="Create campaign"
        :empty-cta-href="route('isp.communications.create')"
    >
        <x-slot:filters>
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All statuses"
                :options="[
                    'draft' => 'Draft',
                    'scheduled' => 'Scheduled',
                    'sending' => 'Sending',
                    'sent' => 'Sent',
                    'failed' => 'Failed',
                ]"
            />
            <x-listing-filters.per-page :default="20" />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Queued</th>
                        <th>Sent</th>
                        <th>Failed</th>
                        <th>Scheduled</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaigns as $campaign)
                        <x-listing-table.row :href="route('isp.communications.show', $campaign)">
                            <td>
                                <a href="{{ route('isp.communications.show', $campaign) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">
                                    {{ $campaign->title }}
                                </a>
                                <p class="text-xs text-slate-500">by {{ $campaign->createdBy?->name ?? 'System' }}</p>
                            </td>
                            <td><span class="pb-pill">{{ strtoupper($campaign->channel) }}</span></td>
                            <td>
                                @php
                                    $statusMap = [
                                        'draft' => 'info',
                                        'scheduled' => 'warning',
                                        'sending' => 'active',
                                        'sent' => 'success',
                                        'failed' => 'error',
                                    ];
                                @endphp
                                <x-status-badge :status="ucfirst($campaign->status)" />
                            </td>
                            <td>{{ number_format($campaign->queued_count) }}</td>
                            <td class="text-emerald-700 dark:text-emerald-300">{{ number_format($campaign->sent_count) }}</td>
                            <td class="text-rose-700 dark:text-rose-300">{{ number_format($campaign->failed_count) }}</td>
                            <td class="whitespace-nowrap text-sm text-slate-600">
                                {{ $campaign->scheduled_at?->format('M j, Y H:i') ?? '—' }}
                            </td>
                            <x-table-actions>
                                <x-table-actions.link :href="route('isp.communications.show', $campaign)" label="Details" />
                            </x-table-actions>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
