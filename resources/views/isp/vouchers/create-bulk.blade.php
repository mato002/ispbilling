@extends('layouts.admin')

@section('title', 'Create Bulk Vouchers')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Bulk Vouchers') }}
        </h2>
        <a href="{{ route('isp.vouchers.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            Back to Vouchers
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <form method="POST" action="{{ route('isp.vouchers.store-bulk') }}" class="p-6 space-y-6">
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

                <!-- Number of Vouchers -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Number of Vouchers <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="quantity" name="quantity" min="1" max="1000" value="10" required
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @error('quantity')
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

                <!-- Expiration -->
                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Expiration Date <span class="text-gray-500">Optional</span>
                    </label>
                    <input type="datetime-local" id="expires_at" name="expires_at"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @error('expires_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="3"
                              placeholder="Additional notes about these vouchers..."
                              class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Warning -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Bulk Creation Notice
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>You are about to create <span id="voucher-count" class="font-bold">10</span> vouchers at once. This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('isp.vouchers.index') }}" 
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            onclick="return confirm('Are you sure you want to create ' + document.getElementById('quantity').value + ' vouchers?')">
                        Create 10 Vouchers
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
            const quantityInput = document.getElementById('quantity');
            const voucherCountSpan = document.getElementById('voucher-count');
            const submitButton = document.querySelector('button[type="submit"]');

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

            function updateVoucherCount() {
                const quantity = quantityInput.value || 10;
                voucherCountSpan.textContent = quantity;
                submitButton.textContent = `Create ${quantity} Vouchers`;
            }

            voucherTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleFields);
            });

            quantityInput.addEventListener('input', updateVoucherCount);

            // Initial state
            toggleFields();
            updateVoucherCount();
        });
    </script>
@endsection
