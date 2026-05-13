@extends('layouts.admin')

@section('title', 'Customer')

@section('header')
    <x-isp-page-header
        :title="$customer->full_name"
        module="Subscribers"
        description="Profile, billing history, subscriptions, and network actions."
        primaryLabel="Edit"
        :primaryHref="route('isp.customers.edit', $customer)"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.customers.index') }}" class="pb-btn">Back to list</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-form-section title="Personal information">
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <dt class="isp-form-label mb-1">Full name</dt>
                    <dd class="text-sm font-medium text-slate-900 dark:text-white">{{ $customer->full_name }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Phone</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $customer->phone_number }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Email</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $customer->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Location</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $customer->location }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Installation</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $customer->installation_date?->format('M j, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Status</dt>
                    <dd>
                        @if($customer->status === 'active')
                            <x-status-badge status="active" />
                        @elseif($customer->status === 'suspended')
                            <x-status-badge status="suspended" />
                        @elseif($customer->status === 'expired')
                            <x-status-badge status="expired" />
                        @else
                            <x-status-badge :status="ucfirst($customer->status)" />
                        @endif
                    </dd>
                </div>
            </dl>
        </x-form-section>

        @if (! empty($customer->custom_attributes))
            <x-form-section title="Extended attributes">
                <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($customer->custom_attributes as $k => $val)
                        <div>
                            <dt class="isp-form-label mb-1">{{ str_replace('_', ' ', $k) }}</dt>
                            <dd class="text-sm text-slate-800 dark:text-slate-100">{{ is_scalar($val) ? $val : json_encode($val) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-form-section>
        @endif

        <x-form-section title="Connection">
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <dt class="isp-form-label mb-1">Username</dt>
                    <dd class="font-mono text-sm font-medium text-slate-900 dark:text-white">{{ $customer->username }}</dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Type</dt>
                    <dd>
                        @if($customer->connection_type === 'hotspot')
                            <span class="pb-pill pb-pill-cyan">Hotspot</span>
                        @else
                            <span class="pb-pill pb-pill-info">PPPoE</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Router</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">
                        {{ $customer->router->name ?? '—' }}
                        @if($customer->router)
                            <span class="font-mono text-slate-500">({{ $customer->router->ip_address }})</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="isp-form-label mb-1">Current plan</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-100">
                        @if($activeSubscription && $activeSubscription->plan)
                            {{ $activeSubscription->plan->name }}
                        @else
                            <span class="text-slate-400">No active plan</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-form-section>

        @if($activeSubscription)
            <x-form-section title="Active subscription">
                <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt class="isp-form-label mb-1">Plan</dt>
                        <dd class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeSubscription->plan->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Start</dt>
                        <dd class="text-sm">{{ $activeSubscription->start_date->format('M j, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">End</dt>
                        <dd class="text-sm">{{ $activeSubscription->end_date->format('M j, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Auto renew</dt>
                        <dd class="text-sm">{{ $activeSubscription->auto_renew ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <a href="{{ route('isp.subscriptions.show', $activeSubscription) }}" class="pb-btn text-xs">Open subscription</a>
                    </div>
                </dl>
            </x-form-section>
        @endif

        <x-form-section title="Subscriptions" description="Recent subscription records for this subscriber.">
            @if($customer->subscriptions->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">No subscriptions yet.</p>
            @else
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th class="whitespace-nowrap">Start</th>
                            <th class="whitespace-nowrap">End</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->subscriptions as $sub)
                            <tr>
                                <td class="font-medium text-slate-900 dark:text-white">{{ $sub->plan->name ?? '—' }}</td>
                                <td class="whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $sub->start_date->format('M j, Y') }}</td>
                                <td class="whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $sub->end_date->format('M j, Y') }}</td>
                                <td>
                                    <x-status-badge :status="ucfirst($sub->status)" />
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('isp.subscriptions.show', $sub) }}" class="pb-btn py-1.5 text-xs">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </x-form-section>

        <x-form-section title="Recent invoices" description="Latest invoices linked to this customer.">
            @if($customer->invoices->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">No invoices yet.</p>
            @else
                <x-data-table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th class="whitespace-nowrap">Issued</th>
                            <th class="text-right">Total</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->invoices as $invoice)
                            <tr>
                                <td class="font-mono text-sm text-slate-900 dark:text-white">{{ $invoice->invoice_number }}</td>
                                <td class="whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $invoice->issue_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="text-right font-medium text-slate-900 dark:text-white">KES {{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                                <td><x-status-badge :status="ucfirst($invoice->status)" /></td>
                                <td class="text-right">
                                    <a href="{{ route('isp.invoices.show', $invoice) }}" class="pb-btn py-1.5 text-xs">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </x-form-section>

        <x-form-section title="Recent payments" description="Latest payment records for this customer.">
            @if($customer->payments->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">No payments yet.</p>
            @else
                <x-data-table>
                    <thead>
                        <tr>
                            <th class="text-right">Amount</th>
                            <th>Status</th>
                            <th class="whitespace-nowrap">Processed</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->payments as $payment)
                            <tr>
                                <td class="text-right font-medium text-slate-900 dark:text-white">KES {{ number_format($payment->amount ?? 0, 2) }}</td>
                                <td><x-status-badge :status="ucfirst($payment->status)" /></td>
                                <td class="whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $payment->processed_at?->format('M j, Y H:i') ?? '—' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('isp.payments.show', $payment) }}" class="pb-btn py-1.5 text-xs">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </x-form-section>
    </div>

    <div class="space-y-6">
        <x-form-section title="Quick actions">
            <div class="flex flex-col gap-2">
                @if($customer->status === 'active')
                    <form method="POST" action="{{ route('isp.customers.suspend', $customer) }}" class="w-full" onsubmit="confirmFormSubmit(event, {title: 'Suspend customer', text: 'Suspend {{ $customer->full_name }} and disconnect access?', icon: 'warning', confirmButtonText: 'Suspend'}); return false;">
                        @csrf
                        <button type="submit" class="pb-btn w-full border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">Suspend</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('isp.customers.activate', $customer) }}" class="w-full" onsubmit="confirmFormSubmit(event, {title: 'Activate customer', text: 'Activate {{ $customer->full_name }}?', icon: 'question', confirmButtonText: 'Activate'}); return false;">
                        @csrf
                        <button type="submit" class="pb-btn pb-btn-primary w-full">Activate</button>
                    </form>
                @endif
                <a href="{{ route('isp.subscriptions.create', ['customer_id' => $customer->id]) }}" class="pb-btn w-full justify-center">New subscription</a>
                <a href="{{ route('isp.invoices.create-for-customer', $customer) }}" class="pb-btn w-full justify-center">Create invoice</a>
                <a href="{{ route('isp.payments.customer', $customer) }}" class="pb-btn w-full justify-center">Payments</a>
                <a href="{{ route('isp.sessions.customer-history', $customer) }}" class="pb-btn w-full justify-center">Sessions</a>
            </div>
        </x-form-section>

        @if($customer->notes)
            <x-form-section title="Notes">
                <p class="text-sm text-slate-700 dark:text-slate-300">{{ $customer->notes }}</p>
            </x-form-section>
        @endif

        <x-form-section
            title="Danger zone"
            description="Permanently deletes this subscriber and related subscriptions, invoices, and payments (database). Router user is removed when possible."
        >
            <form method="POST" action="{{ route('isp.customers.destroy', $customer) }}" class="space-y-3" onsubmit="return confirm('Delete this customer permanently? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <div>
                    <label for="confirm_username" class="isp-form-label mb-1">Type username to confirm</label>
                    <input id="confirm_username" name="confirm_username" type="text" autocomplete="off" required value="{{ old('confirm_username') }}"
                           class="isp-form-input font-mono" placeholder="{{ $customer->username }}">
                    @error('confirm_username')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="pb-btn w-full justify-center border-red-200 bg-red-50 text-red-900 hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-100">
                    Delete customer
                </button>
            </form>
        </x-form-section>
    </div>
</div>
@endsection
