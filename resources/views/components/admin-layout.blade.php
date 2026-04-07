<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Admin</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col">
            <!-- Header -->
            @include('layouts.admin-header')

            <div class="flex flex-1">
                <!-- Sidebar -->
                @include('layouts.admin-sidebar')

                <!-- Main Content -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900 ml-64">
                    <!-- Page Heading -->
                    @isset($header)
                        <div class="bg-white dark:bg-gray-800 shadow">
                            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </div>
                    @endisset

                    <!-- Page Content -->
                    <div>
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                            {{ $slot }}
                        </div>
                    </div>
                </main>
            </div>

            <!-- Footer -->
            @include('layouts.admin-footer')
        </div>
    </body>
</html>
