@extends('hotspot.layout')

@section('content')
<div class="max-w-full mx-auto px-4">
    <div class="text-center mb-6">
        <div class="inline-block p-3 bg-blue-100 rounded-full mb-4">
            <svg class="w-12 h-12 sm:w-16 sm:h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
        </div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Complete Purchase</h2>
        <p class="text-gray-600">Get connected instantly</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Package Details</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Package:</span>
                <span class="font-semibold text-sm sm:text-base">{{ $plan->name }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Price:</span>
                <span class="font-bold text-blue-600 text-lg sm:text-xl">Ksh {{ number_format($plan->price, 0) }}</span>
            </div>
            @if($plan->data_limit_mb)
            <div class="flex justify-between">
                <span class="text-gray-600">Data:</span>
                <span>{{ round($plan->data_limit_mb/1024, 1) }} GB</span>
            </div>
            @endif
            @if($plan->download_speed_kbps)
            <div class="flex justify-between">
                <span class="text-gray-600">Speed:</span>
                <span>{{ round($plan->download_speed_kbps/1024) }} Mbps</span>
            </div>
            @endif
            @if($plan->duration_days)
            <div class="flex justify-between">
                <span class="text-gray-600">Validity:</span>
                <span>{{ $plan->duration_days }} days</span>
            </div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('hotspot.process-purchase', ['plan' => $plan->id]) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="router_ip" value="{{ request()->ip() }}">
        
        <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
            <input type="text" id="full_name" name="full_name" required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="John Doe">
        </div>

        <div>
            <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
            <input type="tel" id="phone_number" name="phone_number" required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="07XX XXX XXX">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email (Optional)</label>
            <input type="email" id="email" name="email"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="john@example.com">
        </div>

        <button type="submit" 
                class="w-full py-4 px-4 sm:px-6 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg shadow-lg hover:from-blue-700 hover:to-indigo-700 transition duration-150 transform hover:scale-105 text-sm sm:text-base">
            🚀 Proceed to Payment
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('hotspot.splash', ['router_ip' => request()->ip()]) }}" 
           class="text-blue-600 hover:text-blue-800 font-medium">
            ← Back to Packages
        </a>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-200">
        <p class="text-xs text-gray-500 text-center">
            Payment will be processed via M-PESA. You'll receive an STK push to complete the transaction.
        </p>
    </div>
</div>
@endsection
