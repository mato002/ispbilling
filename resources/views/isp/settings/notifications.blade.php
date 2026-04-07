@extends('layouts.admin')

@section('title', 'Notification Preferences')

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
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Notification Preferences</h3>
                    
                    <form method="POST" action="{{ route('isp.settings.notifications.update') }}" class="space-y-6">
                        @csrf
                        
                        <!-- Email Notifications -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-4">Email Notifications</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable Email Notifications</label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Receive notifications via email</p>
                                    </div>
                                    <input type="checkbox" name="email_notifications" value="1" 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notification Email</label>
                                    <input type="email" name="notification_email" value="{{ $isp->email ?? '' }}" 
                                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                           placeholder="email@example.com">
                                </div>
                            </div>
                        </div>

                        <!-- SMS Notifications -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6 mt-6">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-4">SMS Notifications</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable SMS Notifications</label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Receive notifications via SMS</p>
                                    </div>
                                    <input type="checkbox" name="sms_notifications" value="1" 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMS Provider</label>
                                    <select name="sms_provider" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <option value="">Select Provider</option>
                                        <option value="africas_talking">Africa's Talking</option>
                                        <option value="twilio">Twilio</option>
                                        <option value="custom">Custom API</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Reminders -->
                        <div class="mt-6">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-4">Invoice Reminders</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Send Invoice Reminders</label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Automatically send reminders for pending invoices</p>
                                    </div>
                                    <input type="checkbox" name="invoice_reminders" value="1" 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reminder Days Before Due</label>
                                    <input type="number" name="reminder_days" min="0" value="3" 
                                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

