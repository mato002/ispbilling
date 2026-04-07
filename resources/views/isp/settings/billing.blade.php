@extends('layouts.admin')

@section('title', 'Billing Settings')

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
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Billing Settings</h3>
                    
                    <form method="POST" action="{{ route('isp.settings.billing.update') }}" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Invoice Number Prefix</label>
                            <input type="text" name="invoice_prefix" value="{{ $isp->settings['billing']['invoice_prefix'] ?? 'INV' }}" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="INV">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Prefix for invoice numbers (e.g., INV-2026-001)</p>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto Generate Invoices</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Automatically generate invoices for active subscriptions</p>
                            </div>
                            <input type="checkbox" name="auto_generate_invoices" value="1" 
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Grace Period (Days)</label>
                            <input type="number" name="grace_period_days" min="0" value="{{ $isp->settings['billing']['grace_period_days'] ?? 3 }}" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Days before suspending service after invoice due date</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Penalty Rate (%)</label>
                            <input type="number" name="penalty_rate" step="0.01" min="0" max="100" value="{{ $isp->settings['billing']['penalty_rate'] ?? 5 }}" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Percentage penalty per day for overdue invoices</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tax Rate (%)</label>
                            <input type="number" name="tax_rate" step="0.01" min="0" max="100" value="{{ $isp->settings['billing']['tax_rate'] ?? 0 }}" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Default tax rate for invoices</p>
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

