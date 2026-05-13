@extends('layouts.admin')

@section('title', 'Session Details')

@section('header')
    <x-isp-page-header title="Session Details" module="Network Operations" description="Inspect session identity, usage metrics, and linked customer context." />
@endsection

@section('content')
<div class="space-y-6">
    <a href="{{ route('isp.sessions.index') }}" class="pb-btn">Back to Sessions</a>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <x-form-section title="Session Information">
            <dl class="space-y-3">
                <div><dt class="text-sm text-slate-500">Username</dt><dd class="text-lg font-semibold text-slate-900 dark:text-white">{{ $session->username }}</dd></div>
                <div><dt class="text-sm text-slate-500">Status</dt><dd><x-status-badge :status="$session->status" /></dd></div>
                <div><dt class="text-sm text-slate-500">IP Address</dt><dd class="font-mono text-sm">{{ $session->ip_address }}</dd></div>
                <div><dt class="text-sm text-slate-500">MAC Address</dt><dd class="font-mono text-sm">{{ $session->mac_address ?? 'N/A' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Router</dt><dd class="text-sm text-slate-900 dark:text-white">{{ $session->router->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Started</dt><dd class="text-sm text-slate-900 dark:text-white">{{ $session->started_at->format('M d, Y H:i:s') }}</dd></div>
                @if($session->ended_at)
                    <div><dt class="text-sm text-slate-500">Ended</dt><dd class="text-sm text-slate-900 dark:text-white">{{ $session->ended_at->format('M d, Y H:i:s') }}</dd></div>
                @endif
            </dl>
        </x-form-section>
        <x-form-section title="Usage Statistics">
            <dl class="space-y-3">
                <div><dt class="text-sm text-slate-500">Data Uploaded</dt><dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ $session->formatted_uploaded }}</dd></div>
                <div><dt class="text-sm text-slate-500">Data Downloaded</dt><dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ $session->formatted_downloaded }}</dd></div>
                <div><dt class="text-sm text-slate-500">Total Data</dt><dd class="text-sm font-semibold text-indigo-600 dark:text-indigo-300">{{ $session->formatted_total_bytes }}</dd></div>
                <div><dt class="text-sm text-slate-500">Uptime</dt><dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ $session->uptime_formatted }}</dd></div>
            </dl>
        </x-form-section>
    </div>
    @if($session->customer)
        <x-form-section title="Customer Information">
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Name</dt><dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ $session->customer->full_name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Phone</dt><dd class="text-sm text-slate-900 dark:text-white">{{ $session->customer->phone_number }}</dd></div>
                <div><dt class="text-sm text-slate-500">Status</dt><dd><x-status-badge :status="$session->customer->status" /></dd></div>
                <div><dt class="text-sm text-slate-500">Connection Type</dt><dd><x-status-badge :status="strtolower($session->customer->connection_type)" /></dd></div>
            </dl>
            <a href="{{ route('isp.customers.show', $session->customer) }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-700">View Customer Details</a>
        </x-form-section>
    @endif
    @if($session->status === 'active')
        <div class="pb-card p-5">
            <form method="POST" action="{{ route('isp.sessions.disconnect', $session) }}">
                @csrf
                <button type="submit" class="pb-btn bg-rose-600 border-rose-600 text-white hover:text-white hover:bg-rose-700">Force Disconnect</button>
            </form>
        </div>
    @endif
</div>
@endsection





