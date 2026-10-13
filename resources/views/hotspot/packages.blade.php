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
                    <x-font-awesome-icon name="clock" class="mx-auto h-24 w-24" />
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
                                        <x-font-awesome-icon name="bolt" class="mr-3 h-5 w-5 text-green-500" />
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
                                        <x-font-awesome-icon name="chart-line" class="mr-3 h-5 w-5 text-blue-500" />
                                        <div>
                                            <span class="font-medium">{{ $plan->data_limit }}{{ $plan->data_limit_unit ?? 'GB' }}</span>
                                            <span class="text-sm text-gray-500 block">Monthly Data Limit</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center text-gray-700">
                                        <x-font-awesome-icon name="circle-check" class="mr-3 h-5 w-5 text-blue-500" />
                                        <div>
                                            <span class="font-medium">Unlimited Data</span>
                                            <span class="text-sm text-gray-500 block">No data restrictions</span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Duration -->
                                @if($plan->duration)
                                    <div class="flex items-center text-gray-700">
                                        <x-font-awesome-icon name="clock" class="mr-3 h-5 w-5 text-purple-500" />
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
                                <x-font-awesome-icon name="envelope" class="mr-2 h-5 w-5 text-gray-400" />
                                {{ $portalSettings->contact_email }}
                            </div>
                        @endif
                        @if($portalSettings->contact_phone)
                            <div class="flex items-center">
                                <x-font-awesome-icon name="mobile-phone" class="mr-2 h-5 w-5 text-gray-400" />
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
