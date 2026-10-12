@extends('layouts.admin')

@section('title', 'Invoices')

@section('header')
    <x-isp-page-header
        title="Invoices"
        module="Billing"
        description="Create invoices, record payments, and export billing records from one place."
        primaryLabel="Create invoice"
        primaryEvent="open-invoice-modal"
    />
@endsection

@section('content')
    @php
        $invoiceCustomerOptions = $invoiceCustomers->map(fn ($customer) => [
            'id' => $customer->id,
            'name' => $customer->full_name,
            'phone' => $customer->phone_number,
            'subscriptions' => $customer->subscriptions->map(fn ($subscription) => [
                'id' => $subscription->id,
                'plan' => $subscription->plan?->name ?? 'Subscription',
                'amount' => (float) ($subscription->plan?->price ?? 0),
            ])->values(),
        ])->values();
    @endphp
    <div x-data="invoiceCreateModal({{ Js::from($invoiceCustomerOptions) }})" @keydown.escape.window="open = false" @open-invoice-modal.window="open = true">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm" @click.self="open = false">
        <section x-transition class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900" role="dialog" aria-modal="true" aria-labelledby="invoice-modal-title">
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <div><p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">Billing</p><h2 id="invoice-modal-title" class="mt-1 text-xl font-bold text-slate-900 dark:text-white">Create invoice</h2><p class="mt-1 text-sm text-slate-500">Choose a customer and subscription, then review the invoice details.</p></div>
                <button type="button" @click="open = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close">✕</button>
            </div>
            <form method="POST" action="{{ route('isp.invoices.store-from-index') }}" class="space-y-5 p-6">
                @csrf
                @if($errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
                        <p class="font-semibold">Please correct the following:</p>
                        <ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div>
                    <label for="invoice-customer" class="isp-form-label">Customer</label>
                    <select id="invoice-customer" name="customer_id" x-model="customerId" @change="subscriptionId = ''; amount = ''" required class="isp-form-select">
                        <option value="">Select customer</option>
                        <template x-for="customer in customers" :key="customer.id"><option :value="customer.id" x-text="`${customer.name} · ${customer.phone}`"></option></template>
                    </select>
                    <p x-show="customers.length === 0" class="mt-2 text-sm text-amber-700">No customers with an active subscription are available to invoice.</p>
                </div>
                <div>
                    <label for="invoice-subscription" class="isp-form-label">Subscription / plan</label>
                    <select id="invoice-subscription" name="subscription_id" x-model="subscriptionId" @change="syncAmount()" :disabled="!selectedCustomer" required class="isp-form-select">
                        <option value="">Select subscription</option>
                        <template x-for="subscription in (selectedCustomer?.subscriptions ?? [])" :key="subscription.id"><option :value="subscription.id" x-text="subscription.plan"></option></template>
                    </select>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><label for="invoice-amount" class="isp-form-label">Amount (KES)</label><input id="invoice-amount" name="amount" type="number" min="0" step="0.01" x-model="amount" required class="isp-form-input"></div>
                    <div><label for="invoice-due-date" class="isp-form-label">Due date</label><input id="invoice-due-date" name="due_date" type="date" value="{{ old('due_date', now()->addDays(7)->format('Y-m-d')) }}" required class="isp-form-input"></div>
                </div>
                <div><label for="invoice-notes" class="isp-form-label">Notes <span class="font-normal text-slate-400">(optional)</span></label><textarea id="invoice-notes" name="notes" rows="3" maxlength="1000" class="isp-form-input" placeholder="Add any details for this invoice">{{ old('notes') }}</textarea></div>
                <div class="flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                    <button type="button" @click="open = false" class="pb-btn">Cancel</button>
                    <button type="submit" :disabled="!selectedCustomer || !subscriptionId" class="pb-btn pb-btn-primary disabled:cursor-not-allowed disabled:opacity-50">Create invoice</button>
                </div>
            </form>
        </section>
    </div>
    <script>
        function invoiceCreateModal(customers) {
            return {
                open: @json($errors->any()), customers, customerId: @json(old('customer_id', '')), subscriptionId: @json(old('subscription_id', '')), amount: @json(old('amount', '')),
                get selectedCustomer() { return this.customers.find(customer => String(customer.id) === String(this.customerId)); },
                syncAmount() {
                    const subscription = this.selectedCustomer?.subscriptions.find(item => String(item.id) === String(this.subscriptionId));
                    this.amount = subscription ? subscription.amount : '';
                }
            };
        }

    </script>
    </div>
    <x-resource-index
        :filter-action="route('isp.invoices.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$invoices"
        :empty="false"
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Invoice #, customer, phone…"
            />
            <x-listing-filters.select
                name="status"
                label="Status"
                placeholder="All"
                :options="[
                    'paid' => 'Paid',
                    'pending' => 'Pending',
                    'overdue' => 'Overdue',
                    'cancelled' => 'Cancelled',
                ]"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Issued</th>
                        <th>Due</th>
                        <th>Status</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody id="invoice-rows">
                    @include('isp.invoices.partials.rows', ['invoices' => $invoices])
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>
@endsection
