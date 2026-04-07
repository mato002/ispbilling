<?php

namespace App\Http\Controllers\Hotspot;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Voucher;
use App\Models\Router;
use App\Models\HotspotSession;
use App\Models\PortalSetting;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PortalController extends Controller
{
    /**
     * Show available packages page
     */
    public function packages(Request $request)
    {
        // Get router IP from request
        $routerIp = $request->get('router_ip') ?? $request->ip();
        
        // Try to find router by IP
        $router = Router::where('ip_address', $routerIp)->first();
        
        // If no router found by IP, try to get any active router (for testing)
        if (!$router) {
            $router = Router::where('is_active', true)->first();
        }
        
        // Get ISP info if router found
        $isp = $router?->isp;
        
        // Get available plans for this ISP
        $plans = collect();
        if ($isp) {
            $plans = \App\Models\Plan::where('isp_id', $isp->id)
                ->where('is_active', true)
                ->orderBy('price')
                ->get();
        }
        
        // Get portal settings for this ISP
        $portalSettings = null;
        if ($isp) {
            $portalSettings = PortalSetting::getForISP($isp->id);
        }
        
        return view('hotspot.packages', compact('isp', 'router', 'plans', 'portalSettings'));
    }

    /**
     * Show splash page (initial page when connecting to WiFi)
     */
    public function splash(Request $request)
    {
        // Get router IP from request (MikroTik sends this)
        $routerIp = $request->get('router_ip') ?? $request->ip();
        
        // Try to find router by IP
        $router = Router::where('ip_address', $routerIp)->first();
        
        // If no router found by IP, try to get any active router (for testing)
        if (!$router) {
            $router = Router::where('is_active', true)->first();
        }
        
        // Get ISP info if router found
        $isp = $router?->isp;
        
        // Get available plans for this ISP
        $plans = collect();
        if ($isp) {
            $plans = \App\Models\Plan::where('isp_id', $isp->id)
                ->where('is_active', true)
                ->orderBy('price')
                ->get();
        }
        
        // Get portal settings for this ISP
        $portalSettings = null;
        if ($isp) {
            $portalSettings = PortalSetting::getForISP($isp->id);
        }
        
        // Debug: Add some info to help identify issues
        if (!$router) {
            \Log::error('No router found for IP: ' . $routerIp);
        } else {
            \Log::info('Router found: ' . $router->name . ' (IP: ' . $router->ip_address . ')');
        }
        
        if (!$isp) {
            \Log::error('No ISP found for router: ' . $router->name);
        } else {
            \Log::info('ISP found: ' . $isp->name . ' (ID: ' . $isp->id . ')');
            \Log::info('Plans count: ' . $plans->count());
        }
        
        return view('hotspot.splash', compact('isp', 'router', 'plans', 'portalSettings'));
    }

    /**
     * Show login page
     */
    public function login(Request $request)
    {
        // Try to get router IP from request parameter first, then from IP header, then fallback to request IP
        $routerIp = $request->get('router_ip') 
            ?? $request->header('X-Router-IP') 
            ?? $request->ip();
        
        // If still localhost/127.0.0.1, try to find any router for the ISP (for local testing)
        if (in_array($routerIp, ['127.0.0.1', '::1', 'localhost'])) {
            // For local testing, get the first active router
            $router = Router::where('is_active', true)->first();
        } else {
            $router = Router::where('ip_address', $routerIp)->first();
        }
        
        $isp = $router?->isp;
        
        // Get portal settings for this ISP
        $portalSettings = null;
        if ($isp) {
            $portalSettings = PortalSetting::getForISP($isp->id);
        }
        
        return view('hotspot.login', compact('isp', 'router', 'portalSettings'));
    }

    /**
     * Handle login
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'router_ip' => 'nullable|ip',
        ]);

        $username = $request->username;
        $password = $request->password;
        $routerIp = $request->router_ip 
            ?? $request->header('X-Router-IP') 
            ?? $request->ip();

        // Find router - if localhost, try to find any active router (for local testing)
        if (in_array($routerIp, ['127.0.0.1', '::1', 'localhost'])) {
            $router = Router::where('is_active', true)->first();
        } else {
            $router = Router::where('ip_address', $routerIp)->first();
        }
        
        if (!$router) {
            return back()->withErrors(['error' => 'Router not found. Please contact support.']);
        }

        // Try to find customer - first by router_id, then by ISP if router not found
        $customer = Customer::where('router_id', $router->id)
            ->where('username', $username)
            ->where('connection_type', 'hotspot')
            ->first();

        // If not found by router, try by ISP (for testing or if router assignment is flexible)
        if (!$customer && $router->isp_id) {
            $customer = Customer::where('isp_id', $router->isp_id)
                ->where('username', $username)
                ->where('connection_type', 'hotspot')
                ->first();
        }

        if (!$customer) {
            // Try voucher
            $voucher = Voucher::where('router_id', $router->id)
                ->where('voucher_code', $username)
                ->where('status', 'active')
                ->first();

            if ($voucher && $voucher->voucher_code === $password) {
                // Voucher login - authenticate via MikroTik
                return $this->authenticateVoucher($voucher, $router, $request);
            }

            return back()->withErrors(['error' => 'Invalid username or password.']);
        }

        // Verify password
        if (!Hash::check($password, $customer->password)) {
            return back()->withErrors(['error' => 'Invalid username or password.']);
        }

        // For hotspot: Check if subscription is active (not customer status)
        // Hotspot is pay-as-you-go - users can login if they have active subscription
        $subscription = $customer->activeSubscription();
        if (!$subscription) {
            // No active subscription - redirect to checkout to purchase/renew
            return redirect()->route('customer.checkout', ['customer' => $customer->id])
                ->with('info', 'Your subscription has expired. Please select a plan to continue.');
        }

        // If subscription is active, automatically activate customer if needed
        // This ensures payment automatically grants access without manual activation
        if ($customer->status !== 'active' && $subscription) {
            // Auto-activate customer when they have active subscription
            $customer->activate();
            
            // Also enable on MikroTik if router is available
            try {
                $mikrotik = new MikroTikService($router);
                if ($mikrotik->connect()) {
                    if ($customer->connection_type === 'hotspot') {
                        $mikrotik->enableHotspotUser($customer);
                    } else {
                        $mikrotik->enableUser($customer);
                    }
                }
            } catch (\Exception $e) {
                // Log but don't block login if router connection fails
                Log::warning("Failed to enable customer on router during login: " . $e->getMessage());
            }
        }

        // Authenticate with MikroTik (optional for local testing)
        $mikrotik = new MikroTikService($router);
        $mikrotikConnected = $mikrotik->connect();
        
        // In local/testing, allow login even if router connection fails
        if (!$mikrotikConnected && !app()->environment(['local', 'testing'])) {
            return back()->withErrors(['error' => 'Unable to connect to router. Please try again.']);
        }

        // Get active session info from MikroTik (only if connected)
        try {
            $connection = $mikrotik->getConnection();
            
            // Only try to get session info if connection is available
            if ($connection) {
                // Check if connection has comm method (old API) or query method (new API)
                if (method_exists($connection, 'comm')) {
                    $activeSessions = $connection->comm('/ip/hotspot/active/print', [
                        '?user' => $username,
                    ]);
                } elseif (method_exists($connection, 'query')) {
                    $query = new \RouterOS\Query('/ip/hotspot/active/print');
                    $query->where('user', $username);
                    $activeSessions = $connection->query($query)->read();
                } else {
                    $activeSessions = [];
                }

                // Create or update session record
                if (!empty($activeSessions)) {
                    $session = $activeSessions[0];
                    HotspotSession::updateOrCreate(
                        [
                            'username' => $username,
                            'router_id' => $router->id,
                            'status' => 'active',
                        ],
                        [
                            'isp_id' => $router->isp_id,
                            'customer_id' => $customer->id,
                            'ip_address' => $session['address'] ?? $request->ip(),
                            'mac_address' => $session['mac-address'] ?? null,
                            'mikrotik_session_id' => $session['.id'] ?? null,
                            'bytes_uploaded' => $session['bytes-up'] ?? 0,
                            'bytes_downloaded' => $session['bytes-down'] ?? 0,
                            'uptime_seconds' => $this->parseUptime($session['uptime'] ?? '0s'),
                            'started_at' => now(),
                        ]
                    );
                }
            } else {
                // No router connection - create session record anyway for local testing
                if (app()->environment(['local', 'testing'])) {
                    HotspotSession::updateOrCreate(
                        [
                            'username' => $username,
                            'router_id' => $router->id,
                            'status' => 'active',
                        ],
                        [
                            'isp_id' => $router->isp_id,
                            'customer_id' => $customer->id,
                            'ip_address' => $request->ip(),
                            'mac_address' => $request->get('mac'),
                            'bytes_uploaded' => 0,
                            'bytes_downloaded' => 0,
                            'uptime_seconds' => 0,
                            'started_at' => now(),
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to get session info: ' . $e->getMessage());
            
            // In local/testing, create session record anyway
            if (app()->environment(['local', 'testing'])) {
                HotspotSession::updateOrCreate(
                    [
                        'username' => $username,
                        'router_id' => $router->id,
                        'status' => 'active',
                    ],
                    [
                        'isp_id' => $router->isp_id,
                        'customer_id' => $customer->id,
                        'ip_address' => $request->ip(),
                        'mac_address' => $request->get('mac'),
                        'bytes_uploaded' => 0,
                        'bytes_downloaded' => 0,
                        'uptime_seconds' => 0,
                        'started_at' => now(),
                    ]
                );
            }
        }

        // Redirect to status page
        return redirect()->route('hotspot.status', [
            'username' => $username,
            'token' => $this->generateToken($customer->id),
        ]);
    }

    /**
     * Authenticate voucher
     */
    protected function authenticateVoucher(Voucher $voucher, Router $router, Request $request)
    {
        // Get customer ID if voucher is assigned or MAC provided
        $customerId = $voucher->assigned_to;
        $macAddress = $request->get('mac');
        
        if (!$customerId && $macAddress) {
            $customer = \App\Models\Customer::where('isp_id', $router->isp_id)
                ->whereHas('hotspotSessions', function ($q) use ($macAddress) {
                    $q->where('mac_address', $macAddress);
                })
                ->latest()
                ->first();
            $customerId = $customer?->id;
        }

        // Activate voucher (creates payment record and enables on MikroTik)
        $voucherService = new \App\Services\VoucherService();
        $voucherService->activate($voucher, $customerId);

        // Create session record
        HotspotSession::create([
            'isp_id' => $router->isp_id,
            'router_id' => $router->id,
            'customer_id' => $customerId,
            'username' => $voucher->voucher_code,
            'ip_address' => $request->ip(),
            'mac_address' => $macAddress,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Redirect to status page
        return redirect()->route('hotspot.status', [
            'username' => $voucher->voucher_code,
            'token' => $this->generateToken($customerId, $voucher->id),
        ]);
    }

    /**
     * Show user status page
     */
    public function status(Request $request)
    {
        $username = $request->get('username');
        $token = $request->get('token');

        if (!$username || !$token) {
            return redirect()->route('hotspot.login')->withErrors(['error' => 'Invalid access.']);
        }

        // Find customer or voucher
        $customer = Customer::where('username', $username)->first();
        $voucher = null;
        
        if (!$customer) {
            $voucher = Voucher::where('voucher_code', $username)->first();
            if (!$voucher) {
                return redirect()->route('hotspot.login')->withErrors(['error' => 'Session not found.']);
            }
        }

        // Get active session
        $session = HotspotSession::where('username', $username)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$session) {
            return redirect()->route('hotspot.login')->withErrors(['error' => 'No active session found.']);
        }

        $router = $session->router;
        $isp = $router->isp;

        // Get subscription if customer
        $subscription = $customer?->activeSubscription();

        // Get usage stats
        $usageStats = null;
        if ($customer) {
            $usageStats = \App\Models\CustomerUsageStat::where('customer_id', $customer->id)
                ->orderBy('date', 'desc')
                ->take(30)
                ->get();
        }

        return view('hotspot.status', compact('customer', 'voucher', 'session', 'router', 'isp', 'subscription', 'usageStats', 'token'));
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        $username = $request->get('username');
        $token = $request->get('token');

        if (!$username) {
            return redirect()->route('hotspot.login');
        }

        // Find session
        $session = HotspotSession::where('username', $username)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($session && $session->router) {
            // Disconnect from MikroTik
            try {
                $mikrotik = new MikroTikService($session->router);
                if ($mikrotik->connect()) {
                    $mikrotik->disconnectSession($session->customer ?? new Customer(['username' => $username, 'connection_type' => 'hotspot']));
                }
            } catch (\Exception $e) {
                Log::error('Failed to disconnect session: ' . $e->getMessage());
            }

            // End session
            $session->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);
        }

        return redirect()->route('hotspot.login')->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show voucher redemption page
     */
    public function redeemVoucher(Request $request)
    {
        $routerIp = $request->ip() ?? $request->get('router_ip');
        $router = Router::where('ip_address', $routerIp)->first();
        $isp = $router?->isp;

        return view('hotspot.redeem-voucher', compact('isp', 'router'));
    }

    /**
     * Process voucher redemption
     */
    public function processVoucher(Request $request)
    {
        $request->validate([
            'voucher_code' => 'required|string|size:12',
            'router_ip' => 'nullable|ip',
        ]);

        $voucherCode = strtoupper($request->voucher_code);
        $routerIp = $request->router_ip ?? $request->ip();

        $router = Router::where('ip_address', $routerIp)->first();
        
        if (!$router) {
            return back()->withErrors(['error' => 'Router not found.']);
        }

        $voucher = Voucher::where('router_id', $router->id)
            ->where('voucher_code', $voucherCode)
            ->first();

        if (!$voucher) {
            return back()->withErrors(['error' => 'Invalid voucher code.']);
        }

        if (!$voucher->isValid()) {
            return back()->withErrors(['error' => 'Voucher has expired or is not valid.']);
        }

        // Check if voucher is assigned to a specific customer
        if ($voucher->assigned_to) {
            // Voucher is pre-assigned, use that customer
            $customerId = $voucher->assigned_to;
        } else {
            // Try to find customer by MAC address if provided
            $macAddress = $request->get('mac');
            $customerId = null;
            if ($macAddress) {
                $customer = \App\Models\Customer::where('isp_id', $router->isp_id)
                    ->whereHas('hotspotSessions', function ($q) use ($macAddress) {
                        $q->where('mac_address', $macAddress);
                    })
                    ->latest()
                    ->first();
                $customerId = $customer?->id;
            }
        }

        // Activate voucher (creates payment record and enables on MikroTik)
        $voucherService = new \App\Services\VoucherService();
        if (!$voucherService->activate($voucher, $customerId)) {
            return back()->withErrors(['error' => 'Failed to activate voucher.']);
        }

        // Create session record
        HotspotSession::create([
            'isp_id' => $router->isp_id,
            'router_id' => $router->id,
            'customer_id' => $customerId,
            'username' => $voucher->voucher_code,
            'ip_address' => $request->ip(),
            'mac_address' => $request->get('mac'),
            'status' => 'active',
            'started_at' => now(),
        ]);

        return redirect()->route('hotspot.status', [
            'username' => $voucher->voucher_code,
            'token' => $this->generateToken($customerId, $voucher->id),
        ])->with('success', 'Voucher redeemed successfully! Internet access activated.');
    }

    /**
     * Show package purchase page
     */
    public function buyPackage(Request $request, $planId)
    {
        $routerIp = $request->get('router_ip') ?? $request->ip();
        
        // If localhost/127.0.0.1, try to find any active router (for local testing)
        if (in_array($routerIp, ['127.0.0.1', '::1', 'localhost'])) {
            $router = Router::where('is_active', true)->first();
        } else {
            $router = Router::where('ip_address', $routerIp)->first();
        }
        
        $isp = $router?->isp;
        
        // Find the plan by ID
        $plan = \App\Models\Plan::find($planId);
        
        if (!$plan) {
            return back()->withErrors(['error' => 'Package not found.']);
        }
        
        if (!$isp || $plan->isp_id !== $isp->id) {
            return back()->withErrors(['error' => 'Invalid package.']);
        }
        
        return view('hotspot.buy-package', compact('plan', 'isp', 'router'));
    }
    
    /**
     * Process package purchase
     */
    public function processPurchase(Request $request, $planId)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'email' => 'nullable|email',
            'full_name' => 'required|string',
            'router_ip' => 'nullable|ip',
        ]);
        
        $routerIp = $request->router_ip ?? $request->ip();
        
        // If localhost/127.0.0.1, try to find any active router (for local testing)
        if (in_array($routerIp, ['127.0.0.1', '::1', 'localhost'])) {
            $router = Router::where('is_active', true)->first();
        } else {
            $router = Router::where('ip_address', $routerIp)->first();
        }
        
        if (!$router) {
            return back()->withErrors(['error' => 'Router not found.']);
        }
        
        // Find the plan by ID
        $plan = \App\Models\Plan::find($planId);
        
        if (!$plan) {
            return back()->withErrors(['error' => 'Package not found.']);
        }
        
        if ($plan->isp_id !== $router->isp_id) {
            return back()->withErrors(['error' => 'Invalid package.']);
        }
        
        // Create temporary customer record
        $customer = Customer::create([
            'isp_id' => $router->isp_id,
            'router_id' => $router->id,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'location' => 'Hotspot Portal Registration',
            'installation_date' => now()->toDateString(),
            'username' => 'temp_' . uniqid(),
            'password' => Hash::make(uniqid()),
            'connection_type' => 'hotspot',
            'status' => 'inactive',
        ]);
        
        // Create payment record
        $payment = \App\Models\Payment::create([
            'customer_id' => $customer->id,
            'isp_id' => $router->isp_id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'method' => 'mpesa',
            'status' => 'pending',
            'phone_number' => $request->phone_number,
            'description' => "Purchase of {$plan->name} package",
        ]);
        
        // Redirect to checkout page for payment processing
        return redirect()->route('checkout', [
            'customer' => $customer->id,
            'payment' => $payment->id,
        ]);
    }

    /**
     * Generate simple token for session access
     */
    protected function generateToken(?int $customerId = null, ?int $voucherId = null): string
    {
        return base64_encode(json_encode([
            'customer_id' => $customerId,
            'voucher_id' => $voucherId,
            'timestamp' => now()->timestamp,
        ]));
    }

    /**
     * Parse MikroTik uptime string to seconds
     */
    protected function parseUptime(string $uptime): int
    {
        // Format: "1d2h3m4s" or "2h3m4s" or "3m4s" or "4s"
        $seconds = 0;
        
        if (preg_match('/(\d+)d/', $uptime, $matches)) {
            $seconds += (int)$matches[1] * 86400;
        }
        if (preg_match('/(\d+)h/', $uptime, $matches)) {
            $seconds += (int)$matches[1] * 3600;
        }
        if (preg_match('/(\d+)m/', $uptime, $matches)) {
            $seconds += (int)$matches[1] * 60;
        }
        if (preg_match('/(\d+)s/', $uptime, $matches)) {
            $seconds += (int)$matches[1];
        }
        
        return $seconds;
    }
}

