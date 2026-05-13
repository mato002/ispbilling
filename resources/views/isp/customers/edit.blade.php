@extends('layouts.admin')

@section('title', 'Edit Customer')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Customer') }}
        </h2>
        <a href="{{ route('isp.customers.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to List
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('isp.customers.update', $customer) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Personal Information</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name *</label>
                            <input type="text" name="full_name" value="{{ old('full_name', $customer->full_name ?? '') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number *</label>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $customer->phone_number ?? '') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location *</label>
                            <input type="text" name="location" value="{{ old('location', $customer->location ?? '') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Installation date *</label>
                            <input type="date" name="installation_date" value="{{ old('installation_date', $customer->installation_date?->format('Y-m-d') ?? '') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>
                    </div>

                    <!-- Connection Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Connection Information</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Connection Type *</label>
                            <select name="connection_type" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="pppoe" @selected(old('connection_type', $customer->connection_type) === 'pppoe')>PPPoE</option>
                                <option value="hotspot" @selected(old('connection_type', $customer->connection_type) === 'hotspot')>Hotspot</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Router *</label>
                            <select name="router_id" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="">Select Router</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->id }}" @selected((string) old('router_id', $customer->router_id) === (string) $router->id)>
                                        {{ $router->name }} ({{ $router->ip_address }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                            <input type="text" name="username" value="{{ old('username', $customer->username ?? '') }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                            <input type="password" name="password"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Leave empty to keep current password">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status *</label>
                            <select name="status" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="active" @selected(old('status', $customer->status) === 'active')>Active</option>
                                <option value="suspended" @selected(old('status', $customer->status) === 'suspended')>Suspended</option>
                                <option value="expired" @selected(old('status', $customer->status) === 'expired')>Expired</option>
                                <option value="inactive" @selected(old('status', $customer->status) === 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <x-isp-custom-fields-form :definitions="$customFieldDefinitions ?? []" :values="$customer->custom_attributes ?? []" />

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('notes', $customer->notes ?? '') }}</textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('isp.customers.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

