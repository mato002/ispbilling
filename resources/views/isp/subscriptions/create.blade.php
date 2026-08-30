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
        use App\Support\PlanPackageTypes;

        $firstPlan = $plans->first();
        $startDefault = old('start_date', now()->format('Y-m-d'));
        $endDefault = old('end_date', $firstPlan ? \Illuminate\Support\Carbon::parse($startDefault)->addDays((int) $firstPlan->duration_days)->format('Y-m-d') : now()->addDays(30)->format('Y-m-d'));
        $preselectCustomer = old('customer_id', $selectedCustomerId ?? null);
    @endphp
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('isp.subscriptions.store') }}" class="space-y-6" id="subscription-form">
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
                                <option
                                    value="{{ $plan->id }}"
                                    data-duration="{{ $plan->duration_days }}"
                                    data-package-type="{{ $plan->package_type }}"
                                    data-requires-pppoe="{{ $plan->requires_pppoe_plan ? '1' : '0' }}"
                                    @selected((string) old('plan_id', $firstPlan?->id) === (string) $plan->id)
                                >
                                    {{ $plan->name }} ({{ $plan->packageTypeLabel() }}) — KES {{ number_format($plan->price, 2) }}
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
                        <input type="checkbox" name="auto_renew" value="1" id="auto_renew" @checked(old('auto_renew', '1') == '1')
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300" for="auto_renew">Auto Renew</label>
                    </div>
                </div>

                <div id="static-ip-fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Static IP address *</label>
                        <input type="text" name="static_ip" id="static_ip" value="{{ old('static_ip') }}"
                               placeholder="192.168.1.10"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    </div>
                    <div class="flex items-center">
                        <input type="hidden" name="static_ip_active" value="0">
                        <input type="checkbox" name="static_ip_active" value="1" id="static_ip_active" @checked(old('static_ip_active', '1') == '1')
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300" for="static_ip_active">IP active on router</label>
                    </div>
                </div>

                <div id="service-status-fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service status</label>
                        <select name="service_status" id="service_status"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @foreach(['pending' => 'Pending', 'scheduled' => 'Scheduled', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('service_status', 'pending') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

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
        const STATIC_IP_TYPE = '{{ PlanPackageTypes::STATIC_IP_ADDON }}';
        const INSTALLATION_TYPE = '{{ PlanPackageTypes::INSTALLATION_SERVICE }}';

        function updateEndDate() {
            const planSelect = document.getElementById('plan_id');
            const planOption = planSelect.options[planSelect.selectedIndex];
            const duration = planOption.getAttribute('data-duration');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (duration && startDateInput.value) {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(startDate);
                endDate.setDate(endDate.getDate() + parseInt(duration, 10));
                endDateInput.value = endDate.toISOString().split('T')[0];
            }
        }

        function togglePackageFields() {
            const planSelect = document.getElementById('plan_id');
            const planOption = planSelect.options[planSelect.selectedIndex];
            const packageType = planOption.getAttribute('data-package-type');
            const staticIpFields = document.getElementById('static-ip-fields');
            const serviceStatusFields = document.getElementById('service-status-fields');
            const autoRenew = document.getElementById('auto_renew');

            staticIpFields.classList.toggle('hidden', packageType !== STATIC_IP_TYPE);
            serviceStatusFields.classList.toggle('hidden', packageType !== INSTALLATION_TYPE);

            if (packageType === INSTALLATION_TYPE) {
                autoRenew.checked = false;
            }
        }

        document.getElementById('plan_id').addEventListener('change', function() {
            updateEndDate();
            togglePackageFields();
        });

        document.getElementById('start_date').addEventListener('change', updateEndDate);

        togglePackageFields();
    </script>
@endsection
