@extends('hotspot.layout')

@section('content')
<div>
    <div class="text-center mb-6">
        <div class="inline-block p-3 bg-yellow-100 rounded-full mb-4">
            <svg class="w-12 h-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2m0 0V5.5A2.5 2.5 0 1016 8h-2m2 0v13m2-13V10a2 2 0 10-2 0"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Redeem Voucher</h2>
        <p class="text-sm text-gray-600">Enter your voucher code to get internet access</p>
    </div>

    <form method="POST" action="{{ route('hotspot.process-voucher') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="router_ip" value="{{ request()->get('router_ip') ?? request()->ip() }}">

        <div>
            <label for="voucher_code" class="block text-sm font-medium text-gray-700 mb-2">
                Voucher Code
            </label>
            <input id="voucher_code" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-lg font-mono tracking-widest uppercase" 
                   type="text" 
                   name="voucher_code" 
                   value="{{ old('voucher_code') }}" 
                   required 
                   autofocus 
                   maxlength="12"
                   placeholder="XXXXXXXXXXXX"
                   oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
            <p class="mt-2 text-xs text-gray-500">Enter the 12-character voucher code</p>
        </div>

        <div>
            <button type="submit" 
                    class="w-full py-3 px-4 bg-gradient-to-r from-yellow-500 to-orange-500 text-white font-semibold rounded-lg shadow-lg hover:from-yellow-600 hover:to-orange-600 transition duration-150">
                Redeem & Connect
            </button>
        </div>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('hotspot.splash', ['router_ip' => request()->get('router_ip') ?? request()->ip()]) }}" 
           class="text-sm text-gray-600 hover:text-gray-700">
            ← Back to Home
        </a>
    </div>
</div>
@endsection





