<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Platform finance') }}
            </h2>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Aggregated cash flow, per-ISP revenue, commission ledger, and ISP payout workflow.
            </div>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Lifetime gross (completed)</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">KES {{ number_format($totals['gross_revenue'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Commission accrued (ledger)</p>
            <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">KES {{ number_format($totals['commission_accrued'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Default commission rate</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($commissionPercent, 2) }}%</p>
            <form method="POST" action="{{ route('admin.finance.commission') }}" class="mt-3 flex flex-wrap items-end gap-2">
                @csrf
                <label class="text-xs text-gray-500 dark:text-gray-400 sr-only" for="platform_commission_percent">Rate %</label>
                <input id="platform_commission_percent" name="platform_commission_percent" type="number" step="0.01" min="0" max="100"
                       value="{{ old('platform_commission_percent', $commissionPercent) }}"
                       class="w-24 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" />
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Save rate</button>
            </form>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Applied when payments transition to completed (e.g. 5% → fee stored on each payment).</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Platform SaaS billing policy</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            Controls grace period, trial defaults for new plans, console lock on unpaid invoices, and optional customer-network kill switch when a tenant is suspended.
        </p>
        <form method="POST" action="{{ route('admin.finance.saas-policy') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            @csrf
            <div>
                <label for="saas_payment_grace_days" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment grace (days)</label>
                <input id="saas_payment_grace_days" name="saas_payment_grace_days" type="number" min="0" max="90" required
                       value="{{ old('saas_payment_grace_days', $saasGraceDays) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Days after invoice before suspend. Use <strong>0</strong> for immediate suspend after grace window ends on due date.</p>
            </div>
            <div>
                <label for="saas_default_trial_days" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Default trial (new plans)</label>
                <input id="saas_default_trial_days" name="saas_default_trial_days" type="number" min="0" max="90" required
                       value="{{ old('saas_default_trial_days', $saasDefaultTrialDays) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" />
            </div>
            <div class="space-y-3">
                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="saas_lock_on_past_due" value="1" class="mt-1 rounded border-gray-300 dark:border-gray-600"
                           @checked(old('saas_lock_on_past_due', $saasLockOnPastDue)) />
                    <span>
                        Lock console on first unpaid invoice
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Blocks dashboard/API immediately when billing enters <code class="text-xs">past_due</code> (Platform subscription page stays open).</span>
                    </span>
                </label>
                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="saas_suspend_customer_services" value="1" class="mt-1 rounded border-gray-300 dark:border-gray-600"
                           @checked(old('saas_suspend_customer_services', $saasSuspendCustomerServices)) />
                    <span>
                        Suspend customer hotspot/PPPoE on platform suspend
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Runs RouterOS kill switch when SaaS suspend fires; restores marked customers after platform payment.</span>
                    </span>
                </label>
            </div>
            <div>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 w-full md:w-auto">Save SaaS policy</button>
            </div>
        </form>
        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 pt-3">
            Scheduler: ensure cron runs <code class="text-xs">php artisan schedule:run</code> every minute so <code class="text-xs">platform-saas:daily</code> (06:10) issues invoices, STK, and suspensions.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Revenue integrity</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Commission lines are stored immutably on each completed <strong>payments</strong> row (<code class="text-xs">platform_fee_amount</code> / <code class="text-xs">platform_fee_rate</code>). The ledger below is the canonical audit trail — no duplicate commission table required.</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Payout workflow uses <code class="text-xs">isp_payouts</code> against those accrued balances.</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">SaaS billing calendar (next 60 tenants by date)</h3>
            <div class="max-h-72 overflow-y-auto text-sm">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs text-gray-500 dark:text-gray-300">
                        <tr>
                            <th class="px-3 py-2">ISP</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Next bill</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($saasBillingForecast as $row)
                            <tr>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                    <a href="{{ route('admin.isps.show', $row) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ $row->name }}</a>
                                    <div class="text-[10px] text-gray-500">{{ $row->company_name }}</div>
                                </td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row->saas_subscription_status ?? '—' }}</td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">
                                    {{ $row->saas_next_billing_at?->timezone(config('app.timezone'))->format('M j, Y') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-4 text-gray-500">No ISPs with <code class="text-xs">saas_next_billing_at</code> set.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Monthly platform cash flow</h3>
                <form method="GET" action="{{ route('admin.finance.index') }}" class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="ledger_page" value="{{ request('ledger_page') }}" />
                    <input type="hidden" name="payouts_page" value="{{ request('payouts_page') }}" />
                    <select name="months" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm" onchange="this.form.submit()">
                        @foreach ([6, 12, 18, 24] as $m)
                            <option value="{{ $m }}" @selected($months == $m)>{{ $m }} months</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="space-y-3">
                @forelse ($monthlyFlow as $row)
                    @php
                        $maxM = max(1, (float) ($monthlyFlow->max('revenue') ?: 1));
                        $w = min(100, ((float) $row->revenue / $maxM) * 100);
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-300 mb-1">
                            <span>{{ $row->ym }}</span>
                            <span class="font-semibold">KES {{ number_format((float) $row->revenue, 2) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $w }}%"></div>
                        </div>
                        @if ((float) $row->commission > 0)
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Commission: KES {{ number_format((float) $row->commission, 2) }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No completed payments in this window.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Revenue by ISP (last 12 months)</h3>
            <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                @forelse ($revenueByIsp as $row)
                    @php $w = $maxIspBar > 0 ? min(100, ((float) $row->total / $maxIspBar) * 100) : 0; @endphp
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-300 mb-1">
                            <span class="truncate mr-2" title="{{ $row->name }}">{{ $row->name }}</span>
                            <span class="font-semibold shrink-0">KES {{ number_format((float) $row->total, 2) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $w }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No data.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Commission owed by ISP (from payment ledger)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ISP</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Commission</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Fee lines</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($commissionByIsp as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $row->name }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-indigo-600 dark:text-indigo-400">KES {{ number_format((float) $row->commission, 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">{{ (int) $row->payment_rows }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No commission rows yet. Set a rate above and complete new payments.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Commission ledger (per payment)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">When</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">ISP</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Ref</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Gross</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Rate</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Fee</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($ledger as $p)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ ($p->processed_at ?? $p->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $p->isp?->name ?? '—' }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-700 dark:text-gray-200">{{ $p->payment_reference }}</td>
                            <td class="px-4 py-2 text-right">KES {{ number_format((float) $p->amount, 2) }}</td>
                            <td class="px-4 py-2 text-right">{{ $p->platform_fee_rate !== null ? number_format((float) $p->platform_fee_rate, 2).'%' : '—' }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-indigo-600 dark:text-indigo-400">KES {{ number_format((float) $p->platform_fee_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $ledger->links() }}</div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Record ISP payout</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Use when the platform settles net funds to an ISP owner. Amounts are manual records for approval and audit.</p>
            <form method="POST" action="{{ route('admin.finance.payouts.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">ISP</label>
                    <select name="isp_id" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <option value="">Select ISP</option>
                        @foreach ($isps as $isp)
                            <option value="{{ $isp->id }}" @selected(old('isp_id') == $isp->id)>{{ $isp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Amount</label>
                        <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Currency</label>
                        <input name="currency" type="text" maxlength="8" placeholder="KES" value="{{ old('currency') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Reference (optional)</label>
                    <input name="reference" type="text" value="{{ old('reference') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                    Create pending payout
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Payout totals by status</h3>
            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1 mb-4">
                @foreach (['pending', 'approved', 'paid', 'rejected'] as $st)
                    @php $agg = $payoutTotalsByStatus->get($st); @endphp
                    <li class="flex justify-between"><span class="capitalize">{{ $st }}</span><span>{{ $agg ? 'KES '.number_format((float) $agg->total, 2).' ('.$agg->c.')' : '—' }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Payout queue</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Created</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">ISP</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Amount</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($payouts as $po)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $po->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $po->isp?->name }}</td>
                            <td class="px-4 py-2 text-right font-medium">{{ $po->currency }} {{ number_format((float) $po->amount, 2) }}</td>
                            <td class="px-4 py-2 capitalize text-gray-700 dark:text-gray-200">{{ $po->status }}</td>
                            <td class="px-4 py-2">
                                <form method="POST" action="{{ route('admin.finance.payouts.update', $po) }}" class="inline-flex flex-wrap gap-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="approved" />
                                    <button type="submit" class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-900 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-100" @disabled($po->status === \App\Models\IspPayout::STATUS_APPROVED || $po->status === \App\Models\IspPayout::STATUS_PAID)>Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.finance.payouts.update', $po) }}" class="inline-flex flex-wrap gap-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="paid" />
                                    <button type="submit" class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-900 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-100" @disabled($po->status === \App\Models\IspPayout::STATUS_PAID)>Mark paid</button>
                                </form>
                                <form method="POST" action="{{ route('admin.finance.payouts.update', $po) }}" class="inline-flex flex-wrap gap-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="rejected" />
                                    <button type="submit" class="rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-900 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-100" @disabled($po->status === \App\Models\IspPayout::STATUS_PAID)>Reject</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $payouts->links() }}</div>
    </div>
</x-admin-layout>
