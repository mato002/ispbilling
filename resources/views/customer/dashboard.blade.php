@extends('customer.layout')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Hello, {{ $customer->full_name }}</h2>
        <p class="mt-1 text-sm text-slate-500">Account status: <span class="font-medium text-slate-800">{{ $customer->status }}</span></p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active plan</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">
                {{ $customer->activeSubscription()?->plan?->name ?? 'No active plan' }}
            </p>
            @if($activeSession ?? null)
                <p class="mt-1 text-sm text-emerald-700">Online session active</p>
            @else
                <p class="mt-1 text-sm text-slate-500">No active hotspot session</p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Need help?</p>
            <p class="mt-2 text-sm text-slate-600">Open a support ticket and our team will follow up.</p>
            <a href="{{ route('customer.tickets.create') }}" class="mt-3 inline-flex rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Contact support
            </a>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">Recent invoices</h3>
            <a href="{{ route('customer.invoices') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
        </div>
        @if(($invoices ?? collect())->count())
            <ul class="divide-y divide-slate-100">
                @foreach($invoices as $inv)
                    <li class="flex items-center justify-between py-2 text-sm">
                        <span class="font-medium text-slate-800">{{ $inv->invoice_number }}</span>
                        <span class="text-slate-600">{{ number_format($inv->total_amount, 2) }} · {{ $inv->status }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-slate-500">No invoices yet.</p>
        @endif
    </div>
</div>
@endsection
