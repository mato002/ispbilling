@extends('layouts.admin')

@section('title', 'Session Monitoring')

@section('header')
    <x-isp-page-header
        title="Session Monitoring"
        module="Network Operations"
        description="Track live hotspot and PPPoE sessions, data usage, uptime, and disconnection actions from one NOC view."
    />
@endsection

@section('content')
<div class="space-y-6">
    <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-dashboard.kpi-card title="Active Sessions" :value="number_format($stats['active_sessions'] ?? 0)" subtext="Live subscribers" iconBg="from-indigo-500 to-cyan-500">
            <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12a10 10 0 0 1 20 0"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Sessions Today" :value="number_format($stats['total_today'] ?? 0)" subtext="Daily traffic" iconBg="from-emerald-500 to-teal-500">
            <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
        <x-dashboard.kpi-card title="Bandwidth Today" :value="number_format(($stats['total_bandwidth_today'] ?? 0) / 1024 / 1024, 2).' GB'" subtext="Aggregate transfer" iconBg="from-violet-500 to-indigo-500">
            <x-slot:icon><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3 9 4-18 3 9h4"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
    </section>

    <x-filter-bar :action="route('isp.sessions.index')">
        <div class="min-w-[180px] flex-1">
            <label class="isp-form-label">Router</label>
            <select name="router_id" class="isp-form-select">
                <option value="">All Routers</option>
                @foreach($routers as $router)
                    <option value="{{ $router->id }}" @selected(request('router_id') == $router->id)>{{ $router->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[200px] flex-1">
            <label class="isp-form-label">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="isp-form-input" placeholder="Username, customer, IP">
        </div>
        <div class="min-w-[150px]">
            <label class="isp-form-label">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="isp-form-input">
        </div>
        <button type="submit" class="pb-btn pb-btn-primary">Apply</button>
        <a href="{{ route('isp.sessions.index') }}" class="pb-btn">Reset</a>
    </x-filter-bar>

    @if($sessions->isEmpty())
        <div class="pb-card">
            <x-empty-state title="No sessions found" message="No hotspot or PPPoE sessions match the current filters." />
        </div>
    @else
        <x-data-table>
            <thead>
                <tr>
                    <th>Username</th><th>Customer</th><th>Router</th><th>IP Address</th><th>Data Used</th><th>Uptime</th><th>Started</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $session)
                    <tr>
                        <td>{{ $session->username }}</td>
                        <td>{{ $session->customer->full_name ?? 'Voucher User' }}</td>
                        <td>{{ $session->router->name }}</td>
                        <td class="font-mono text-xs">{{ $session->ip_address }}</td>
                        <td>{{ $session->formatted_total_bytes }}</td>
                        <td>{{ $session->uptime_formatted }}</td>
                        <td>{{ $session->started_at->format('M d, H:i') }}</td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('isp.sessions.show', $session) }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-semibold">View</a>
                            @if($session->status === 'active')
                                <form method="POST" action="{{ route('isp.sessions.disconnect', $session) }}" class="inline ml-2">
                                    @csrf
                                    <button type="submit" class="text-rose-600 hover:text-rose-700 text-sm font-semibold">Disconnect</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
        <div class="pb-card p-4">{{ $sessions->links() }}</div>
    @endif
</div>
@endsection





