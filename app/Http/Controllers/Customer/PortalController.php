<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ISP;
use App\Models\HotspotSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PortalController extends Controller
{
    /**
     * Captive portal entry point for hotspot users
     * 
     * Route: /customer/portal
     * Parameters: mac_address, ip, isp_id
     * 
     * - Validates ISP exists and is active
     * - Finds customer by MAC address or IP
     * - Checks if customer has active subscription
     * - If active: redirect to "Access Granted"
     * - If expired: redirect to payment checkout
     * - Only allows hotspot users
     */
    public function portal(Request $request)
    {
        // Validate required parameters
        $request->validate([
            'mac_address' => 'required|string',
            'ip' => 'required|ip',
            'isp_id' => 'required|exists:i_s_p_s,id',
        ]);

        $macAddress = strtolower(trim($request->mac_address));
        $ipAddress = $request->ip;
        $ispId = $request->isp_id;

        // Validate ISP exists and is active
        $isp = ISP::find($ispId);
        if (!$isp || !$isp->is_active) {
            Log::warning('Captive portal: Invalid or inactive ISP', [
                'isp_id' => $ispId,
                'mac' => $macAddress,
                'ip' => $ipAddress,
            ]);
            abort(403, 'Invalid ISP or ISP is inactive.');
        }

        // Find customer by MAC address (from hotspot sessions)
        $customer = null;
        
        // First, try to find by MAC address in recent sessions
        $session = HotspotSession::where('isp_id', $ispId)
            ->where('mac_address', $macAddress)
            ->whereNotNull('customer_id')
            ->latest('started_at')
            ->first();

        if ($session && $session->customer_id) {
            $customer = Customer::find($session->customer_id);
        }

        // If not found by MAC, try to find by IP address
        if (!$customer) {
            $sessionByIp = HotspotSession::where('isp_id', $ispId)
                ->where('ip_address', $ipAddress)
                ->whereNotNull('customer_id')
                ->latest('started_at')
                ->first();

            if ($sessionByIp && $sessionByIp->customer_id) {
                $customer = Customer::find($sessionByIp->customer_id);
            }
        }

        // If still not found, try direct customer lookup by phone or username
        // (in case MAC/IP lookup fails but we have customer identifier)
        if (!$customer && $request->has('username')) {
            $customer = Customer::where('isp_id', $ispId)
                ->where('username', $request->username)
                ->where('connection_type', 'hotspot')
                ->first();
        }

        // Ensure customer exists and is a hotspot user
        if (!$customer) {
            Log::info('Captive portal: Customer not found', [
                'isp_id' => $ispId,
                'mac' => $macAddress,
                'ip' => $ipAddress,
            ]);
            
            // Redirect to hotspot login page if customer not found
            return redirect()->route('hotspot.login', [
                'isp_id' => $ispId,
                'mac' => $macAddress,
                'ip' => $ipAddress,
            ])->with('error', 'Customer not found. Please contact support.');
        }

        // Ensure only hotspot users are processed
        if ($customer->connection_type !== 'hotspot') {
            Log::warning('Captive portal: Non-hotspot user attempted access', [
                'customer_id' => $customer->id,
                'connection_type' => $customer->connection_type,
            ]);
            abort(403, 'This portal is only for hotspot users.');
        }

        // Ensure customer belongs to the specified ISP
        if ($customer->isp_id !== $ispId) {
            Log::warning('Captive portal: ISP mismatch', [
                'customer_id' => $customer->id,
                'customer_isp_id' => $customer->isp_id,
                'requested_isp_id' => $ispId,
            ]);
            abort(403, 'Customer does not belong to this ISP.');
        }

        // Check if customer has active subscription
        $isActive = $customer->isActive();
        $activeSubscription = $customer->activeSubscription();

        if ($isActive && $activeSubscription) {
            // Customer has active subscription - grant access
            Log::info('Captive portal: Access granted', [
                'customer_id' => $customer->id,
                'subscription_id' => $activeSubscription->id,
                'mac' => $macAddress,
                'ip' => $ipAddress,
            ]);

            // Return "Access Granted" page (for now)
            return view('hotspot.access-granted', [
                'customer' => $customer,
                'subscription' => $activeSubscription,
            ]);
        } else {
            // Customer subscription expired or inactive - redirect to checkout
            Log::info('Captive portal: Subscription expired, redirecting to checkout', [
                'customer_id' => $customer->id,
                'status' => $customer->status,
                'has_subscription' => $activeSubscription !== null,
                'mac' => $macAddress,
                'ip' => $ipAddress,
            ]);

            // Redirect to checkout page
            return redirect()->route('customer.checkout', [
                'customer' => $customer->id,
                'mac' => $macAddress,
                'ip' => $ipAddress,
                'isp_id' => $ispId,
            ])->with('message', 'Your subscription has expired. Please renew to continue.');
        }
    }
}

