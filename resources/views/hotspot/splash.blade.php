@extends('hotspot.layout')

@section('content')
<div class="text-center">
    @if(!isset($portalSettings) || $portalSettings->show_packages)
    <div class="mb-8">
        <div class="inline-block p-4 bg-blue-100 rounded-full mb-4">
            <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
            </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Available Packages</h3>
    </div>
    @endif

    @if($plans->count() > 0 && (!isset($portalSettings) || $portalSettings->show_packages))
    <div class="mb-8">
        <div class="space-y-4">
            @foreach($plans as $plan)
            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 border border-gray-200 hover:shadow-lg transition-shadow">
                <div class="text-center">
                    <h4 class="text-base sm:text-lg font-bold text-gray-900 mb-2">{{ $plan->name }}</h4>
                    <div class="text-2xl sm:text-3xl font-bold text-blue-600 mb-2">
                        Ksh {{ number_format($plan->price, 0) }}
                    </div>
                    <div class="text-sm text-gray-600 mb-4">
                        @if($plan->data_limit_mb)
                            {{ round($plan->data_limit_mb/1024, 1) }} GB
                        @endif
                        @if($plan->download_speed_kbps)
                            • {{ round($plan->download_speed_kbps/1024) }} Mbps
                        @endif
                        @if($plan->duration_days)
                            • {{ $plan->duration_days }} days
                        @endif
                    </div>
                    @if(!isset($portalSettings) || $portalSettings->show_payment_button)
                    <a href="{{ route('hotspot.buy-package', ['plan' => $plan->id, 'router_ip' => request()->ip()]) }}" 
                       class="block w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition duration-150 text-sm sm:text-base">
                        {{ isset($portalSettings) ? $portalSettings->payment_button_text : 'Buy Now' }}
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="space-y-4">
        <a href="{{ route('hotspot.redeem', ['router_ip' => request()->ip()]) }}" 
           class="block w-full py-4 px-4 sm:px-6 bg-white text-blue-600 font-semibold rounded-lg border-2 border-blue-600 hover:bg-blue-50 transition duration-150 text-sm sm:text-base">
            🎫 Have a voucher? Redeem here
        </a>
        <a href="{{ route('hotspot.login', ['router_ip' => request()->ip()]) }}" 
           class="block w-full py-4 px-4 sm:px-6 text-white font-semibold rounded-lg bg-gray-600 hover:bg-gray-700 transition duration-150 text-sm sm:text-base">
            👤 Already have an account? Login
        </a>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-200">
        <p class="text-xs text-gray-500">
            By purchasing, you agree to our Terms of Service
        </p>
    </div>
</div>
@endsection





