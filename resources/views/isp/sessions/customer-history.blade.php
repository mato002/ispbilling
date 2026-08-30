@extends('layouts.admin')

@section('title', 'Session history · '.$customer->full_name)

@section('header')
    <x-isp-page-header
        :title="'Sessions · '.$customer->full_name"
        module="Network"
        description="Historical hotspot and connection sessions for this customer."
    >
        <x-slot name="actions">
            <a href="{{ route('isp.sessions.index') }}" class="pb-btn">All sessions</a>
            <a href="{{ route('isp.customers.show', $customer) }}" class="pb-btn pb-btn-primary">Customer profile</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="pb-card p-4 sm:p-5 text-sm text-slate-600 dark:text-slate-300">
            <p class="font-semibold text-slate-900 dark:text-white">{{ $customer->full_name }}</p>
            <p class="mt-1">
                {{ $customer->username ?? $customer->phone_number }}
                · <x-status-badge :status="$customer->status" />
            </p>
        </div>

        @if ($sessions->isEmpty())
            <div class="pb-card p-0">
                <x-empty-state title="No sessions" message="This customer has no recorded sessions yet." />
            </div>
        @else
            <div class="pb-card overflow-hidden p-0">
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Started</th>
                            <th>Router</th>
                            <th>IP / MAC</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <x-table-actions.header />
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td class="text-sm">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ $session->started_at?->format('M d, Y H:i') }}</div>
                                    @if ($session->ended_at)
                                        <div class="text-xs text-slate-500">Ended {{ $session->ended_at->format('M d H:i') }}</div>
                                    @endif
                                </td>
                                <td class="text-sm">{{ $session->router?->name ?? '—' }}</td>
                                <td class="font-mono text-xs">
                                    <div>{{ $session->ip_address }}</div>
                                    <div class="text-slate-500">{{ $session->mac_address ?? '—' }}</div>
                                </td>
                                <td class="text-sm tabular-nums">{{ $session->formatted_total_bytes ?? '—' }}</td>
                                <td><x-status-badge :status="$session->status" /></td>
                                <td class="text-right">
                                    <x-table-actions>
                                        <x-table-actions.link :href="route('isp.sessions.show', $session)" label="View" />
                                    </x-table-actions>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            </div>

            <div>{{ $sessions->withQueryString()->links() }}</div>
        @endif
    </div>
@endsection
