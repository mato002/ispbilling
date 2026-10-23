<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ config('app.name', 'ISP Billing') }} - @yield('title', 'Dashboard')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <x-vite-assets />
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <div class="flex">
                <!-- Sidebar -->
                @if(Auth::check() && (Auth::user()->isIspAdmin() || Auth::user()->isIspStaff()))
                    <x-sidebar />
                @endif

                <!-- Main Content -->
                <turbo-frame id="pb-main" class="block flex-1 {{ Auth::check() && (Auth::user()->isIspAdmin() || Auth::user()->isIspStaff()) ? 'ml-64' : '' }}">
                <main class="min-h-screen">
                    <!-- Page Heading -->
                    @isset($header)
                        <header class="bg-white dark:bg-gray-800 shadow mt-16">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <!-- Page Content -->
                    <div class="py-6">
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                            @yield('content')
                        </div>
                    </div>
                </main>
                </turbo-frame>
            </div>
        </div>
    </body>
</html>

