<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\HotspotSession;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * Handle customer checkout completion from hotspot after successful payment.
     *
     * For now, this only shows an "Access Granted" page for testing.
     * In production you can wire this into the MikroTik walled-garden
     * redirect so that, once payment is confirmed, the user is sent here.
     */
    public function access(Request $request, Payment $payment)
    {
        // 1. Ensure payment is completed
        if ($payment->status !== 'completed') {
            abort(403, 'Payment not completed.');
        }

        $customer = $payment->customer;

        if (!$customer) {
            abort(404, 'Customer not found for this payment.');
        }

        // 2. Optional MAC & ISP gating for security
        $requestMac = $request->get('mac');
        $router = $customer->router;

        if ($router && $router->isp_id !== $payment->isp_id) {
            abort(403, 'ISP mismatch for this payment.');
        }

        if ($requestMac) {
            // Check that the MAC belongs to this customer (based on latest known session)
            $lastSession = HotspotSession::where('customer_id', $customer->id)
                ->whereNotNull('mac_address')
                ->latest('started_at')
                ->first();

            if (!$lastSession || strcasecmp($lastSession->mac_address, $requestMac) !== 0) {
                Log::warning('MAC mismatch on checkout access', [
                    'customer_id' => $customer->id,
                    'expected_mac' => $lastSession?->mac_address,
                    'provided_mac' => $requestMac,
                ]);
                abort(403, 'Device not recognized for this customer.');
            }
        }

        // 3. Activate hotspot user on MikroTik (synchronously for immediate access)
        if ($router && $customer->connection_type === 'hotspot') {
            try {
                $mikrotik = new MikroTikService($router);
                $mikrotik->activateHotspotUser($customer);
            } catch (\Throwable $e) {
                Log::error('Failed to activate hotspot user during checkout', [
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 4. For testing: show a simple "Access Granted" page
        return view('hotspot.access-granted', [
            'customer' => $customer,
            'payment' => $payment,
        ]);
    }
}






