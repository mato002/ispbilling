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
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <x-filter-bar :action="route('isp.notifications.index')">
        <div class="min-w-[160px]">
            <label class="isp-form-label">Status</label>
            <select name="status" class="isp-form-select">
                <option value="">All ({{ $totalCount }})</option>
                <option value="unread" @selected(request('status') === 'unread')>Unread ({{ $unreadCount }})</option>
                <option value="read" @selected(request('status') === 'read')>Read</option>
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="isp-form-label">Type</label>
            <select name="type" class="isp-form-select">
                <option value="">All</option>
                <option value="payment" @selected(request('type') === 'payment')>Payment</option>
                <option value="subscription" @selected(request('type') === 'subscription')>Subscription</option>
                <option value="system" @selected(request('type') === 'system')>System</option>
                <option value="hotspot" @selected(request('type') === 'hotspot')>Hotspot</option>
                <option value="success" @selected(request('type') === 'success')>Success</option>
                <option value="warning" @selected(request('type') === 'warning')>Warning</option>
                <option value="error" @selected(request('type') === 'error')>Error</option>
                <option value="info" @selected(request('type') === 'info')>Info</option>
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="isp-form-label">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="isp-form-input">
        </div>
        <div class="min-w-[130px]">
            <label class="isp-form-label">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="isp-form-input">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
            <a href="{{ route('isp.notifications.index') }}" class="pb-btn">Reset</a>
        </div>
    </x-filter-bar>

    @if($notifications->isEmpty())
        <div class="pb-card p-0">
            <x-empty-state
                title="No notifications"
                :message="request()->anyFilled(['status', 'type', 'date_from', 'date_to']) ? 'No notifications match your filters.' : 'You are all caught up.'"
            />
        </div>
    @else
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
        @if($notifications->hasPages())
            <div class="pb-card p-4">{{ $notifications->withQueryString()->links() }}</div>
        @endif
    @endif
</div>
@endsection
