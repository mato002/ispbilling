@extends('hotspot.layout')

@section('content')
<div class="text-center">
    <div class="mb-8">
        <div class="inline-block p-4 bg-green-100 rounded-full mb-4">
            <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Access Granted</h2>
        <p class="text-sm text-gray-600">
            Your payment has been received and your internet access is now active.
        </p>
    </div>

    @if($customer ?? false)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 text-left">
            <p class="text-sm text-gray-700">
                <span class="font-semibold">Customer:</span>
                {{ $customer->full_name }} ({{ $customer->username }})
            </p>
            <p class="text-sm text-gray-700 mt-1">
                <span class="font-semibold">Phone:</span>
                {{ $customer->phone_number }}
            </p>
        </div>
    @endif

    @if($payment ?? false)
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6 text-left">
            <p class="text-sm text-gray-700">
                <span class="font-semibold">Amount Paid:</span>
                {{ number_format($payment->amount, 2) }} {{ $payment->currency ?? '' }}
            </p>
            <p class="text-sm text-gray-700 mt-1">
                <span class="font-semibold">Reference:</span>
                {{ $payment->payment_reference }}
            </p>
        </div>
    @endif

    <p class="text-xs text-gray-500 mb-6">
        You can now open a new tab and browse the internet. This page is just for testing the flow.
    </p>

    <button onclick="window.close();" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Close Window
    </button>
</div>
@endsection






