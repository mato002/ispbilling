@extends('layouts.admin')

@section('title', 'Create Voucher')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Voucher') }}
        </h2>
        <a href="{{ route('isp.vouchers.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            Back to Vouchers
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <form method="POST" action="{{ route('isp.vouchers.store') }}" class="p-6 space-y-6">
                @csrf
                
                <!-- Router Selection -->
                <div>
                    <label for="router_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Router <span class="text-red-500">*</span>
                    </label>
                    <select id="router_id" name="router_id" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Select a router</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->name }}</option>
                        @endforeach
                    </select>
                    @error('router_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Plan Selection -->
                <div>
                    <label for="plan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Plan (Optional)
                    </label>
                    <select id="plan_id" name="plan_id"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">No specific plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} - KES {{ number_format($plan->price, 0) }}</option>
                        @endforeach
                    </select>
                    @error('plan_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Voucher Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Voucher Type <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="voucher_type" value="time" required
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Time-based</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="voucher_type" value="data" required
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Data-based</span>
                        </label>
                    </div>
                    @error('voucher_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Time Limit (for time-based vouchers) -->
                <div id="time-fields" class="hidden">
                    <label for="time_limit_hours" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Time Limit (Hours) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="time_limit_hours" name="time_limit_hours" min="1"
                           placeholder="e.g., 24 for 24 hours"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @error('time_limit_hours')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Data Limit (for data-based vouchers) -->
                <div id="data-fields" class="hidden">
                    <label for="data_limit_mb" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Data Limit (MB) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="data_limit_mb" name="data_limit_mb" min="1"
                           placeholder="e.g., 1024 for 1GB"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @error('data_limit_mb')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Speed Profile -->
                <div>
                    <label for="speed_profile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Speed Profile (Optional)
                    </label>
                    <input type="text" id="speed_profile" name="speed_profile"
                           placeholder="e.g., 1Mbps/512Kbps"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @error('speed_profile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Price (KES) <span class="text-gray-500">Optional</span>
                    </label>
                    <input type="number" id="price" name="price" min="0" step="0.01"
                           placeholder="e.g., 100"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @error('price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="3"
                              placeholder="Additional notes about this voucher..."
                              class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('isp.vouchers.index') }}" 
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Create Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const voucherTypeRadios = document.querySelectorAll('input[name="voucher_type"]');
            const timeFields = document.getElementById('time-fields');
            const dataFields = document.getElementById('data-fields');

            function toggleFields() {
                const selectedType = document.querySelector('input[name="voucher_type"]:checked')?.value;
                
                if (selectedType === 'time') {
                    timeFields.classList.remove('hidden');
                    dataFields.classList.add('hidden');
                    document.getElementById('time_limit_hours').required = true;
                    document.getElementById('data_limit_mb').required = false;
                } else if (selectedType === 'data') {
                    timeFields.classList.add('hidden');
                    dataFields.classList.remove('hidden');
                    document.getElementById('time_limit_hours').required = false;
                    document.getElementById('data_limit_mb').required = true;
                } else {
                    timeFields.classList.add('hidden');
                    dataFields.classList.add('hidden');
                    document.getElementById('time_limit_hours').required = false;
                    document.getElementById('data_limit_mb').required = false;
                }
            }

            voucherTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleFields);
            });

            // Initial state
            toggleFields();
        });
    </script>
@endsection
