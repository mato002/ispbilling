<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\IspPaymentMethod;
use App\Models\ISP;
use App\Models\PlatformIntegration;
use App\Support\IspPaymentExperience;
use App\Support\IspSettingsChangeTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    /**
     * Display payment method settings
     */
    public function index()
    {
        $ispId = auth()->user()->isp_id;

        if (! $ispId) {
            abort(403, 'Only ISP admins can access payment methods');
        }

        $isp = ISP::query()->findOrFail($ispId);

        $paymentMethod = IspPaymentMethod::forIsp($ispId)
            ->byProvider('daraja')
            ->first();

        $recommendedCallback = ($paymentMethod && $paymentMethod->webhook_token)
            ? url('/api/payments/mpesa/callback/'.$paymentMethod->webhook_token)
            : null;

        $paymentExperience = array_merge(
            IspPaymentExperience::defaults(),
            $isp->settings['payment_experience'] ?? []
        );

        return view('isp.settings.payment', compact(
            'paymentMethod',
            'isp',
            'recommendedCallback',
            'paymentExperience'
        ));
    }

    /**
     * Store or update payment method
     */
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
            'callback_url' => 'nullable|url|max:1024',
            'active' => 'boolean',
            'customer_instructions' => 'nullable|string|max:5000',
            'stk_account_reference' => 'nullable|in:payment_ref,phone,full_name,customer_id',
            'auto_activate_on_payment' => 'nullable|boolean',
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

        $credentials = [
            'consumer_key' => $request->consumer_key,
            'consumer_secret' => $request->consumer_secret,
            'shortcode' => $request->shortcode,
            'passkey' => $request->passkey,
        ];

        $paymentMethod = IspPaymentMethod::forIsp($ispId)
            ->byProvider('daraja')
            ->first();

        $webhookToken = $paymentMethod?->webhook_token ?: Str::random(48);
        $defaultCallback = url('/api/payments/mpesa/callback/'.$webhookToken);

        $callbackUrl = $request->filled('callback_url')
            ? $request->callback_url
            : ($paymentMethod?->callback_url ?: $defaultCallback);

        if ($paymentMethod) {
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
                'provider' => 'daraja',
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

    /**
     * Toggle active status
     */
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

    /**
     * Delete payment method
     */
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
