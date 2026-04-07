<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\IspPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    /**
     * Display payment method settings
     */
    public function index()
    {
        $ispId = auth()->user()->isp_id;
        
        if (!$ispId) {
            abort(403, 'Only ISP admins can access payment methods');
        }

        $paymentMethod = IspPaymentMethod::forIsp($ispId)
            ->byProvider('daraja')
            ->first();

        return view('isp.settings.payment', compact('paymentMethod'));
    }

    /**
     * Store or update payment method
     */
    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        if (!$ispId) {
            abort(403, 'Only ISP admins can manage payment methods');
        }

        $validator = Validator::make($request->all(), [
            'consumer_key' => 'required|string|max:255',
            'consumer_secret' => 'required|string|max:255',
            'shortcode' => 'required|string|max:50',
            'passkey' => 'required|string|max:255',
            'callback_url' => 'nullable|url|max:500',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('isp.settings.payment')
                ->withErrors($validator)
                ->withInput();
        }

        $credentials = [
            'consumer_key' => $request->consumer_key,
            'consumer_secret' => $request->consumer_secret,
            'shortcode' => $request->shortcode,
            'passkey' => $request->passkey,
        ];

        // Default callback URL if not provided
        $callbackUrl = $request->callback_url ?? url('/api/payments/mpesa/callback');

        $paymentMethod = IspPaymentMethod::forIsp($ispId)
            ->byProvider('daraja')
            ->first();

        if ($paymentMethod) {
            // Update existing
            $paymentMethod->update([
                'credentials' => $credentials,
                'callback_url' => $callbackUrl,
                'active' => $request->has('active') ? (bool)$request->active : false,
            ]);

            $message = 'Payment method updated successfully';
        } else {
            // Create new
            $paymentMethod = IspPaymentMethod::create([
                'isp_id' => $ispId,
                'provider' => 'daraja',
                'credentials' => $credentials,
                'callback_url' => $callbackUrl,
                'active' => $request->has('active') ? (bool)$request->active : false,
            ]);

            $message = 'Payment method created successfully';
        }

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

        $paymentMethod->update([
            'active' => !$paymentMethod->active,
        ]);

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

        return redirect()->route('isp.settings.payment')
            ->with('success', 'Payment method deleted successfully');
    }
}

