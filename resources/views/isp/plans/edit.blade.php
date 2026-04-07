@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Plan') }}
        </h2>
        <a href="{{ route('isp.plans.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to List
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('isp.plans.update', $plan->id) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Basic Information</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Plan Name *</label>
                            <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                            <textarea name="description" rows="3"
                                      class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $plan->description ?? '' }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Access Type *</label>
                            <select name="access_type" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="pppoe" {{ ($plan->access_type ?? '') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="hotspot" {{ ($plan->access_type ?? '') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Price (KES) *</label>
                            <input type="number" name="price" step="0.01" min="0" value="{{ $plan->price ?? '' }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Duration (Days) *</label>
                            <input type="number" name="duration_days" min="1" value="{{ $plan->duration_days ?? 30 }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>
                    </div>

                    <!-- Speed & Limits -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Speed & Limits</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Billing Type *</label>
                            <select name="billing_type" required id="billing_type"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="unlimited" {{ ($plan->billing_type ?? '') == 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                                <option value="data" {{ ($plan->billing_type ?? '') == 'data' ? 'selected' : '' }}>Data Limit</option>
                                <option value="time" {{ ($plan->billing_type ?? '') == 'time' ? 'selected' : '' }}>Time Limit</option>
                            </select>
                        </div>

                        <div id="data_limit_field" style="display: {{ ($plan->billing_type ?? '') == 'data' ? 'block' : 'none' }};">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Data Limit (MB) *</label>
                            <input type="number" name="data_limit_mb" min="0" value="{{ $plan->data_limit_mb ?? '' }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div id="time_limit_field" style="display: {{ ($plan->billing_type ?? '') == 'time' ? 'block' : 'none' }};">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Limit (Hours) *</label>
                            <input type="number" name="time_limit_hours" min="0" value="{{ $plan->time_limit_hours ?? '' }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Device Limit</label>
                            <input type="number" name="device_limit" min="0" value="{{ old('device_limit', $plan->device_limit ?? '') }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Leave empty for unlimited">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maximum number of devices per customer. Leave empty for unlimited devices.</p>
                            @error('device_limit')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Download Speed (Kbps)</label>
                            <input type="number" name="download_speed_kbps" min="0" value="{{ $plan->download_speed_kbps ?? '' }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upload Speed (Kbps)</label>
                            <input type="number" name="upload_speed_kbps" min="0" value="{{ $plan->upload_speed_kbps ?? '' }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Speed Profile Name</label>
                            <input type="text" name="speed_profile" value="{{ old('speed_profile', $plan->speed_profile ?? '') }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 @error('speed_profile') border-red-500 @enderror"
                                   placeholder="Profile name in MikroTik">
                            @error('speed_profile')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ ($plan->is_active ?? true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</label>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('isp.plans.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Plan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('billing_type').addEventListener('change', function() {
            const dataLimitField = document.getElementById('data_limit_field');
            const timeLimitField = document.getElementById('time_limit_field');
            
            if (this.value === 'data') {
                dataLimitField.style.display = 'block';
                timeLimitField.style.display = 'none';
            } else if (this.value === 'time') {
                dataLimitField.style.display = 'none';
                timeLimitField.style.display = 'block';
            } else {
                dataLimitField.style.display = 'none';
                timeLimitField.style.display = 'none';
            }
        });
    </script>
@endsection

