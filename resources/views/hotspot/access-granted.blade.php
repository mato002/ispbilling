@extends('hotspot.layout')

@section('content')
<div class="text-center">
    <div class="mb-8">
        <div class="inline-block p-4 bg-green-100 rounded-full mb-4">
            <x-font-awesome-icon name="circle-check" class="h-16 w-16 text-green-600" />
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ ($routerReady ?? false) ? 'Access Granted' : 'Payment received' }}</h2>
        <p class="text-sm text-gray-600">
            @if($routerReady ?? false)
                Your payment is confirmed and your hotspot account is ready.
            @else
                Your payment is confirmed, but the router could not be reached to activate service. Please stay connected and contact the ISP.
            @endif
        </p>
    </div>

    @if($customer ?? false)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 text-left">
            <p class="text-sm text-gray-700">
                <span class="font-semibold">Customer:</span>
                {{ $customer['username'] ?? '' }}
            </p>
            <p class="text-sm text-gray-700 mt-1">
                <span class="font-semibold">Phone:</span>
                Access is linked to your account.
            </p>
        </div>
    @endif

    @if($payment ?? false)
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6 text-left">
            <p class="text-sm text-gray-700">
                <span class="font-semibold">Amount Paid:</span>
                {{ number_format((float) ($payment['amount'] ?? 0), 2) }}
            </p>
            <p class="text-sm text-gray-700 mt-1">
                <span class="font-semibold">Reference:</span>
                Confirmed
            </p>
        </div>
    @endif

    @if(($routerReady ?? false) && !empty($routerLoginUrl) && !empty($hotspotPassword))
        <form id="router-login" method="POST" action="{{ $routerLoginUrl }}" class="mb-5">
            <input type="hidden" name="username" value="{{ $customer['username'] }}">
            <input type="hidden" name="password" value="{{ $hotspotPassword }}">
            @if(!empty($destinationUrl))
                <input type="hidden" name="dst" value="{{ $destinationUrl }}">
            @endif
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Connect to the internet</button>
        </form>
        <p class="text-xs text-gray-500 mb-6">Connecting this device to the hotspot now…</p>
        <script>document.getElementById('router-login')?.requestSubmit();</script>
    @elseif($routerReady ?? false)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-5 text-left">
            <p class="text-sm text-gray-700"><span class="font-semibold">Hotspot username:</span> {{ $customer['username'] }}</p>
            <p class="text-sm text-gray-700 mt-1"><span class="font-semibold">Hotspot password:</span> {{ $hotspotPassword }}</p>
        </div>
        <p class="text-xs text-gray-500 mb-6">Your service is active. If this device does not connect automatically, reopen the Wi-Fi sign-in page.</p>
    @else
        <a href="{{ request()->fullUrl() }}" class="mb-6 inline-flex rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">Retry router connection</a>
    @endif

    <button onclick="history.back();" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Close Window
    </button>
</div>
@endsection






