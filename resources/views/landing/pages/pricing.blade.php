<section class="px-4 sm:px-6 lg:px-8" x-data="landingPricing({ defaultTrialDays: {{ $defaultTrialDays }} })">
    <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-2">
        <article class="pb-card p-5 sm:p-7">
            <h2 class="text-xl font-bold text-slate-900">Estimate usage-based pricing</h2>
            <p class="mt-1 text-sm text-slate-600">Adjust the estimates to match your current network.</p>
            <div class="mt-7 space-y-6">
                <label class="block">
                    <span class="flex items-center justify-between gap-3 text-sm font-semibold text-slate-700"><span>PPPoE subscribers</span><span x-text="pppoeUsers + ' users'"></span></span>
                    <input type="range" class="pb-range mt-3" min="0" max="500" step="5" x-model.number="pppoeUsers" @input="updateRangeBg($event.target)">
                </label>
                <label class="block">
                    <span class="flex items-center justify-between gap-3 text-sm font-semibold text-slate-700"><span>Hotspot revenue per month</span><span x-text="'KSh ' + format(hotspotRevenue)"></span></span>
                    <input type="range" class="pb-range mt-3" min="0" max="100000" step="500" x-model.number="hotspotRevenue" @input="updateRangeBg($event.target)">
                </label>
            </div>
            <dl class="mt-7 space-y-2 border-t border-slate-100 pt-5 text-sm">
                <div class="flex justify-between text-slate-600"><dt>PPPoE estimate</dt><dd x-text="'KSh ' + format(pppoeCost)"></dd></div>
                <div class="flex justify-between text-slate-600"><dt>Hotspot estimate</dt><dd x-text="'KSh ' + format(hotspotCost)"></dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-3 text-base font-bold text-slate-900"><dt>Monthly estimate</dt><dd x-text="'KSh ' + format(monthly)"></dd></div>
            </dl>
        </article>

        <div class="space-y-4">
            @forelse($platformSaasPlans as $plan)
                <article class="pb-card p-5 sm:p-7">
                    <h2 class="text-xl font-bold text-slate-900">{{ $plan->name }}</h2>
                    @if($plan->description)<p class="mt-1 text-sm text-slate-600">{{ $plan->description }}</p>@endif
                    <p class="mt-4 text-3xl font-extrabold text-slate-900">KSh {{ number_format((float) $plan->monthly_price, 0) }} <span class="text-sm font-normal text-slate-500">/ month</span></p>
                    @if($plan->max_customers)<p class="mt-1 text-sm text-slate-600">Up to {{ number_format($plan->max_customers) }} subscribers</p>@endif
                    @if(is_array($plan->features) && count($plan->features))
                        <ul class="mt-4 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                            @foreach(array_keys(array_filter($plan->features)) as $feature)<li>✓ {{ str_replace('_', ' ', ucfirst($feature)) }}</li>@endforeach
                        </ul>
                    @endif
                    @guest
                        <a href="{{ Route::has('register.isp') ? route('register.isp') : route('register') }}" class="pb-btn-primary mt-5">Start trial</a>
                    @endguest
                </article>
            @empty
                <article class="pb-card p-5 sm:p-7">
                    <h2 class="text-xl font-bold text-slate-900">Metered</h2>
                    <p class="mt-2 text-sm text-slate-600">KSh 25 per PPPoE subscriber plus 3% of hotspot revenue, with a KSh 500 monthly minimum.</p>
                    <ul class="mt-4 space-y-2 text-sm text-slate-700">
                        <li>✓ M-Pesa STK Push and payment reconciliation</li><li>✓ MikroTik router and subscriber management</li><li>✓ Customer portal and core operating modules</li>
                    </ul>
                </article>
                <article class="pb-card p-5 sm:p-7">
                    <h2 class="text-xl font-bold text-slate-900">Enterprise</h2>
                    <p class="mt-2 text-sm text-slate-600">Custom options for larger networks, dedicated support, and tailored integrations.</p>
                    <a href="{{ route('login') }}" class="pb-btn-secondary mt-5">Contact sales</a>
                </article>
            @endforelse
        </div>
    </div>
    <p class="mx-auto mt-5 max-w-7xl text-xs text-slate-500">Estimates are indicative. Confirm current plan terms before subscribing.</p>
</section>
