@extends('layouts.admin')

@section('title', 'Company Information')

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
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Company Information</h3>
                    
                    <form method="POST" action="{{ route('isp.settings.company.update') }}" class="space-y-6">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Company Name</label>
                            <input type="text" name="company_name" value="{{ $isp->company_name ?? '' }}" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email" name="email" value="{{ $isp->email ?? '' }}" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone</label>
                            <input type="text" name="phone" value="{{ $isp->phone ?? '' }}" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address</label>
                            <textarea name="address" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $isp->address ?? '' }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                            <select name="currency" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="KES" {{ ($isp->currency ?? 'KES') == 'KES' ? 'selected' : '' }}>KES - Kenyan Shilling</option>
                                <option value="USD" {{ ($isp->currency ?? '') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                <option value="TZS" {{ ($isp->currency ?? '') == 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                                <option value="UGX" {{ ($isp->currency ?? '') == 'UGX' ? 'selected' : '' }}>UGX - Ugandan Shilling</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Timezone</label>
                            <select name="timezone" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="Africa/Nairobi" {{ ($isp->timezone ?? 'Africa/Nairobi') == 'Africa/Nairobi' ? 'selected' : '' }}>Africa/Nairobi</option>
                                <option value="Africa/Dar_es_Salaam" {{ ($isp->timezone ?? '') == 'Africa/Dar_es_Salaam' ? 'selected' : '' }}>Africa/Dar es Salaam</option>
                                <option value="Africa/Kampala" {{ ($isp->timezone ?? '') == 'Africa/Kampala' ? 'selected' : '' }}>Africa/Kampala</option>
                            </select>
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

