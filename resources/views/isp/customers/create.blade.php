@php
    $fields = $formFields ?? [];
@endphp

<x-modal-form :title="__('Create Customer')" :cancel-url="route('isp.customers.index')" maxWidth="4xl">
    <x-form-shell :action="route('isp.customers.store')">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Personal information</h3>

                @if ($fields['full_name']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="full_name">Full name @if($fields['full_name']['required'] ?? true)<span class="text-rose-500">*</span>@endif</label>
                        <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $fields['full_name']['default'] ?? '') }}" class="isp-form-input" @required($fields['full_name']['required'] ?? true) @readonly($fields['full_name']['read_only'] ?? false)>
                    </div>
                @endif

                @if ($fields['phone_number']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="phone_number">Phone number @if($fields['phone_number']['required'] ?? true)<span class="text-rose-500">*</span>@endif</label>
                        <input id="phone_number" type="text" name="phone_number" value="{{ old('phone_number', $fields['phone_number']['default'] ?? '') }}" class="isp-form-input" @required($fields['phone_number']['required'] ?? true) @readonly($fields['phone_number']['read_only'] ?? false)>
                    </div>
                @endif

                @if ($fields['email']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $fields['email']['default'] ?? '') }}" class="isp-form-input" @required($fields['email']['required'] ?? false) @readonly($fields['email']['read_only'] ?? false)>
                    </div>
                @endif

                @if ($fields['location']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="location">Location @if($fields['location']['required'] ?? true)<span class="text-rose-500">*</span>@endif</label>
                        <input id="location" type="text" name="location" value="{{ old('location', $fields['location']['default'] ?? '') }}" class="isp-form-input" @required($fields['location']['required'] ?? true) @readonly($fields['location']['read_only'] ?? false)>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Connection information</h3>

                @if ($fields['connection_type']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="connection_type">Connection type @if($fields['connection_type']['required'] ?? true)<span class="text-rose-500">*</span>@endif</label>
                        <select id="connection_type" name="connection_type" class="isp-form-select" @required($fields['connection_type']['required'] ?? true) @disabled($fields['connection_type']['read_only'] ?? false)>
                            <option value="pppoe" @selected(old('connection_type', $fields['connection_type']['default'] ?? 'pppoe') === 'pppoe')>PPPoE</option>
                            <option value="hotspot" @selected(old('connection_type') === 'hotspot')>Hotspot</option>
                        </select>
                    </div>
                @endif

                @if ($fields['router_id']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="router_id">Router @if($fields['router_id']['required'] ?? true)<span class="text-rose-500">*</span>@endif</label>
                        <select id="router_id" name="router_id" class="isp-form-select" @required($fields['router_id']['required'] ?? true) @disabled($fields['router_id']['read_only'] ?? false)>
                            <option value="">Select router</option>
                            @foreach ($routers as $router)
                                <option value="{{ $router->id }}" @selected((string) old('router_id', $fields['router_id']['default'] ?? '') === (string) $router->id)>{{ $router->name }} ({{ $router->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($fields['plan_id']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="plan_id">Plan @if($fields['plan_id']['required'] ?? true)<span class="text-rose-500">*</span>@endif</label>
                        <select id="plan_id" name="plan_id" class="isp-form-select" @required($fields['plan_id']['required'] ?? true) @disabled($fields['plan_id']['read_only'] ?? false)>
                            <option value="">Select plan</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected((string) old('plan_id', $fields['plan_id']['default'] ?? '') === (string) $plan->id)>{{ $plan->name }} - KES {{ number_format($plan->price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($fields['username']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="username">Username</label>
                        <input id="username" type="text" name="username" value="{{ old('username', $fields['username']['default'] ?? '') }}" class="isp-form-input" placeholder="Auto-generated if left empty" @readonly($fields['username']['read_only'] ?? false)>
                    </div>
                @endif

                @if ($fields['password']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="password">Password</label>
                        <input id="password" type="password" name="password" class="isp-form-input" placeholder="Auto-generated if left empty" @readonly($fields['password']['read_only'] ?? false)>
                    </div>
                @endif

                @if ($fields['installation_date']['visible'] ?? true)
                    <div>
                        <label class="isp-form-label" for="installation_date">Installation date @if($fields['installation_date']['required'] ?? true)<span class="text-rose-500">*</span>@endif</label>
                        <input id="installation_date" type="date" name="installation_date" value="{{ old('installation_date', $fields['installation_date']['default'] ?? '') }}" class="isp-form-input" @required($fields['installation_date']['required'] ?? true) @readonly($fields['installation_date']['read_only'] ?? false)>
                    </div>
                @endif
            </div>
        </div>

        <x-isp-custom-fields-form :definitions="$customFieldDefinitions ?? []" :values="[]" />

        @if ($fields['notes']['visible'] ?? true)
            <div>
                <label class="isp-form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="isp-form-textarea" @readonly($fields['notes']['read_only'] ?? false)>{{ old('notes', $fields['notes']['default'] ?? '') }}</textarea>
            </div>
        @endif

        <x-form-modal-actions :cancel-url="route('isp.customers.index')">
            <button type="submit" class="pb-btn pb-btn-primary">{{ __('Create customer') }}</button>
        </x-form-modal-actions>
    </x-form-shell>
</x-modal-form>
