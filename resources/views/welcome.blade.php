<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Smart ISP Billing System - Connect. Earn. Expand.</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /* Tailwind CSS will be injected here */
            </style>
        @endif
    </head>
    <body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
        <!-- Navigation -->
        <nav class="fixed top-0 w-full bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-800 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">ISP Billing</span>
                    </div>
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="#features" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Features</a>
                        <a href="#integrations" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Integrations</a>
                        <a href="#automations" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Automations</a>
            @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Log in</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">Get Started</a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
            <!-- Background gradient -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-blue-950 dark:via-gray-900 dark:to-purple-950"></div>
            
            <!-- Decorative elements -->
            <div class="absolute top-20 left-10 w-72 h-72 bg-blue-200 dark:bg-blue-800 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-xl opacity-20 animate-pulse"></div>
            <div class="absolute top-40 right-10 w-72 h-72 bg-purple-200 dark:bg-purple-800 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-xl opacity-20 animate-pulse animation-delay-2000"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-200 dark:bg-pink-800 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-xl opacity-20 animate-pulse animation-delay-4000"></div>
            
            <div class="relative max-w-7xl mx-auto text-center">
                <!-- Trust badges -->
                <div class="flex flex-wrap justify-center items-center gap-8 mb-8 opacity-80">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>ISO 27001 Certified</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>GDPR Compliant</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <span>4.9/5 Rating</span>
                    </div>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    Smart ISP Billing System
                </h1>
                <p class="text-xl md:text-3xl text-gray-600 dark:text-gray-400 mb-6 font-semibold">
                    Connect. Earn. Expand.
                </p>
                <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400 max-w-4xl mx-auto mb-12 leading-relaxed">
                    Transform your ISP business with our comprehensive billing platform. Automate operations, integrate with MikroTik, and accept multiple payments. <span class="font-semibold text-blue-600 dark:text-blue-400">Focus on growth while we handle the complexity.</span>
                </p>
                
                <!-- Enhanced CTA buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-xl font-semibold transition transform hover:scale-105 shadow-lg inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Go to Dashboard
                        </a>
                    @else
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-xl font-semibold transition transform hover:scale-105 shadow-lg inline-flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Get Started - Free
                            </a>
                        @endif
                        <a href="{{ route('login') }}" class="border-2 border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 px-8 py-4 rounded-xl font-semibold transition transform hover:scale-105 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            View Demo
                        </a>
                    @endauth
                </div>
                
                <!-- Social proof -->
                <div class="text-center text-gray-600 dark:text-gray-400">
                    <p class="text-sm mb-2">Trusted by leading ISPs worldwide</p>
                    <div class="flex flex-wrap justify-center items-center gap-6 opacity-60">
                        <span class="text-2xl font-bold text-gray-400">500+</span>
                        <span class="text-sm">ISPs Managed</span>
                        <span class="text-2xl font-bold text-gray-400">50K+</span>
                        <span class="text-sm">Active Subscribers</span>
                        <span class="text-2xl font-bold text-gray-400">99.9%</span>
                        <span class="text-sm">Uptime</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Testimonials Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold mb-4">Trusted by Industry Leaders</h2>
                    <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                        See how ISPs worldwide are transforming their business operations with our platform
                    </p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-8 mb-16">
                    <div class="bg-gray-50 dark:bg-gray-800 p-8 rounded-2xl shadow-sm hover:shadow-lg transition-shadow">
                        <div class="flex items-center mb-4">
                            <img src="https://picsum.photos/seed/avatar1/48/48.jpg" alt="Customer avatar" class="w-12 h-12 rounded-full mr-4">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">Michael Chen</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">CEO, FastNet ISP</p>
                            </div>
                        </div>
                        <div class="flex mb-3">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 italic">"The automation features have saved us 20+ hours per week. Our revenue increased by 35% in the first 6 months."</p>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 p-8 rounded-2xl shadow-sm hover:shadow-lg transition-shadow">
                        <div class="flex items-center mb-4">
                            <img src="https://picsum.photos/seed/avatar2/48/48.jpg" alt="Customer avatar" class="w-12 h-12 rounded-full mr-4">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">Sarah Johnson</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Operations Director, ConnectISP</p>
                            </div>
                        </div>
                        <div class="flex mb-3">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 italic">"MikroTik integration is seamless. Customer support tickets dropped by 60% after implementing the self-service portal."</p>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 p-8 rounded-2xl shadow-sm hover:shadow-lg transition-shadow">
                        <div class="flex items-center mb-4">
                            <img src="https://picsum.photos/seed/avatar3/48/48.jpg" alt="Customer avatar" class="w-12 h-12 rounded-full mr-4">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">David Kimani</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Technical Manager, SwiftNet</p>
                            </div>
                        </div>
                        <div class="flex mb-3">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 italic">"Payment gateway integration with MPesa has been game-changing. Collection efficiency improved by 45%."</p>
                    </div>
                </div>
                
                <!-- Partner logos -->
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Proudly serving ISPs of all sizes</p>
                    <div class="flex flex-wrap justify-center items-center gap-8 opacity-60">
                        <div class="bg-gray-200 dark:bg-gray-700 px-6 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-semibold">FastNet</div>
                        <div class="bg-gray-200 dark:bg-gray-700 px-6 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-semibold">ConnectISP</div>
                        <div class="bg-gray-200 dark:bg-gray-700 px-6 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-semibold">SwiftNet</div>
                        <div class="bg-gray-200 dark:bg-gray-700 px-6 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-semibold">TechLink</div>
                        <div class="bg-gray-200 dark:bg-gray-700 px-6 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-semibold">NetPro</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-800">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-4xl font-bold text-center mb-4">Comprehensive Features</h2>
                <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
                    Purpose-built for ISPs to track customer engagement and make data-driven decisions to grow your business.
                </p>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900 dark:to-blue-800 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-gray-900 dark:text-gray-100">Real-Time Analytics</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Monitor revenue, payments, and subscriber metrics with live dashboards and customizable reports.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900 dark:to-green-800 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-gray-900 dark:text-gray-100">Smart Automation</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Schedule billing, send reminders, and manage subscriptions with intelligent workflow automation.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900 dark:to-purple-800 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-gray-900 dark:text-gray-100">MikroTik Control</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Remote management of all RouterOS devices with automated user provisioning and bandwidth control.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="w-14 h-14 bg-gradient-to-br from-orange-100 to-orange-200 dark:from-orange-900 dark:to-orange-800 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-gray-900 dark:text-gray-100">Multi-Payment Gateway</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Accept MPesa, Selcom, and manual payments with instant reconciliation and auto-activation.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold mb-4">Impact & Growth Metrics</h2>
                    <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                        Real numbers from real ISPs using our platform daily
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-5xl font-bold mb-2 counter" data-target="500">0</div>
                        <div class="text-blue-100 text-lg font-semibold mb-1">ISPs Managed</div>
                        <div class="text-blue-200 text-sm">Across 15+ countries</div>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold mb-2 counter" data-target="50000">0</div>
                        <div class="text-blue-100 text-lg font-semibold mb-1">Active Subscribers</div>
                        <div class="text-blue-200 text-sm">Growing daily</div>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold mb-2">99.9%</div>
                        <div class="text-blue-100 text-lg font-semibold mb-1">Uptime SLA</div>
                        <div class="text-blue-200 text-sm">24/7 monitoring</div>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold mb-2">35%</div>
                        <div class="text-blue-100 text-lg font-semibold mb-1">Revenue Growth</div>
                        <div class="text-blue-200 text-sm">Average increase</div>
                    </div>
                </div>
                
                <div class="mt-16 text-center">
                    <p class="text-lg text-blue-100 mb-8">Join thousands of ISPs transforming their business operations</p>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition transform hover:scale-105 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                            Start Your Growth Journey
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <!-- Integrations Section -->
        <section id="integrations" class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-4xl font-bold text-center mb-4">Seamless Integration</h2>
                <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
                    Multiple payment gateways, SMS platforms, and full MikroTik RouterOS support
                </p>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Payment Gateways</h3>
                        <p class="text-gray-600 dark:text-gray-400">MPesa STK Push, Selcom, and manual payment options</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">SMS Platforms</h3>
                        <p class="text-gray-600 dark:text-gray-400">Vonage/Nexmo integration for automated notifications</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">MikroTik RouterOS</h3>
                        <p class="text-gray-600 dark:text-gray-400">Full API integration for automated user management</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Time-Saving Automations -->
        <section id="automations" class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-800">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-4xl font-bold text-center mb-4">Time-Saving Automations</h2>
                <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
                    Simplify the management of your ISP business
                </p>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-3">PPPoE Integration</h3>
                        <p class="text-gray-600 dark:text-gray-400">Seamless authentication and access control for broadband subscribers. Monitor bandwidth usage and manage connection settings.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-3">HotSpot Management</h3>
                        <p class="text-gray-600 dark:text-gray-400">Create and manage voucher-based and account-based hotspot access. Track usage patterns and enforce bandwidth policies.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-3">Automated Billing</h3>
                        <p class="text-gray-600 dark:text-gray-400">Schedule recurring invoices and automate payment collection across multiple payment gateways. Reduce manual processing.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-3">Remote MikroTik</h3>
                        <p class="text-gray-600 dark:text-gray-400">Control and configure all your MikroTik devices from a central dashboard. Monitor network health and deploy changes instantly.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-3">Package Management</h3>
                        <p class="text-gray-600 dark:text-gray-400">Create and manage customizable service plans with flexible pricing tiers. Easily update packages and apply changes automatically.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-3">Customer Portal</h3>
                        <p class="text-gray-600 dark:text-gray-400">Self-service platform for subscribers to view bills, make payments, and manage services. Reduce support workload.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-4xl font-bold text-center mb-4">Questions? Answers!</h2>
                <p class="text-center text-gray-600 dark:text-gray-400 mb-12">
                    Find quick answers to the most common questions about our platform
                </p>
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-2">Which MikroTik models do you support?</h3>
                        <p class="text-gray-600 dark:text-gray-400">Our billing system is fully compatible with MikroTik RouterOS v6 and v7, including hAP, RB, CCR, CRS, LHG, SXT, and LtAP series. As long as your router supports API access, you can take full advantage of our features.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-2">What payment methods do you support?</h3>
                        <p class="text-gray-600 dark:text-gray-400">We integrate with MPesa STK Push, Selcom, and support manual payments. Our platform ensures real-time payment confirmations and instant service activation.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-2">Do you support multiple locations?</h3>
                        <p class="text-gray-600 dark:text-gray-400">Yes, we support multiple locations and multiple MikroTik devices. Our multi-tenant architecture allows you to manage multiple ISPs from a single platform.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-2">Is there technical support available?</h3>
                        <p class="text-gray-600 dark:text-gray-400">Yes, we offer technical support to help you get started and resolve any issues you may encounter.</p>
                    </div>
                </div>
        </div>
        </section>

        <!-- Enhanced CTA Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-600 via-purple-600 to-blue-700 text-white relative overflow-hidden">
            <!-- Background decorative elements -->
            <div class="absolute inset-0">
                <div class="absolute top-10 left-10 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
                <div class="absolute bottom-10 right-10 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                <div class="absolute top-1/2 left-1/3 w-24 h-24 bg-white/10 rounded-full blur-lg"></div>
            </div>
            
            <div class="relative max-w-6xl mx-auto text-center">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">Ready to Transform Your ISP Business?</h2>
                <p class="text-xl text-blue-100 mb-12 max-w-3xl mx-auto">
                    Join thousands of successful ISPs using our platform to automate operations, boost revenue, and delight customers.
                </p>
                
                <div class="flex flex-col lg:flex-row gap-8 justify-center items-center mb-16">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="bg-white text-blue-600 px-10 py-4 rounded-xl font-bold hover:bg-gray-100 transition transform hover:scale-105 shadow-2xl inline-flex items-center gap-3 text-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Go to Dashboard
                        </a>
                    @else
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-white text-blue-600 px-10 py-4 rounded-xl font-bold hover:bg-gray-100 transition transform hover:scale-105 shadow-2xl inline-flex items-center gap-3 text-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Get Started - Free
                            </a>
                        @endif
                    @endauth
                    
                    <a href="{{ route('login') }}" class="border-2 border-white text-white px-10 py-4 rounded-xl font-bold hover:bg-white/10 transition transform hover:scale-105 inline-flex items-center gap-3 text-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Schedule Demo
                    </a>
                </div>
                
                <!-- Mobile App Downloads -->
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 max-w-2xl mx-auto">
                    <h3 class="text-2xl font-bold mb-4">Manage Your Business On The Go</h3>
                    <p class="text-blue-100 mb-6">Download our mobile app for iOS and Android to monitor your ISP operations from anywhere.</p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button class="bg-black hover:bg-gray-900 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center justify-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                            </svg>
                            Download for iOS
                        </button>
                        <button class="bg-black hover:bg-gray-900 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center justify-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/>
                            </svg>
                            Download for Android
                        </button>
                    </div>
                </div>
                
                <!-- Trust indicators -->
                <div class="mt-12 flex flex-wrap justify-center items-center gap-8 text-sm text-blue-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>30-Day Free Trial</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>No Credit Card Required</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Free Setup & Migration</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-400 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-white font-bold text-xl mb-4">ISP Billing</h3>
                        <p class="text-sm">Smart billing system for Internet Service Providers</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Features</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#features" class="hover:text-white transition">Billing & Invoicing</a></li>
                            <li><a href="#automations" class="hover:text-white transition">Automations</a></li>
                            <li><a href="#integrations" class="hover:text-white transition">Integrations</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Resources</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ route('documentation') }}" class="hover:text-white transition">Documentation</a></li>
                            <li><a href="{{ route('documentation') }}#api" class="hover:text-white transition">API Reference</a></li>
                            <li><a href="#faq" class="hover:text-white transition">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Legal</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ route('terms') }}" class="hover:text-white transition">Terms of Service</a></li>
                            <li><a href="{{ route('privacy') }}" class="hover:text-white transition">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                    <p>&copy; {{ date('Y') }} ISP Billing System. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
