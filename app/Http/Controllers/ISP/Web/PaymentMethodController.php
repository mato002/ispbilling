<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\IspPaymentMethod;
use App\Models\ISP;
use App\Models\PlatformIntegration;
use App\Services\MpesaService;
use App\Support\IspPaymentExperience;
use App\Support\IspSettingsChangeTracker;
use App\Support\MpesaGatewayConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            abort(403, 'Only ISP admins can access payment methods');
        }

        $isp = ISP::query()->findOrFail($ispId);

        $paymentMethod = IspPaymentMethod::forIsp($ispId)
            ->mpesaProvider()
            ->first();

        $webhookToken = $paymentMethod?->webhook_token ?: Str::random(48);
        $stkCallback = $paymentMethod && $paymentMethod->webhook_token
            ? url('/api/payments/mpesa/callback/'.$paymentMethod->webhook_token)
            : url('/api/payments/mpesa/callback/'.$webhookToken);
        $c2bValidation = $paymentMethod && $paymentMethod->webhook_token
            ? url('/api/payments/mpesa/c2b/validation/'.$paymentMethod->webhook_token)
            : url('/api/payments/mpesa/c2b/validation/'.$webhookToken);
        $c2bConfirmation = $paymentMethod && $paymentMethod->webhook_token
            ? url('/api/payments/mpesa/c2b/confirmation/'.$paymentMethod->webhook_token)
            : url('/api/payments/mpesa/c2b/confirmation/'.$webhookToken);
        $stkTimeout = url('/api/payments/mpesa/timeout');

        $credentials = $paymentMethod
            ? MpesaGatewayConfig::normalizeCredentials($paymentMethod->credentials ?? [])
            : MpesaGatewayConfig::unconfigured();

        $paymentExperience = IspPaymentExperience::forIsp($isp);

        return view('isp.settings.payment', compact(
            'paymentMethod',
            'isp',
            'stkCallback',
            'c2bValidation',
            'c2bConfirmation',
            'stkTimeout',
            'credentials',
            'paymentExperience'
        ));
    }

    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            abort(403, 'Only ISP admins can manage payment methods');
        }

        $validator = Validator::make($request->all(), [
            'consumer_key' => 'required|string|max:255',
            'consumer_secret' => 'required|string|max:255',
            'shortcode' => 'required|string|max:50',
            'passkey' => 'required|string|max:255',
            'environment' => 'required|in:sandbox,production',
            'transaction_type' => 'required|in:CustomerPayBillOnline,CustomerBuyGoodsOnline',
            'party_b' => 'nullable|string|max:50',
            'callback_url' => 'nullable|url|max:1024',
            'timeout_url' => 'nullable|url|max:1024',
            'b2c_initiator_name' => 'nullable|string|max:255',
            'b2c_security_credential' => 'nullable|string|max:4096',
            'b2c_shortcode' => 'nullable|string|max:50',
            'active' => 'boolean',
            'customer_instructions' => 'nullable|string|max:5000',
            'stk_account_reference' => 'nullable|in:payment_ref,phone,full_name,customer_id',
            'auto_activate_on_payment' => 'nullable|boolean',
            'auto_reconnect_on_payment' => 'nullable|boolean',
            'grace_period_days' => 'nullable|integer|min:0|max:30',
            'auto_receipt_email' => 'nullable|boolean',
            'auto_receipt_sms' => 'nullable|boolean',
            'partial_payment_policy' => 'nullable|in:proportional,balance,reject',
            'manual_payments_enabled' => 'nullable|boolean',
            'offline_cash_enabled' => 'nullable|boolean',
            'offline_bank_enabled' => 'nullable|boolean',
            'credit_apply_next_cycle' => 'nullable|boolean',
            'sms_sender_id' => 'nullable|string|max:11',
            'sms_api_key' => 'nullable|string|max:512',
            'sms_api_secret' => 'nullable|string|max:512',
        ]);

        if ($validator->fails()) {
            return redirect()->route('isp.settings.payment')
                ->withErrors($validator)
                ->withInput();
        }

        if ($request->boolean('active') && ! PlatformIntegration::current()->isPaymentPipeEnabled('daraja')) {
            return redirect()->route('isp.settings.payment')
                ->with('error', 'M-Pesa (Daraja) is disabled at platform level. You can save credentials as inactive or contact your administrator.')
                ->withInput();
        }

        $isp = ISP::query()->findOrFail($ispId);

        $credentials = MpesaGatewayConfig::normalizeCredentials([
            'consumer_key' => $request->consumer_key,
            'consumer_secret' => $request->consumer_secret,
            'shortcode' => $request->shortcode,
            'passkey' => $request->passkey,
            'environment' => $request->environment,
            'transaction_type' => $request->transaction_type,
            'party_b' => $request->party_b,
            'timeout_url' => $request->timeout_url,
            'b2c_initiator_name' => $request->b2c_initiator_name,
            'b2c_security_credential' => $request->filled('b2c_security_credential')
                ? $request->b2c_security_credential
                : null,
            'b2c_shortcode' => $request->b2c_shortcode,
        ]);

        $paymentMethod = IspPaymentMethod::forIsp($ispId)
            ->mpesaProvider()
            ->first();

        $webhookToken = $paymentMethod?->webhook_token ?: Str::random(48);
        $defaultCallback = url('/api/payments/mpesa/callback/'.$webhookToken);

        $callbackUrl = $request->filled('callback_url')
            ? $request->callback_url
            : ($paymentMethod?->callback_url ?: $defaultCallback);

        if ($paymentMethod) {
            if (! $request->filled('b2c_security_credential')) {
                $existing = MpesaGatewayConfig::normalizeCredentials($paymentMethod->credentials ?? []);
                if (! empty($existing['b2c_security_credential'])) {
                    $credentials['b2c_security_credential'] = $existing['b2c_security_credential'];
                }
            }

            $paymentMethod->update([
                'credentials' => $credentials,
                'callback_url' => $callbackUrl,
                'webhook_token' => $paymentMethod->webhook_token ?: $webhookToken,
                'active' => $request->has('active') ? (bool) $request->active : false,
            ]);

            $message = 'Payment configuration updated successfully';
        } else {
            $paymentMethod = IspPaymentMethod::create([
                'isp_id' => $ispId,
                'provider' => MpesaGatewayConfig::STORAGE_PROVIDER,
                'credentials' => $credentials,
                'callback_url' => $callbackUrl,
                'webhook_token' => $webhookToken,
                'active' => $request->has('active') ? (bool) $request->active : false,
            ]);

            $message = 'Payment configuration saved successfully';
        }

        $settings = $isp->settings ?? [];
        $experience = array_merge(IspPaymentExperience::defaults(), $settings['payment_experience'] ?? []);
        $experience['customer_instructions'] = (string) $request->input('customer_instructions', '');
        $experience['stk_account_reference'] = $request->input('stk_account_reference', 'payment_ref');
        $experience['auto_activate_on_payment'] = $request->boolean('auto_activate_on_payment', true);
        $experience['auto_reconnect_on_payment'] = $request->boolean('auto_reconnect_on_payment', true);
        $experience['grace_period_days'] = (int) $request->input('grace_period_days', 0);
        $experience['auto_receipt_email'] = $request->boolean('auto_receipt_email', true);
        $experience['auto_receipt_sms'] = $request->boolean('auto_receipt_sms', false);
        $experience['partial_payment_policy'] = $request->input('partial_payment_policy', 'proportional');
        $experience['manual_payments_enabled'] = $request->boolean('manual_payments_enabled', true);
        $experience['offline_cash_enabled'] = $request->boolean('offline_cash_enabled', true);
        $experience['offline_bank_enabled'] = $request->boolean('offline_bank_enabled', true);
        $experience['credit_apply_next_cycle'] = $request->boolean('credit_apply_next_cycle', true);
        $experience['sms_sender_id'] = (string) $request->input('sms_sender_id', '');

        if ($request->filled('sms_api_key')) {
            $experience['sms_api_key_enc'] = Crypt::encryptString($request->input('sms_api_key'));
        }
        if ($request->filled('sms_api_secret')) {
            $experience['sms_api_secret_enc'] = Crypt::encryptString($request->input('sms_api_secret'));
        }

        $settings['payment_experience'] = $experience;
        $settings['meta'] = array_merge($settings['meta'] ?? [], [
            'last_settings_change_at' => now()->toIso8601String(),
        ]);
        $isp->update(['settings' => $settings]);

        IspSettingsChangeTracker::touch($ispId);

        return redirect()->route('isp.settings.payment')
            ->with('success', $message);
    }

    public function registerC2BUrls(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            abort(403);
        }

        $paymentMethod = IspPaymentMethod::forIsp($ispId)->mpesaProvider()->first();

        if (! $paymentMethod || ! $paymentMethod->webhook_token) {
            return back()->with('error', 'Save Daraja credentials first to generate webhook URLs.');
        }

        $credentials = MpesaGatewayConfig::normalizeCredentials($paymentMethod->credentials ?? []);
        $validationUrl = url('/api/payments/mpesa/c2b/validation/'.$paymentMethod->webhook_token);
        $confirmationUrl = url('/api/payments/mpesa/c2b/confirmation/'.$paymentMethod->webhook_token);

        $result = MpesaService::registerC2BUrls($credentials, $validationUrl, $confirmationUrl);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function toggleActive(IspPaymentMethod $paymentMethod)
    {
        $ispId = auth()->user()->isp_id;

        if ($paymentMethod->isp_id !== $ispId) {
            abort(403, 'Unauthorized access');
        }

        $nextActive = ! $paymentMethod->active;
        if ($nextActive && ! PlatformIntegration::current()->isPaymentPipeEnabled('daraja')) {
            return redirect()->route('isp.settings.payment')
                ->with('error', 'M-Pesa (Daraja) is disabled at platform level. Contact your administrator.');
        }

        $paymentMethod->update([
            'active' => ! $paymentMethod->active,
        ]);

        IspSettingsChangeTracker::touch($ispId);

        $status = $paymentMethod->active ? 'activated' : 'deactivated';

        return redirect()->route('isp.settings.payment')
            ->with('success', "Payment method {$status} successfully");
    }

    public function destroy(IspPaymentMethod $paymentMethod)
    {
        $ispId = auth()->user()->isp_id;

        if ($paymentMethod->isp_id !== $ispId) {
            abort(403, 'Unauthorized access');
        }

        $paymentMethod->delete();

        IspSettingsChangeTracker::touch($ispId);

        return redirect()->route('isp.settings.payment')
            ->with('success', 'Payment method deleted successfully');
    }
}
