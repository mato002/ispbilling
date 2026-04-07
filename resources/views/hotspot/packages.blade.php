@extends('layouts.hotspot')

@section('title', 'Available Packages')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            @if($portalSettings?->logo)
                <div class="mb-6">
                    <img src="{{ asset('storage/' . $portalSettings->logo) }}" alt="{{ $isp->name }}" class="h-16 mx-auto">
                </div>
            @endif
            
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                {{ $portalSettings?->title ?? 'Available Internet Packages' }}
            </h1>
            
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                {{ $portalSettings?->description ?? 'Choose the perfect internet package for your needs. All packages include unlimited data and high-speed connectivity.' }}
            </p>
        </div>

        @if($plans->isEmpty())
            <div class="text-center py-12">
                <div class="text-gray-400 text-6xl mb-4">
                    <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No packages available</h3>
                <p class="text-gray-600">Please check back later or contact support.</p>
            </div>
        @else
            <!-- Packages Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($plans as $plan)
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                        <!-- Package Header -->
                        <div class="bg-gradient-to-r {{ $plan->is_popular ? 'from-blue-500 to-purple-600' : 'from-gray-600 to-gray-700' }} p-6 text-white">
                            @if($plan->is_popular)
                                <div class="flex justify-between items-start mb-4">
                                    <span class="bg-white text-blue-600 text-xs font-bold px-3 py-1 rounded-full">MOST POPULAR</span>
                                </div>
                            @endif
                            
                            <h3 class="text-2xl font-bold mb-2">{{ $plan->name }}</h3>
                            <div class="flex items-baseline">
                                <span class="text-4xl font-bold">${{ number_format($plan->price, 2) }}</span>
                                <span class="ml-2 text-sm opacity-90">/ {{ $plan->billing_cycle }}</span>
                            </div>
                        </div>

                        <!-- Package Features -->
                        <div class="p-6">
                            <div class="space-y-4">
                                <!-- Speed -->
                                @if($plan->download_speed || $plan->upload_speed)
                                    <div class="flex items-center text-gray-700">
                                        <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        <div>
                                            <span class="font-medium">{{ $plan->download_speed }}{{ $plan->download_speed_unit ?? 'Mbps' }}</span>
                                            @if($plan->upload_speed)
                                                <span class="text-gray-500"> / {{ $plan->upload_speed }}{{ $plan->upload_speed_unit ?? 'Mbps' }}</span>
                                            @endif
                                            <span class="text-sm text-gray-500 block">Download / Upload Speed</span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Data Limit -->
                                @if($plan->data_limit)
                                    <div class="flex items-center text-gray-700">
                                        <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        <div>
                                            <span class="font-medium">{{ $plan->data_limit }}{{ $plan->data_limit_unit ?? 'GB' }}</span>
                                            <span class="text-sm text-gray-500 block">Monthly Data Limit</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center text-gray-700">
                                        <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div>
                                            <span class="font-medium">Unlimited Data</span>
                                            <span class="text-sm text-gray-500 block">No data restrictions</span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Duration -->
                                @if($plan->duration)
                                    <div class="flex items-center text-gray-700">
                                        <svg class="w-5 h-5 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div>
                                            <span class="font-medium">{{ $plan->duration }} {{ $plan->duration_unit ?? 'days' }}</span>
                                            <span class="text-sm text-gray-500 block">Package Duration</span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Description -->
                                @if($plan->description)
                                    <div class="text-gray-600 text-sm pt-2 border-t">
                                        {{ $plan->description }}
                                    </div>
                                @endif
                            </div>

                            <!-- Action Button -->
                            <div class="mt-6">
                                <a href="{{ route('hotspot.buy-package', $plan->id) }}{{ $router ? '?router_ip=' . $router->ip_address : '' }}" 
                                   class="w-full bg-gradient-to-r {{ $plan->is_popular ? 'from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700' : 'from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800' }} text-white font-medium py-3 px-4 rounded-lg text-center transition-all duration-200 transform hover:scale-105">
                                    Get Started
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Contact Info -->
        @if($portalSettings?->contact_email || $portalSettings?->contact_phone)
            <div class="mt-12 text-center">
                <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Need Help?</h3>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center text-gray-600">
                        @if($portalSettings->contact_email)
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ $portalSettings->contact_email }}
                            </div>
                        @endif
                        @if($portalSettings->contact_phone)
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $portalSettings->contact_phone }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
