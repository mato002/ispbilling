<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IspPaymentMethod;
use App\Models\PlatformIntegration;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IntegrationsController extends Controller
{
    public function index()
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $platform = PlatformIntegration::current();
        $pipeLabels = PlatformIntegration::paymentPipeLabels();
        $vaultPresence = $platform->vaultKeyPresence();

        $tenantPipes = IspPaymentMethod::query()
            ->with('isp:id,name,company_name')
            ->orderBy('isp_id')
            ->orderBy('provider')
            ->get();

        return view('admin.integrations.index', compact('platform', 'pipeLabels', 'vaultPresence', 'tenantPipes'));
    }

    public function update(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $platform = PlatformIntegration::current();
        $pipeKeys = array_keys(PlatformIntegration::paymentPipeLabels());

        $validated = $request->validate([
            'pipes' => 'nullable|array',
            'pipes.*' => 'boolean',
            'global_tax_rate_percent' => 'required|numeric|min:0|max:100',
            'global_tax_label' => 'required|string|max:64',
            'enforce_global_tax' => 'nullable|boolean',
            'vault_daraja_consumer_key' => 'nullable|string|max:512',
            'vault_daraja_consumer_secret' => 'nullable|string|max:512',
            'vault_daraja_passkey' => 'nullable|string|max:512',
            'vault_paystack_public_key' => 'nullable|string|max:512',
            'vault_paystack_secret_key' => 'nullable|string|max:512',
            'vault_flutterwave_public_key' => 'nullable|string|max:512',
            'vault_flutterwave_secret_key' => 'nullable|string|max:512',
            'vault_flutterwave_encryption_key' => 'nullable|string|max:512',
            'vault_pesapal_consumer_key' => 'nullable|string|max:512',
            'vault_pesapal_consumer_secret' => 'nullable|string|max:512',
        ]);

        $pipes = [];
        foreach ($pipeKeys as $key) {
            $pipes[$key] = (bool) ($request->input("pipes.{$key}", false));
        }

        $platform->payment_pipes = $pipes;
        $platform->global_billing = [
            'global_tax_rate_percent' => (float) $validated['global_tax_rate_percent'],
            'global_tax_label' => $validated['global_tax_label'],
            'enforce_global_tax' => $request->boolean('enforce_global_tax'),
        ];

        $vault = $platform->credentials_vault ?? [];
        $vaultInputs = [
            'daraja_consumer_key' => $validated['vault_daraja_consumer_key'] ?? null,
            'daraja_consumer_secret' => $validated['vault_daraja_consumer_secret'] ?? null,
            'daraja_passkey' => $validated['vault_daraja_passkey'] ?? null,
            'paystack_public_key' => $validated['vault_paystack_public_key'] ?? null,
            'paystack_secret_key' => $validated['vault_paystack_secret_key'] ?? null,
            'flutterwave_public_key' => $validated['vault_flutterwave_public_key'] ?? null,
            'flutterwave_secret_key' => $validated['vault_flutterwave_secret_key'] ?? null,
            'flutterwave_encryption_key' => $validated['vault_flutterwave_encryption_key'] ?? null,
            'pesapal_consumer_key' => $validated['vault_pesapal_consumer_key'] ?? null,
            'pesapal_consumer_secret' => $validated['vault_pesapal_consumer_secret'] ?? null,
        ];
        foreach ($vaultInputs as $k => $v) {
            if (is_string($v) && $v !== '') {
                $vault[$k] = $v;
            }
        }
        $platform->credentials_vault = $vault;

        $platform->save();

        return redirect()->route('admin.integrations.index')
            ->with('success', 'Platform integrations, tax policy, and vault updated.');
    }

    public function clearVaultKey(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $key = $request->validate(['key' => 'required|string|max:128'])['key'];
        if (! in_array($key, PlatformIntegration::vaultSchemaKeys(), true)) {
            throw ValidationException::withMessages(['key' => ['Unknown vault key.']]);
        }

        $platform = PlatformIntegration::current();
        $vault = $platform->credentials_vault ?? [];
        unset($vault[$key]);
        $platform->credentials_vault = $vault;
        $platform->save();

        return redirect()->route('admin.integrations.index')
            ->with('success', 'Vault entry removed.');
    }
}
