@extends('layouts.admin')

@section('title', 'Notifications')

@section('header')
    <x-isp-page-header
        title="Notifications"
        module="Operations"
        description="ISP alerts, payments, subscriptions, and system messages."
    >
        <x-slot name="actions">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('isp.notifications.mark-all-read') }}" class="inline">
                    @csrf
                    <button type="submit" class="pb-btn">Mark all read</button>
                </form>
            @endif
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <x-resource-index
        :filter-action="route('isp.notifications.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$notifications"
        :empty="$notifications->isEmpty()"
        empty-title="No notifications"
        :empty-message="request()->anyFilled(['status', 'type', 'date_from', 'date_to']) ? 'No notifications match your filters.' : 'You are all caught up.'"
    >
        <x-slot:filters>
            <div class="min-w-[160px]">
                <label class="isp-form-label">Status</label>
                <select name="status" class="isp-form-select">
                    <option value="">All ({{ $totalCount }})</option>
                    <option value="unread" @selected(request('status') === 'unread')>Unread ({{ $unreadCount }})</option>
                    <option value="read" @selected(request('status') === 'read')>Read</option>
                </select>
            </div>
            <x-listing-filters.select
                name="type"
                label="Type"
                placeholder="All"
                :options="[
                    'payment' => 'Payment',
                    'subscription' => 'Subscription',
                    'system' => 'System',
                    'hotspot' => 'Hotspot',
                    'success' => 'Success',
                    'warning' => 'Warning',
                    'error' => 'Error',
                    'info' => 'Info',
                ]"
            />
            <x-listing-filters.date name="date_from" label="From" />
            <x-listing-filters.date name="date_to" label="To" />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>State</th>
                        <th>Message</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notifications as $notification)
                        @php
                            $badge = match($notification->type) {
                                'success' => 'pb-pill-ok',
                                'warning' => 'pb-pill-warn',
                                'error' => 'pb-pill-bad',
                                'payment' => 'pb-pill-info',
                                'subscription' => '',
                                'system' => '',
                                'hotspot' => 'pb-pill-cyan',
                                default => 'pb-pill-info',
                            };
                        @endphp
                        <tr class="{{ $notification->is_read ? '' : 'bg-indigo-50/50 dark:bg-indigo-950/25' }}">
                            <td class="whitespace-nowrap text-sm text-slate-500">{{ $notification->created_at->format('M j, H:i') }}</td>
                            <td class="font-semibold text-slate-900 dark:text-white">{{ $notification->title }}</td>
                            <td><span class="pb-pill {{ $badge }}">{{ ucfirst($notification->type) }}</span></td>
                            <td>
                                @if($notification->is_read)
                                    <span class="text-slate-400 text-sm">Read</span>
                                @else
                                    <span class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">New</span>
                                @endif
                            </td>
                            <td class="max-w-xs truncate text-sm text-slate-600 dark:text-slate-300" title="{{ $notification->message }}">{{ $notification->message }}</td>
                            <td class="text-right whitespace-nowrap">
                                @if(!$notification->is_read)
                                    <form method="POST" action="{{ route('isp.notifications.mark-read', $notification) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Mark read</button>
                                    </form>
                                @else
                                    <span class="text-slate-400 text-sm">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
