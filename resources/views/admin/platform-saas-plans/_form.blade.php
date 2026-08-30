@props([
    'plan' => null,
    'defaultTrialDays' => 14,
    'cancelUrl',
])

@php
    $features = $plan?->features ?? [];
    $nameValue = old('name', $plan?->name);
    $slugValue = old('slug', $plan?->slug);
    $descriptionValue = old('description', $plan?->description);
    $maxTenantsValue = old('max_tenants', $features['max_tenants'] ?? '');
    $maxCustomersValue = old('max_customers', $plan?->max_customers);
    $maxRoutersValue = old('max_routers', $features['max_routers'] ?? '');
    $speedLimitValue = old('speed_limit_mbps', $features['speed_limit_mbps'] ?? '');
    $monthlyPriceValue = old('monthly_price', $plan?->monthly_price ?? 0);
    $setupFeeValue = old('setup_fee', $plan?->setup_fee ?? 0);
    $trialDaysValue = old('trial_days', $plan?->trial_days ?? $defaultTrialDays);
    $sortOrderValue = old('sort_order', $plan?->sort_order ?? 0);
    $isActive = filter_var(old('is_active', $plan?->is_active ?? true), FILTER_VALIDATE_BOOLEAN);
    $advancedAnalytics = old('advanced_analytics', ! empty($features['advanced_analytics']));
    $smsAutomation = old('sms_automation', ! empty($features['sms_automation']));
@endphp

<div class="space-y-6">
    {{-- Primary configuration card --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:px-6">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Tier configuration</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Set infrastructure quotas and commercial terms for this SaaS tier.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 p-5 sm:p-6 lg:grid-cols-5">
            {{-- Left: Core Infrastructure Quotas (3 cols) --}}
            <div class="space-y-5 lg:col-span-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Core infrastructure quotas</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Capacity limits enforced per ISP workspace on this tier.</p>
                </div>

                <div>
                    <label class="isp-form-label" for="name">Plan name</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ $nameValue }}"
                        required
                        placeholder="e.g. Growth, Enterprise"
                        class="isp-form-input isp-form-input--emerald rounded-xl transition-all duration-150"
                    >
                    @error('name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="isp-form-label" for="max_tenants">Tenant limit</label>
                    <x-form-input-affix
                        id="max_tenants"
                        name="max_tenants"
                        type="number"
                        :value="$maxTenantsValue"
                        min="1"
                        unit="Tenants"
                        placeholder="Unlimited"
                    />
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Leave empty for unlimited ISP workspaces on this tier.</p>
                    @error('max_tenants')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="isp-form-label" for="max_customers">Customer limit</label>
                    <x-form-input-affix
                        id="max_customers"
                        name="max_customers"
                        type="number"
                        :value="$maxCustomersValue"
                        min="1"
                        unit="Subscribers"
                        placeholder="Unlimited"
                    />
                    @error('max_customers')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="isp-form-label" for="max_routers">Router limit</label>
                    <x-form-input-affix
                        id="max_routers"
                        name="max_routers"
                        type="number"
                        :value="$maxRoutersValue"
                        min="1"
                        unit="Devices"
                        placeholder="Unlimited"
                    />
                    @error('max_routers')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Right: Performance & Commercial (2 cols) --}}
            <div class="space-y-5 lg:col-span-2">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Performance &amp; commercial</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Billing rate, throughput cap, and availability.</p>
                </div>

                <div>
                    <label class="isp-form-label" for="speed_limit_mbps">Speed limit</label>
                    <x-form-input-affix
                        id="speed_limit_mbps"
                        name="speed_limit_mbps"
                        type="number"
                        :value="$speedLimitValue"
                        min="1"
                        suffix="Mbps"
                        placeholder="Unlimited"
                    />
                    @error('speed_limit_mbps')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="isp-form-label" for="monthly_price">Price</label>
                    <x-form-input-affix
                        id="monthly_price"
                        name="monthly_price"
                        type="number"
                        :value="$monthlyPriceValue"
                        step="0.01"
                        min="0"
                        prefix="KSh"
                        required
                    />
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Monthly recurring platform fee billed to the ISP.</p>
                    @error('monthly_price')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <x-form-status-toggle :checked="$isActive" />
            </div>
        </div>
    </div>

    {{-- Secondary metadata --}}
    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50 sm:p-6">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Additional settings</p>

        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label class="isp-form-label" for="slug">Slug</label>
                <input
                    id="slug"
                    type="text"
                    name="slug"
                    value="{{ $slugValue }}"
                    required
                    pattern="[a-z0-9\-]+"
                    placeholder="growth"
                    class="isp-form-input isp-form-input--emerald rounded-xl font-mono text-sm transition-all duration-150"
                >
                @error('slug')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="isp-form-label" for="sort_order">Display order</label>
                <input
                    id="sort_order"
                    type="number"
                    name="sort_order"
                    value="{{ $sortOrderValue }}"
                    min="0"
                    class="isp-form-input isp-form-input--emerald rounded-xl transition-all duration-150"
                >
            </div>
        </div>

        <div class="mt-5">
            <label class="isp-form-label" for="description">Description</label>
            <textarea
                id="description"
                name="description"
                rows="2"
                class="isp-form-textarea isp-form-input--emerald rounded-xl transition-all duration-150"
            >{{ $descriptionValue }}</textarea>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label class="isp-form-label" for="setup_fee">Setup fee</label>
                <x-form-input-affix
                    id="setup_fee"
                    name="setup_fee"
                    type="number"
                    :value="$setupFeeValue"
                    step="0.01"
                    min="0"
                    prefix="KSh"
                />
            </div>
            <div>
                <label class="isp-form-label" for="trial_days">Trial days</label>
                <x-form-input-affix
                    id="trial_days"
                    name="trial_days"
                    type="number"
                    :value="$trialDaysValue"
                    min="0"
                    max="90"
                    unit="Days"
                    required
                />
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Days until the first platform invoice after onboarding.</p>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-4 rounded-xl border border-dashed border-slate-200 px-4 py-3 dark:border-slate-700">
            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="checkbox" name="advanced_analytics" value="1" @checked($advancedAnalytics) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500/20">
                Advanced analytics
            </label>
            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="checkbox" name="sms_automation" value="1" @checked($smsAutomation) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500/20">
                SMS automation
            </label>
        </div>
    </div>

    {{-- Action panel --}}
    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
        @php
            $inFormModal = request()->header('Turbo-Frame') === 'pb-form-modal';
        @endphp
        @if ($inFormModal)
            <button type="button" class="pb-btn pb-btn-secondary" data-pb-form-modal-close>
                {{ __('Cancel') }}
            </button>
        @else
            <a href="{{ $cancelUrl }}" class="pb-btn pb-btn-secondary">{{ __('Cancel') }}</a>
        @endif
        <button type="submit" class="pb-btn pb-btn-success">
            {{ $plan ? __('Save tier changes') : __('Create tier') }}
        </button>
    </div>
</div>
