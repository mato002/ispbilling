<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Integrations & platform billing') }}
        </h2>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @php
        $gb = $platform->global_billing ?? [];
        $pipes = $platform->payment_pipes ?? [];
    @endphp

    <div class="space-y-8">
        <form method="POST" action="{{ route('admin.integrations.update') }}" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Payment gateway pipes</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Control which aggregators ISPs may connect in their workspace. Disabled pipes block new credentials and edits that leave a method active.
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                    @foreach ($pipeLabels as $key => $label)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-600">
                            <input
                                type="checkbox"
                                name="pipes[{{ $key }}]"
                                value="1"
                                class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600"
                                {{ old("pipes.$key", $pipes[$key] ?? true) ? 'checked' : '' }}
                            />
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</span>
                                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">Pipe key: {{ $key }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Global tax / VAT</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        When enforcement is on, every new invoice uses the platform rate and label. When off, each ISP continues to use the rate from
                        <span class="font-mono text-xs">Settings → Billing</span>.
                    </p>
                </div>
                <div class="space-y-4 p-6">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            name="enforce_global_tax"
                            value="1"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600"
                            {{ old('enforce_global_tax', ! empty($gb['enforce_global_tax'])) ? 'checked' : '' }}
                        />
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Enforce platform tax on all invoices</span>
                    </label>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="global_tax_rate_percent">Platform tax rate (%)</label>
                            <input
                                id="global_tax_rate_percent"
                                name="global_tax_rate_percent"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                value="{{ old('global_tax_rate_percent', $gb['global_tax_rate_percent'] ?? 0) }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="global_tax_label">Line label (e.g. VAT)</label>
                            <input
                                id="global_tax_label"
                                name="global_tax_label"
                                type="text"
                                maxlength="64"
                                value="{{ old('global_tax_label', $gb['global_tax_label'] ?? 'VAT') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">API credentials vault</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Platform-level secrets are encrypted at rest with your application key. Leave a field blank to keep the current value.
                        Tenant-specific keys should continue to live under each ISP’s payment methods (also encrypted).
                    </p>
                </div>
                <div class="space-y-6 p-6">
                    <div>
                        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paystack platform billing</h4>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_paystack_public_key">Public key</label>
                                <input id="vault_paystack_public_key" name="vault_paystack_public_key" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['paystack_public_key'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_paystack_secret_key">Secret key</label>
                                <input id="vault_paystack_secret_key" name="vault_paystack_secret_key" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['paystack_secret_key'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Used for platform subscription invoices paid by ISP operators. This is separate from each ISP’s own Paystack keys. The webhook endpoint is <code>{{ url('/api/payments/platform/paystack/webhook') }}</code>.</p>
                    </div>
                    <div>
                        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Safaricom Daraja</h4>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_daraja_consumer_key">Consumer key</label>
                                <input id="vault_daraja_consumer_key" name="vault_daraja_consumer_key" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['daraja_consumer_key'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_daraja_consumer_secret">Consumer secret</label>
                                <input id="vault_daraja_consumer_secret" name="vault_daraja_consumer_secret" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['daraja_consumer_secret'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_daraja_passkey">Passkey</label>
                                <input id="vault_daraja_passkey" name="vault_daraja_passkey" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['daraja_passkey'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Flutterwave</h4>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_flutterwave_public_key">Public key</label>
                                <input id="vault_flutterwave_public_key" name="vault_flutterwave_public_key" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['flutterwave_public_key'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_flutterwave_secret_key">Secret key</label>
                                <input id="vault_flutterwave_secret_key" name="vault_flutterwave_secret_key" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['flutterwave_secret_key'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_flutterwave_encryption_key">Encryption key</label>
                                <input id="vault_flutterwave_encryption_key" name="vault_flutterwave_encryption_key" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['flutterwave_encryption_key'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pesapal</h4>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_pesapal_consumer_key">Consumer key</label>
                                <input id="vault_pesapal_consumer_key" name="vault_pesapal_consumer_key" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['pesapal_consumer_key'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="vault_pesapal_consumer_secret">Consumer secret</label>
                                <input id="vault_pesapal_consumer_secret" name="vault_pesapal_consumer_secret" type="password" autocomplete="off" class="w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="{{ ($vaultPresence['pesapal_consumer_secret'] ?? false) ? '•••• saved' : 'Not set' }}" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                    Save platform settings
                </button>
            </div>
        </form>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tenant credential registry</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Overview of encrypted ISP payment profiles. Open the ISP to manage users and subscriptions; ISPs edit secrets in their own settings.
                </p>
            </div>
            <div class="overflow-x-auto p-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">ISP</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Provider</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($tenantPipes as $row)
                            <tr>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                    {{ $row->isp->name ?? '—' }}
                                    <span class="block text-xs text-gray-500">{{ $row->isp->company_name ?? '' }}</span>
                                </td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $row->provider }}</td>
                                <td class="px-3 py-2">
                                    @if ($row->active)
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-200">Active</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('admin.isps.show', $row->isp_id) }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">ISP →</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">No tenant payment methods yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Clear vault slot</h4>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Remove a single platform vault key (e.g. rotate Daraja consumer secret).</p>
            <form method="POST" action="{{ route('admin.integrations.vault.clear') }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400" for="clear_vault_key">Key</label>
                    <select id="clear_vault_key" name="key" class="rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        @foreach (\App\Models\PlatformIntegration::vaultSchemaKeys() as $vk)
                            <option value="{{ $vk }}">{{ $vk }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-300 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">
                    Clear selected
                </button>
            </form>
        </div>
    </div>
</x-admin-layout>
