@extends('layouts.admin')

@section('title', 'Create Subscription')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Subscription') }}
        </h2>
        <a href="{{ route('isp.subscriptions.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to List
        </a>
    </div>
@endsection

@section('content')
    @php
        $firstPlan = $plans->first();
        $startDefault = old('start_date', now()->format('Y-m-d'));
        $endDefault = old('end_date', $firstPlan ? \Illuminate\Support\Carbon::parse($startDefault)->addDays((int) $firstPlan->duration_days)->format('Y-m-d') : now()->addDays(30)->format('Y-m-d'));
        $preselectCustomer = old('customer_id', $selectedCustomerId ?? null);
    @endphp
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('isp.subscriptions.store') }}" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer *</label>
                        <select name="customer_id" required id="customer_id"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) $preselectCustomer === (string) $customer->id)>{{ $customer->full_name }} ({{ $customer->phone_number }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Plan *</label>
                        <select name="plan_id" required id="plan_id"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">Select Plan</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" data-duration="{{ $plan->duration_days }}" @selected((string) old('plan_id', $firstPlan?->id) === (string) $plan->id)>
                                    {{ $plan->name }} - KES {{ number_format($plan->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date *</label>
                        <input type="date" name="start_date" required id="start_date" value="{{ $startDefault }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date *</label>
                        <input type="date" name="end_date" required id="end_date" value="{{ $endDefault }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status *</label>
                        <select name="status" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                        </select>
                    </div>

                    <div class="flex items-center">
                        <input type="hidden" name="auto_renew" value="0">
                        <input type="checkbox" name="auto_renew" value="1" @checked(old('auto_renew', '1') == '1')
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Auto Renew</label>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('isp.subscriptions.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('plan_id').addEventListener('change', function() {
            const planOption = this.options[this.selectedIndex];
            const duration = planOption.getAttribute('data-duration');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            if (duration && startDateInput.value) {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(startDate);
                endDate.setDate(endDate.getDate() + parseInt(duration));
                endDateInput.value = endDate.toISOString().split('T')[0];
            }
        });

        document.getElementById('start_date').addEventListener('change', function() {
            const planSelect = document.getElementById('plan_id');
            const planOption = planSelect.options[planSelect.selectedIndex];
            const duration = planOption.getAttribute('data-duration');
            const endDateInput = document.getElementById('end_date');
            
            if (duration && this.value) {
                const startDate = new Date(this.value);
                const endDate = new Date(startDate);
                endDate.setDate(endDate.getDate() + parseInt(duration));
                endDateInput.value = endDate.toISOString().split('T')[0];
            }
        });
    </script>
@endsection

