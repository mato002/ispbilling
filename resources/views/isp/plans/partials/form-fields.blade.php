@php
    use App\Support\PlanPackageTypes;

    $planModel = $plan ?? null;
    $fields = $formFields ?? [];
    $packageType = old('package_type', $planModel->package_type ?? PlanPackageTypes::PPPOE);
    $validityPeriod = old('validity_period', $planModel->validity_period ?? PlanPackageTypes::VALIDITY_MONTHLY);
@endphp

<div
    class="grid grid-cols-1 gap-6 md:grid-cols-2"
    x-data="{
        packageType: '{{ $packageType }}',
        validityPeriod: '{{ $validityPeriod }}',
        billingType: '{{ old('billing_type', $planModel->billing_type ?? 'unlimited') }}',
        isConnection() {
            return ['hotspot','pppoe','data_bundle','unlimited'].includes(this.packageType);
        },
        showAccessType() {
            return ['hotspot','pppoe','data_bundle','unlimited'].includes(this.packageType);
        },
        showDataLimit() {
            return this.packageType === 'data_bundle' || this.packageType === 'hotspot' || this.billingType === 'data';
        },
        showTimeLimit() {
            return this.packageType === 'hotspot' || this.billingType === 'time';
        },
        showSpeedFields() {
            return ['hotspot','pppoe','data_bundle','unlimited'].includes(this.packageType);
        },
        showValidity() {
            return ['hotspot','pppoe','data_bundle','unlimited'].includes(this.packageType);
        },
        showInstallation() {
            return this.packageType === 'installation_service';
        },
        showAddon() {
            return this.packageType === 'addon_service';
        },
        showStaticIp() {
            return this.packageType === 'static_ip_addon';
        }
    }"
>
    <div class="space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Basic information</h3>
        <x-form-controlled :fields="$fields" name="name">
        <div>
            <label class="isp-form-label" for="name">Plan name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $planModel->name ?? '') }}" @required($fields['name']['required'] ?? true) class="isp-form-input @error('name') isp-form-input--invalid @enderror" @readonly($fields['name']['read_only'] ?? false)>
            @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        </x-form-controlled>
        <x-form-controlled :fields="$fields" name="description">
        <div>
            <label class="isp-form-label" for="description">Description</label>
            <textarea id="description" name="description" rows="3" class="isp-form-textarea" @readonly($fields['description']['read_only'] ?? false)>{{ old('description', $planModel->description ?? '') }}</textarea>
        </div>
        </x-form-controlled>
        <x-form-controlled :fields="$fields" name="package_type">
        <div>
            <label class="isp-form-label" for="package_type">Package type</label>
            <select id="package_type" name="package_type" @required($fields['package_type']['required'] ?? true) class="isp-form-select" x-model="packageType" @disabled($fields['package_type']['read_only'] ?? false)>
                @foreach (PlanPackageTypes::labels() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        </x-form-controlled>
        <div x-show="showAccessType()">
            <label class="isp-form-label" for="access_type">Connection access</label>
            <select id="access_type" name="access_type" class="isp-form-select">
                <option value="pppoe" @selected(old('access_type', $planModel->access_type ?? 'pppoe') === 'pppoe')>PPPoE</option>
                <option value="hotspot" @selected(old('access_type', $planModel->access_type ?? '') === 'hotspot')>Hotspot</option>
            </select>
        </div>
        <div>
            <label class="isp-form-label" for="price">Price (KES)</label>
            <input id="price" type="number" name="price" step="0.01" min="0" value="{{ old('price', $planModel->price ?? '') }}" required class="isp-form-input">
        </div>
        <div x-show="showValidity()">
            <label class="isp-form-label" for="validity_period">Validity period</label>
            <select id="validity_period" name="validity_period" class="isp-form-select" x-model="validityPeriod">
                @foreach (PlanPackageTypes::validityPeriods() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="showValidity() && validityPeriod === 'custom'">
            <label class="isp-form-label" for="duration_days">Custom duration (days)</label>
            <input id="duration_days" type="number" name="duration_days" min="1" value="{{ old('duration_days', $planModel->duration_days ?? 30) }}" class="isp-form-input">
        </div>
        <div x-show="showStaticIp() || showAddon()">
            <label class="isp-form-label" for="duration_days_addon">Billing period (days)</label>
            <input id="duration_days_addon" type="number" name="duration_days" min="1" value="{{ old('duration_days', $planModel->duration_days ?? 30) }}" class="isp-form-input">
        </div>
    </div>

    <div class="space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Limits &amp; services</h3>

        <div x-show="isConnection() && packageType !== 'data_bundle' && packageType !== 'unlimited'">
            <label class="isp-form-label" for="billing_type">Billing type</label>
            <select id="billing_type" name="billing_type" class="isp-form-select" x-model="billingType">
                <option value="unlimited">Unlimited</option>
                <option value="data">Data limit</option>
                <option value="time">Time limit</option>
            </select>
        </div>

        <div x-show="showDataLimit()">
            <label class="isp-form-label" for="data_limit_mb">Data limit (MB)</label>
            <input id="data_limit_mb" type="number" name="data_limit_mb" min="1" value="{{ old('data_limit_mb', $planModel->data_limit_mb ?? '') }}" class="isp-form-input" placeholder="e.g. 5120 for 5GB">
</div>

        <div x-show="showTimeLimit()">
            <label class="isp-form-label" for="time_limit_hours">Time limit (hours)</label>
            <input id="time_limit_hours" type="number" name="time_limit_hours" min="0" value="{{ old('time_limit_hours', $planModel->time_limit_hours ?? '') }}" class="isp-form-input">
            <label class="isp-form-label mt-3" for="time_limit_minutes">Time limit (minutes, for short passes)</label>
            <input id="time_limit_minutes" type="number" name="time_limit_minutes" min="1" max="10080" value="{{ old('time_limit_minutes', $planModel->time_limit_minutes ?? '') }}" class="isp-form-input" placeholder="e.g. 30">
        </div>

        <div x-show="showSpeedFields()">
            <div>
                <label class="isp-form-label" for="download_speed_kbps">Download speed (Kbps)</label>
                <input id="download_speed_kbps" type="number" name="download_speed_kbps" min="0" value="{{ old('download_speed_kbps', $planModel->download_speed_kbps ?? '') }}" class="isp-form-input">
            </div>
            <div class="mt-3">
                <label class="isp-form-label" for="upload_speed_kbps">Upload speed (Kbps)</label>
                <input id="upload_speed_kbps" type="number" name="upload_speed_kbps" min="0" value="{{ old('upload_speed_kbps', $planModel->upload_speed_kbps ?? '') }}" class="isp-form-input">
            </div>
            <div class="mt-3">
                <label class="isp-form-label" for="speed_profile">Speed profile name</label>
                <input id="speed_profile" type="text" name="speed_profile" value="{{ old('speed_profile', $planModel->speed_profile ?? '') }}" class="isp-form-input">
            </div>
            <div class="mt-3">
                <label class="isp-form-label" for="device_limit">Device limit</label>
                <input id="device_limit" type="number" name="device_limit" min="0" value="{{ old('device_limit', $planModel->device_limit ?? '') }}" class="isp-form-input" placeholder="Unlimited if empty">
            </div>
        </div>

        <div x-show="showInstallation()">
            <label class="isp-form-label" for="service_type_install">Service type</label>
            <select id="service_type_install" name="service_type" class="isp-form-select">
                @foreach (PlanPackageTypes::installationServiceTypes() as $value => $label)
                    <option value="{{ $value }}" @selected(old('service_type', $planModel->service_type ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
</div>

        <div x-show="showAddon()">
            <label class="isp-form-label" for="service_type_addon">Add-on type</label>
            <select id="service_type_addon" name="service_type" class="isp-form-select">
                @foreach (PlanPackageTypes::addonServiceTypes() as $value => $label)
                    <option value="{{ $value }}" @selected(old('service_type', $planModel->service_type ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_recurring" value="1" class="rounded border-slate-300 text-indigo-600" @checked(old('is_recurring', $planModel->is_recurring ?? true))>
                Recurring billing
            </label>
        </div>

        <div x-show="showStaticIp()">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="requires_pppoe_plan" value="1" class="rounded border-slate-300 text-indigo-600" @checked(old('requires_pppoe_plan', $planModel->requires_pppoe_plan ?? true))>
                Requires active PPPoE (or unlimited) subscription
            </label>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_recurring" value="1" class="rounded border-slate-300 text-indigo-600" @checked(old('is_recurring', $planModel->is_recurring ?? true))>
                Recurring monthly charge
            </label>
        </div>

        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $planModel->is_active ?? true)) class="rounded border-[var(--pb-border)]">
            Active
        </label>
    </div>
</div>
