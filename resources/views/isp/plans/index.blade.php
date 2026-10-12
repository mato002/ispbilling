@extends('layouts.admin')

@section('title', 'Plans')

@section('header')
    <x-isp-page-header
        title="Plans &amp; packages"
        module="Catalog"
        description="Hotspot, PPPoE, data bundles, unlimited, static IP, installation, and add-on packages."
        primaryLabel="Create plan"
        :primaryHref="route('isp.plans.create')"
    />
@endsection

@section('content')
    @php use App\Support\PlanPackageTypes; @endphp
    <div x-data="{ voucher: { open: false, planId: '', planName: '', price: '', speed: '', hours: '', data: '', type: 'time' }, openVoucher(plan) { this.voucher = { ...this.voucher, ...plan, open: true }; } }">
    <x-resource-index
        :filter-action="route('isp.plans.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$plans"
        :empty="$plans->isEmpty()"
        empty-title="No plans found"
        empty-message="Create a plan to attach subscriptions and vouchers."
        empty-cta-label="Create plan"
        :empty-cta-href="route('isp.plans.create')"
    >
        <x-slot:filters>
            <x-listing-filters.select
                name="package_type"
                label="Package type"
                placeholder="All types"
                class="min-w-[180px]"
                :options="PlanPackageTypes::labels()"
            />
            <x-listing-filters.select
                name="access_type"
                label="Access type"
                placeholder="All"
                :options="[
                    'pppoe' => 'PPPoE',
                    'hotspot' => 'Hotspot',
                ]"
            />
            <x-listing-filters.select
                name="is_active"
                label="Status"
                placeholder="All"
                :options="['1' => 'Active', '0' => 'Inactive']"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-data-table>
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Package type</th>
                        <th>Access</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Limits</th>
                        <th>Subs</th>
                        <th>Status</th>
                        <th>Public</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plans as $plan)
                        <x-listing-table.row :href="route('isp.plans.show', $plan)">
                            <td class="max-w-[220px]">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $plan->name }}</div>
                                <div class="truncate text-sm text-slate-500 dark:text-slate-400">{{ $plan->description ?: '—' }}</div>
                            </td>
                            <td>
                                <span class="pb-pill">{{ $plan->packageTypeLabel() }}</span>
                            </td>
                            <td>
                                @if ($plan->access_type === 'hotspot')
                                    <span class="pb-pill pb-pill-cyan">Hotspot</span>
                                @elseif (in_array($plan->package_type, [PlanPackageTypes::INSTALLATION_SERVICE, PlanPackageTypes::ADDON_SERVICE, PlanPackageTypes::STATIC_IP_ADDON], true))
                                    <span class="text-xs text-slate-500">—</span>
                                @else
                                    <span class="pb-pill pb-pill-info">PPPoE</span>
                                @endif
                            </td>
                            <td class="font-mono whitespace-nowrap">KES {{ number_format($plan->price, 2) }}</td>
                            <td>
                                {{ $plan->duration_days }}d
                                @if ($plan->is_one_time)
                                    <span class="text-[10px] text-slate-400">· once</span>
                                @endif
                            </td>
                            <td class="max-w-[160px] truncate text-sm text-slate-600 dark:text-slate-300">
                                @if ($plan->data_limit_mb)
                                    {{ number_format($plan->data_limit_mb) }} MB
                                @elseif ($plan->isUnlimitedPackage())
                                    {{ $plan->formatted_speed }}
                                @elseif ($plan->service_type)
                                    {{ str_replace('_', ' ', $plan->service_type) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="tabular-nums">{{ $plan->subscriptions_count }}</td>
                            <td>
                                @if ($plan->archived_at)
                                    <span class="pb-pill">Archived</span>
                                @elseif ($plan->is_active)
                                    <x-status-badge status="active" />
                                @else
                                    <x-status-badge status="inactive" />
                                @endif
                            </td>
                            <td>
                                @if ($plan->customer_visible && ! $plan->archived_at)
                                    <span class="pb-pill pb-pill-success">Visible</span>
                                @else
                                    <span class="pb-pill">Hidden</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('isp.plans.show', $plan)" label="View" />
                                    <x-table-actions.link :href="route('isp.plans.edit', $plan)" label="Edit" />
                                    @if ($plan->access_type === 'hotspot')
                                        <button type="button" @click.prevent="openVoucher({ planId: {{ $plan->id }}, planName: @js($plan->name), price: {{ (float) $plan->price }}, speed: @js($plan->speed_profile ?? ''), hours: {{ (int) ($plan->time_limit_hours ?: $plan->duration_days * 24) }}, data: {{ (int) ($plan->data_limit_mb ?? 0) }}, type: @js($plan->billing_type === 'data' ? 'data' : 'time') })" class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Generate vouchers</button>
                                    @endif
                                    <x-table-actions.link :href="route('isp.vouchers.index', ['plan_id' => $plan->id])" label="View vouchers" />
                                    <x-table-actions.link :href="route('isp.active-users.index', ['plan_id' => $plan->id])" label="View active sessions" />
                                    <x-table-actions.form :action="route('isp.plans.duplicate', $plan)" label="Duplicate plan" :danger="false" turboFrame="_top" />
                                    @if ($plan->access_type === 'hotspot')
                                        <x-table-actions.form :action="route('isp.plans.sync-profile', $plan)" label="Sync MikroTik profile" :danger="false" turboFrame="_top" onsubmit="confirmFormSubmit(event, {title: 'Sync hotspot profile', text: 'Create or update this plan profile on all active routers?', icon: 'question', confirmButtonText: 'Sync profile'}); return false;" />
                                    @endif
                                    <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                                    @if ($plan->archived_at)
                                        <x-table-actions.form :action="route('isp.plans.archive', $plan)" label="Restore archived plan" :danger="false" turboFrame="_top" />
                                    @else
                                        <x-table-actions.form :action="route('isp.plans.toggle-public', $plan)" :label="$plan->customer_visible ? 'Hide from public' : 'Publish to public'" :danger="false" turboFrame="_top" />
                                        <x-table-actions.form :action="route('isp.plans.toggle-active', $plan)" :label="$plan->is_active ? 'Disable plan' : 'Enable plan'" :danger="false" turboFrame="_top" />
                                        <x-table-actions.form :action="route('isp.plans.archive', $plan)" label="Archive plan" :danger="false" turboFrame="_top" onsubmit="confirmFormSubmit(event, {title: 'Archive plan', text: 'Archive this plan? Its existing subscribers and reports will be preserved.', icon: 'warning', confirmButtonText: 'Archive plan'}); return false;" />
                                    @endif
                                    @if ($plan->subscriptions_count === 0)
                                        <x-table-actions.form :action="route('isp.plans.destroy', $plan)" method="DELETE" label="Delete plan" turboFrame="_top" onsubmit="confirmFormSubmit(event, {title: 'Delete plan', text: 'Permanently delete this plan?', icon: 'warning', confirmButtonText: 'Delete plan', confirmButtonColor: '#dc2626'}); return false;" />
                                    @else
                                        <span class="block px-3 py-2 text-xs text-slate-400" title="Plans with subscribers cannot be deleted">Delete unavailable · has subscribers</span>
                                    @endif
                                </x-table-actions>
                            </td>
                        </x-listing-table.row>
                    @endforeach
                </tbody>
            </x-data-table>
        </x-slot:table>
    </x-resource-index>

    <div x-show="voucher.open" x-cloak class="fixed inset-0 z-[11000] flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4" @keydown.escape.window="voucher.open = false" @click.self="voucher.open = false">
        <section class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Voucher batch</p><h2 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">Generate for <span x-text="voucher.planName"></span></h2><p class="mt-1 text-sm text-slate-500">The generated codes will be linked to this plan.</p></div>
                <button type="button" @click="voucher.open = false" class="rounded-lg px-3 py-1 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close">✕</button>
            </div>
            <form method="POST" action="{{ route('isp.vouchers.store-bulk') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="plan_id" x-model="voucher.planId">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Router
                        <select name="router_id" required class="isp-form-select mt-1 w-full">
                            <option value="">Select router</option>
                            @foreach($routers as $router)<option value="{{ $router->id }}">{{ $router->name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Quantity
                        <input type="number" name="quantity" min="1" max="1000" value="50" required class="isp-form-input mt-1 w-full">
                    </label>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Code prefix
                        <input type="text" name="code_prefix" maxlength="6" pattern="[A-Za-z0-9]{0,6}" placeholder="e.g. START" class="isp-form-input mt-1 w-full">
                        <span class="mt-1 block text-xs text-slate-500">Up to 6 letters or numbers; codes remain 12 characters.</span>
                    </label>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Voucher type
                        <select name="voucher_type" x-model="voucher.type" class="isp-form-select mt-1 w-full"><option value="time">Time based</option><option value="data">Data based</option></select>
                    </label>
                    <label x-show="voucher.type === 'time'" class="text-sm font-medium text-slate-700 dark:text-slate-200">Time limit (hours)
                        <input type="number" name="time_limit_hours" min="1" :value="voucher.hours" :required="voucher.type === 'time'" class="isp-form-input mt-1 w-full">
                    </label>
                    <label x-show="voucher.type === 'data'" class="text-sm font-medium text-slate-700 dark:text-slate-200">Data limit (MB)
                        <input type="number" name="data_limit_mb" min="1" :value="voucher.data" :required="voucher.type === 'data'" class="isp-form-input mt-1 w-full">
                    </label>
                </div>
                <input type="hidden" name="speed_profile" :value="voucher.speed">
                <input type="hidden" name="price" :value="voucher.price">
                @if($routers->isEmpty())<p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Add an active router before generating vouchers.</p>@endif
                <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <button type="button" @click="voucher.open = false" class="pb-btn">Cancel</button>
                    <button type="submit" @disabled($routers->isEmpty()) class="pb-btn pb-btn-primary">Generate vouchers</button>
                </div>
            </form>
        </section>
    </div>
    </div>
@endsection
