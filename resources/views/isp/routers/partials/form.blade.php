@php
    /** @var \App\Models\Router|null $router */
    $router = $router ?? null;
    $owners = $owners ?? collect();
    $routerDefaults = $routerDefaults ?? [];
    $isEdit = isset($router) && $router->exists;
    $defaultPort = (int) ($routerDefaults['default_api_port'] ?? 8728);
    $defaultSsl = (bool) ($routerDefaults['use_ssl_by_default'] ?? false);
    $defaultLifecycle = $routerDefaults['lifecycle_default'] ?? 'live';
    $val = function (string $field, $default = null) use ($router) {
        return old($field, $router?->{$field} ?? $default);
    };
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-form-section title="Identity" description="Friendly name and where this device lives.">
        <div class="space-y-4">
            <div>
                <label class="isp-form-label mb-1">Router name *</label>
                <input type="text" name="name" value="{{ $val('name') }}" required
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="Edge-Nairobi-01" />
            </div>
            <div>
                <label class="isp-form-label mb-1">Branch / site</label>
                <input type="text" name="branch" value="{{ $val('branch') }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="Westlands" />
            </div>
            <div>
                <label class="isp-form-label mb-1">Location notes</label>
                <input type="text" name="location" value="{{ $val('location') }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="Rack A2, second floor" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="isp-form-label mb-1">Latitude</label>
                    <input type="number" step="0.0000001" name="latitude" value="{{ $val('latitude') }}"
                           class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div>
                    <label class="isp-form-label mb-1">Longitude</label>
                    <input type="number" step="0.0000001" name="longitude" value="{{ $val('longitude') }}"
                           class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
            </div>
            <div>
                <label class="isp-form-label mb-1">Tags <span class="text-slate-400">(comma-separated)</span></label>
                <input type="text" name="tags" value="{{ $val('tags') }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="core, ccr1036, hotspot" />
            </div>
        </div>
    </x-form-section>

    <x-form-section title="RouterOS API" description="Credentials used by the polling job and bulk operations.">
        <div class="space-y-4">
            <div>
                <label class="isp-form-label mb-1">IP / hostname *</label>
                <input type="text" name="ip_address" id="ip_address" value="{{ $val('ip_address') }}" required
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="192.168.88.1" />
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="isp-form-label mb-1">Port *</label>
                    <input type="number" name="port" id="port" value="{{ $val('port', $defaultPort) }}" required
                           class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div class="col-span-2 flex items-end gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="use_ssl" value="1" {{ $val('use_ssl', $defaultSsl) ? 'checked' : '' }}
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Use SSL
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="is_active" value="1" {{ $val('is_active', true) ? 'checked' : '' }}
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Active
                    </label>
                </div>
            </div>
            <div>
                <label class="isp-form-label mb-1">Username *</label>
                <input type="text" name="username" id="username" value="{{ $val('username') }}" required
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="admin" />
            </div>
            <div>
                <label class="isp-form-label mb-1">Password {{ $isEdit ? '' : '*' }}</label>
                <input type="password" name="password" id="password" {{ $isEdit ? '' : 'required' }}
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="{{ $isEdit ? 'Leave empty to keep current password' : '*****' }}" />
                <p class="mt-1 text-[11px] text-slate-500">Stored encrypted at rest.</p>
            </div>
        </div>
    </x-form-section>

    <x-form-section title="Asset / CMDB" description="Treats the router as a fixed asset for ERP reporting.">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="isp-form-label mb-1">Vendor</label>
                <input type="text" name="vendor" value="{{ $val('vendor') }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="MikroTik" />
            </div>
            <div>
                <label class="isp-form-label mb-1">Model</label>
                <input type="text" name="model" value="{{ $val('model') }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="CCR1036-12G-4S" />
            </div>
            <div>
                <label class="isp-form-label mb-1">Serial number</label>
                <input type="text" name="serial_number" value="{{ $val('serial_number') }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" />
            </div>
            <div>
                <label class="isp-form-label mb-1">MAC address</label>
                <input type="text" name="mac_address" value="{{ $val('mac_address') }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900"
                       placeholder="AA:BB:CC:DD:EE:FF" />
            </div>
            <div>
                <label class="isp-form-label mb-1">Purchase date</label>
                <input type="date" name="purchase_date" value="{{ $val('purchase_date') ? \Illuminate\Support\Carbon::parse($val('purchase_date'))->toDateString() : '' }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" />
            </div>
            <div>
                <label class="isp-form-label mb-1">Warranty expiry</label>
                <input type="date" name="warranty_expiry" value="{{ $val('warranty_expiry') ? \Illuminate\Support\Carbon::parse($val('warranty_expiry'))->toDateString() : '' }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" />
            </div>
            <div>
                <label class="isp-form-label mb-1">Monthly cost</label>
                <input type="number" step="0.01" name="monthly_cost" value="{{ $val('monthly_cost') }}"
                       class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900" />
            </div>
            <div>
                <label class="isp-form-label mb-1">Lifecycle</label>
                <select name="lifecycle_status" class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900">
                    @foreach (['live', 'stock', 'maintenance', 'retired'] as $opt)
                        <option value="{{ $opt }}" @selected($val('lifecycle_status', $defaultLifecycle) === $opt)>{{ ucfirst($opt) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-form-section>

    <x-form-section title="Ownership" description="Who is responsible for this router?">
        <div class="space-y-4">
            <div>
                <label class="isp-form-label mb-1">Owner / on-call engineer</label>
                <select name="owner_user_id" class="w-full rounded-md border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-900">
                    <option value="">— Unassigned —</option>
                    @foreach ($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((string) $val('owner_user_id') === (string) $owner->id)>
                            {{ $owner->name }} <span class="text-slate-400">({{ $owner->email }})</span>
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-slate-500">Used for routing outage notifications.</p>
            </div>
        </div>
    </x-form-section>
</div>
