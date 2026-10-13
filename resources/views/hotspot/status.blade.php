@extends('hotspot.layout')

@section('content')
<div>
    <div class="text-center mb-6">
        <div class="inline-block p-3 bg-green-100 rounded-full mb-4">
            <x-font-awesome-icon name="circle-check" class="h-12 w-12 text-green-600" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Connected!</h2>
        <p class="text-sm text-gray-600">You are now connected to the internet</p>
    </div>

    @if($customer)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-blue-900">{{ $customer->full_name }}</p>
            <p class="text-xs text-blue-700">{{ $customer->phone_number }}</p>
        </div>
    @endif

    @if($subscription)
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">Plan:</span>
                <span class="text-sm font-semibold text-gray-900">{{ $subscription->plan->name }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700">Expires:</span>
                <span class="text-sm text-gray-900">{{ $subscription->end_date->format('M d, Y') }}</span>
            </div>
        </div>
    @endif

    @if($session)
        <div class="space-y-3 mb-6">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs text-gray-600">Session Time</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $session->uptime_formatted }}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs text-gray-600">Data Used</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $session->formatted_total_bytes }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">IP Address</span>
                    <span class="text-sm font-mono text-gray-900">{{ $session->ip_address }}</span>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('hotspot.logout') }}" class="mb-4">
        @csrf
        <input type="hidden" name="username" value="{{ $session->username ?? '' }}">
        <input type="hidden" name="token" value="{{ $token ?? '' }}">
        <button type="submit" 
                class="w-full py-3 px-4 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition duration-150">
            Disconnect
        </button>
    </form>

    <div class="text-center">
        <button onclick="location.reload()" 
                class="text-sm text-blue-600 hover:text-blue-700 font-medium">
            Refresh Status
        </button>
    </div>
</div>

<script>
    // Auto-refresh status every 30 seconds
    setInterval(function() {
        fetch('{{ route("hotspot.status", ["username" => $session->username ?? "", "token" => $token ?? ""]) }}')
            .then(response => response.text())
            .then(html => {
                // Update session stats if needed
            });
    }, 30000);
</script>
@endsection





