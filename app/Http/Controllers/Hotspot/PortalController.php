<?php

namespace App\Http\Controllers\Hotspot;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Voucher;
use App\Models\HotspotSession;
use App\Models\PortalSetting;
use App\Support\InvoiceTax;
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
        $router = $request->filled('router_id')
            ? Router::whereKey($request->integer('router_id'))->where('is_active', true)->first()
            : Router::where('ip_address', $routerIp)->where('is_active', true)->first();
        
        // If no router found by IP, try to get any active router (for testing)
        if (!$router && !$request->filled('router_id') && app()->environment(['local', 'testing'])) {
            $router = Router::where('is_active', true)->first();
        }
        
        // Get ISP info if router found
        $isp = $router?->isp;
        
        // Get available plans for this ISP
        $plans = collect();
        if ($isp) {
            $plans = \App\Models\Plan::where('isp_id', $isp->id)
                ->where(function ($query) {
                    $query->where('package_type', 'hotspot')->orWhere('access_type', 'hotspot');
                })
                ->where('is_active', true)
                ->where('customer_visible', true)
                ->whereNull('archived_at')
                ->orderBy('price')
                ->get();
        }
        
        // Get portal settings for this ISP
        $portalSettings = null;
        if ($isp) {
            $portalSettings = PortalSetting::getForISP($isp->id);
        }
        
        return view('hotspot.splash', compact('isp', 'router', 'plans', 'portalSettings'));
    }

    /**
     * Show splash page (initial page when connecting to WiFi)
     */
    public function splash(Request $request)
    {
        // Get router IP from request (MikroTik sends this)
        $routerIp = $request->get('router_ip') ?? $request->ip();
        
        // Try to find router by IP
        $router = $request->filled('router_id')
            ? Router::whereKey($request->integer('router_id'))->where('is_active', true)->first()
            : Router::where('ip_address', $routerIp)->where('is_active', true)->first();
        
        // If no router found by IP, try to get any active router (for testing)
        if (!$router && !$request->filled('router_id') && app()->environment(['local', 'testing'])) {
            $router = Router::where('is_active', true)->first();
        }
        
        // Get ISP info if router found
        $isp = $router?->isp;
        
        // Get available plans for this ISP
        $plans = collect();
        if ($isp) {
            $plans = \App\Models\Plan::where('isp_id', $isp->id)
                ->where(function ($query) {
                    $query->where('package_type', 'hotspot')->orWhere('access_type', 'hotspot');
                })
                ->where('is_active', true)
                ->where('customer_visible', true)
                ->whereNull('archived_at')
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
        $router = in_array($routerIp, ['127.0.0.1', '::1', 'localhost'], true)
            ? Router::where('is_active', true)->first()
            : Router::where('ip_address', $routerIp)->first();
        
        if (!$router) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Router not found.'], 404)
                : back()->withErrors(['error' => 'Router not found.']);
        }

        $voucher = Voucher::where('router_id', $router->id)
            ->where('voucher_code', $voucherCode)
            ->first();

        if (!$voucher) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Invalid voucher code.'], 422)
                : back()->withErrors(['error' => 'Invalid voucher code.']);
        }

        if (!$voucher->isValid()) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Voucher has expired or is not valid.'], 422)
                : back()->withErrors(['error' => 'Voucher has expired or is not valid.']);
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
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Failed to activate voucher.'], 422)
                : back()->withErrors(['error' => 'Failed to activate voucher.']);
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

        $statusUrl = route('hotspot.status', [
            'username' => $voucher->voucher_code,
            'token' => $this->generateToken($customerId, $voucher->id),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Voucher redeemed successfully.',
                'redirect_url' => $statusUrl,
            ]);
        }

        return redirect()->to($statusUrl)->with('success', 'Voucher redeemed successfully! Internet access activated.');
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

        if (! $plan->isHotspot() || ! $plan->is_active || ! $plan->customer_visible || $plan->archived_at) {
            abort(404);
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
            'router_ip' => 'nullable|ip',
            'router_id' => 'nullable|integer',
            'ip_address' => 'nullable|ip',
            'mac' => ['nullable', 'regex:/^(?:[0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
            'link_login_only' => 'nullable|url|max:2048',
            'link_orig' => 'nullable|url|max:2048',
        ]);
        
        $routerIp = $request->router_ip ?? $request->ip();
        
        // If localhost/127.0.0.1, try to find any active router (for local testing)
        if ($request->filled('router_id')) {
            $router = Router::whereKey($request->integer('router_id'))->where('is_active', true)->first();
        } elseif (in_array($routerIp, ['127.0.0.1', '::1', 'localhost'])) {
            $router = Router::where('is_active', true)->first();
        } else {
            $router = Router::where('ip_address', $routerIp)->where('is_active', true)->first();
        }
        
        if (!$router) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Router not found.'], 404)
                : back()->withErrors(['error' => 'Router not found.']);
        }
        
        // Find the plan by ID
        $plan = \App\Models\Plan::find($planId);
        
        if (!$plan) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Package not found.'], 404)
                : back()->withErrors(['error' => 'Package not found.']);
        }

        if (! $plan->isHotspot() || ! $plan->is_active || ! $plan->customer_visible || $plan->archived_at) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Package is not available.'], 404)
                : back()->withErrors(['error' => 'Package is not available.']);
        }
        
        if ($plan->isp_id !== $router->isp_id) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Invalid package.'], 422)
                : back()->withErrors(['error' => 'Invalid package.']);
        }

        $router->load('isp');

        // RouterOS supplies this URL to the external captive portal. It must
        // point to the router configured for this purchase, preventing a
        // crafted request from making the buyer's browser post credentials to
        // an unrelated internal host.
        $routerLoginUrl = $request->input('link_login_only');
        if ($routerLoginUrl) {
            $parts = parse_url($routerLoginUrl) ?: [];
            $host = $parts['host'] ?? null;
            $allowedGateway = $router->hotspot_gateway_ip ?: $router->ip_address;
            $validRouterLogin = is_string($host)
                && hash_equals(strtolower((string) $allowedGateway), strtolower($host))
                && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
                && ($parts['path'] ?? '') === '/login'
                && ! isset($parts['user'])
                && ! isset($parts['pass'])
                && (! isset($parts['port']) || in_array((int) $parts['port'], [80, 443], true));
            if (! $validRouterLogin) {
                return response()->json(['success' => false, 'message' => 'Invalid captive portal login address.'], 422);
            }
        }

        // Create temporary customer record
        $customer = Customer::create([
            'isp_id' => $router->isp_id,
            'router_id' => $router->id,
            // Hotspot checkout only requires a phone number; use a generic
            // label for the customer record instead of collecting a name.
            'full_name' => 'Hotspot Customer',
            'email' => null,
            'phone_number' => $request->phone_number,
            'location' => 'Hotspot Portal Registration',
            'installation_date' => now()->toDateString(),
            'username' => 'HB' . strtoupper(bin2hex(random_bytes(5))),
            'password' => Hash::make(uniqid()),
            'hotspot_password' => \Illuminate\Support\Str::random(24),
            'hotspot_mac_address' => $request->input('mac'),
            'hotspot_login_url' => $routerLoginUrl,
            'connection_type' => 'hotspot',
            'status' => 'inactive',
        ]);

        if ($request->filled('link_orig')) {
            $attributes = $customer->custom_attributes ?? [];
            $attributes['hotspot_original_url'] = $request->input('link_orig');
            $attributes['hotspot_client_ip'] = $request->input('ip_address');
            $customer->update(['custom_attributes' => $attributes]);
        } elseif ($request->filled('ip_address')) {
            $attributes = $customer->custom_attributes ?? [];
            $attributes['hotspot_client_ip'] = $request->input('ip_address');
            $customer->update(['custom_attributes' => $attributes]);
        }

        // Subscription + invoice (payment row is created when user pays via customer.payments.initiate)
        $subscription = Subscription::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
            ],
            [
                'start_date' => now(),
                // The package clock starts only after payment is confirmed.
                // PaymentCompletionService::renew() sets its real expiry then.
                'end_date' => today(),
                'ends_at' => now(),
                'renewal_date' => now(),
                'status' => 'suspended',
                'auto_renew' => false,
            ]
        );

        $invoice = Invoice::where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->first();

        if (! $invoice) {
            $ispPrefix = strtoupper(substr($router->isp->name ?? 'ISP', 0, 3));
            $invoiceCount = Invoice::where('isp_id', $router->isp_id)->count() + 1;
            $invoiceNumber = $ispPrefix . '-' . now()->format('Y') . '-' . str_pad($invoiceCount, 6, '0', STR_PAD_LEFT);

            $amount = (float) $plan->price;
            $lines = InvoiceTax::computeForAmount($amount, $router->isp);

            $invoice = Invoice::create([
                'isp_id' => $router->isp_id,
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $lines['amount'],
                'tax' => $lines['tax'],
                'total_amount' => $lines['total_amount'],
                'issue_date' => now(),
                'due_date' => now()->addDays(7),
                'status' => 'pending',
                'notes' => "Purchase of {$plan->name} package (plan_id: {$plan->id})",
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'invoice_id' => $invoice->id,
                'message' => 'Purchase is ready for payment.',
            ]);
        }

        return redirect()->route('customer.checkout', [
            'customer' => $customer->id,
            'invoice' => $invoice->id,
            'plan' => $plan->id,
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

