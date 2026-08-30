@extends('layouts.admin')

@section('title', $outage->title)

@section('header')
    <x-isp-page-header
        :title="$outage->title"
        module="Network"
        :description="$outage->router ? 'Router: '.$outage->router->name : 'No router linked'"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.outages.index') }}" class="pb-btn">All outages</a>
            @if ($outage->isActive())
                <form method="POST" action="{{ route('isp.outages.resolve', $outage) }}" class="inline" onsubmit="return confirm('Mark this outage as resolved?');">
                    @csrf
                    <button type="submit" class="pb-btn pb-btn-primary">Mark resolved</button>
                </form>
            @endif
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    @if (session('compensation_errors'))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <p class="font-semibold">Some compensations were skipped:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach (session('compensation_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Outage details --}}
            <div class="pb-card p-6">
                <h3 class="isp-settings-section-title mb-4">Outage details</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="isp-form-label mb-1">Status</dt>
                        <dd>
                            @if ($outage->isActive())
                                <x-status-badge status="active" />
                            @else
                                <x-status-badge status="resolved" />
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Router</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $outage->router?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Started</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $outage->started_at->format('M d, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Ended</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $outage->ended_at?->format('M d, Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="isp-form-label mb-1">Description</dt>
                        <dd class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $outage->description ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="isp-form-label mb-1">Recorded by</dt>
                        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ $outage->createdBy?->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Affected customers + compensation --}}
            <div class="pb-card p-6" x-data="{ selectAll: false, selected: @json(old('customer_ids', [])) }">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-dashed border-[var(--pb-border)] pb-4">
                    <div>
                        <h3 class="isp-settings-section-title">Affected customers</h3>
</div>
                </div>

                @if ($outage->router_id && $affectedCustomers->isNotEmpty())
                    <form method="POST" action="{{ route('isp.outages.apply-compensation', $outage) }}"
                          onsubmit="return confirm('Apply compensation to selected customers? This cannot be undone automatically.');">
                        @csrf

                        <div class="mb-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="isp-form-label">Compensation type</label>
                                <select name="compensation_type" class="isp-form-select" required>
                                    <option value="subscription_extension" @selected(old('compensation_type') === 'subscription_extension')>Extend subscription</option>
                                    <option value="voucher_extension" @selected(old('compensation_type') === 'voucher_extension')>Extend voucher validity</option>
                                </select>
                                @error('compensation_type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="isp-form-label">Duration</label>
                                <input type="number" name="duration_value" min="1" max="365" value="{{ old('duration_value', 1) }}" class="isp-form-input" required>
                                @error('duration_value')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="isp-form-label">Unit</label>
                                <select name="duration_unit" class="isp-form-select" required>
                                    <option value="hours" @selected(old('duration_unit', 'hours') === 'hours')>Hours</option>
                                    <option value="days" @selected(old('duration_unit') === 'days')>Days</option>
                                </select>
                                @error('duration_unit')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-[var(--pb-border)]">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900/50">
                                    <tr>
                                        <th class="px-4 py-3">
                                            <input type="checkbox"
                                                   class="h-4 w-4 rounded border-slate-300"
                                                   x-model="selectAll"
                                                   @change="document.querySelectorAll('[data-customer-checkbox]').forEach(el => { el.checked = selectAll; });">
                                        </th>
                                        <th class="px-4 py-3">Customer</th>
                                        <th class="px-4 py-3">Type</th>
                                        <th class="px-4 py-3">Username</th>
                                        <th class="px-4 py-3">Subscription ends</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--pb-border)]">
                                    @foreach ($affectedCustomers as $customer)
                                        @php $activeSub = $customer->subscriptions->first(); @endphp
                                        <tr>
                                            <td class="px-4 py-3">
                                                <input type="checkbox"
                                                       name="customer_ids[]"
                                                       value="{{ $customer->id }}"
                                                       data-customer-checkbox
                                                       class="h-4 w-4 rounded border-slate-300"
                                                       @checked(in_array($customer->id, old('customer_ids', [])))>
                                            </td>
                                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                                <a href="{{ route('isp.customers.show', $customer) }}" class="text-indigo-600 hover:text-indigo-700">{{ $customer->full_name }}</a>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ ucfirst($customer->connection_type ?? '—') }}</td>
                                            <td class="px-4 py-3 font-mono text-xs">{{ $customer->username }}</td>
                                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                                                {{ $activeSub?->end_date?->format('M d, Y') ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @error('customer_ids')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror

                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="pb-btn pb-btn-primary">Apply compensation</button>
                        </div>
                    </form>
                @elseif ($outage->router_id)
                    <x-empty-state
                        title="No customers on this router"
                        message="No hotspot or PPPoE customers are assigned to the selected router."
                    />
                @else
                    <p class="text-sm text-slate-600 dark:text-slate-400">Select a router on a new outage record to detect affected customers.</p>
                @endif
            </div>
        </div>

        {{-- Audit trail --}}
        <div class="space-y-6">
            <div class="pb-card p-6">
                <h3 class="isp-settings-section-title mb-4">Compensation log</h3>
                @if ($outage->compensationLogs->isEmpty())
                    <p class="text-sm text-slate-500 dark:text-slate-400">No compensations applied yet.</p>
                @else
                    <ul class="space-y-4">
                        @foreach ($outage->compensationLogs->sortByDesc('created_at') as $log)
                            <li class="rounded-xl border border-[var(--pb-border)] p-3 text-sm">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $log->customer?->full_name }}</p>
                                <p class="mt-1 text-slate-600 dark:text-slate-400">
                                    {{ str_replace('_', ' ', ucfirst($log->compensation_type)) }}
                                    · +{{ $log->durationLabel() }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $log->previous_expires_at }} → {{ $log->new_expires_at }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    By {{ $log->admin?->name ?? 'Admin' }}
                                    · {{ $log->created_at->format('M d, Y H:i') }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
