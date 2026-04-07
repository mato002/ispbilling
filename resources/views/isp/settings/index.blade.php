@extends('layouts.admin')

@section('title', 'Settings')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Settings') }}
    </h2>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Settings Navigation -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Settings</h3>
                    <nav class="space-y-2">
                        <a href="{{ route('isp.settings.company') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.company*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg font-medium">
                            Company Information
                        </a>
                        <a href="{{ route('isp.settings.payment') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.payment*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg">
                            MPESA Daraja Settings
                        </a>
                        <a href="{{ route('isp.settings.notifications') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.notifications*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg">
                            Notification Preferences
                        </a>
                        <a href="{{ route('isp.settings.users') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.users*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg">
                            User Management
                        </a>
                        <a href="{{ route('isp.settings.billing') }}" class="block px-4 py-2 {{ request()->routeIs('isp.settings.billing*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg">
                            Billing Settings
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-gray-600 dark:text-gray-400">Select a settings category from the menu to get started.</p>
                </div>
            </div>
        </div>
    </div>
@endsection


