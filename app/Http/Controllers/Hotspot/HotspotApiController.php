<?php

namespace App\Http\Controllers\Hotspot;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Voucher;
use App\Models\Router;
use App\Models\HotspotSession;
use App\Models\CustomerDevice;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * API Controller for MikroTik Hotspot Portal Integration
 * These endpoints are called by MikroTik router for authentication
 */
class HotspotApiController extends Controller
{
    /**
     * Authenticate user login (called by MikroTik)
     * POST /api/hotspot/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'router_ip' => 'required|ip',
            'ip' => 'required|ip',
            'mac' => 'nullable|string',
        ]);

        $username = $request->username;
        $password = $request->password;
        $routerIp = $request->router_ip;
        $clientIp = $request->ip;
        $macAddress = $request->mac;

        // Find router
        $router = Router::where('ip_address', $routerIp)->first();
        
        if (!$router) {
            return response()->json([
                'status' => 'reject',
                'message' => 'Router not found',
            ], 404);
        }

        // Try customer first
        $customer = Customer::where('router_id', $router->id)
            ->where('username', $username)
            ->where('connection_type', 'hotspot')
            ->first();

        if ($customer) {
            // Verify password
            if (!Hash::check($password, $customer->password)) {
                return response()->json([
                    'status' => 'reject',
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Check if active
            if ($customer->status !== 'active') {
                return response()->json([
                    'status' => 'reject',
                    'message' => 'Account not active',
                ], 403);
            }

            // Check subscription
            if (!$customer->activeSubscription()) {
                return response()->json([
                    'status' => 'reject',
                    'message' => 'Subscription expired',
                ], 403);
            }

            // Check device limit if MAC address is provided
            if ($macAddress) {
                $deviceCheck = $this->checkDeviceLimit($customer, $macAddress);
                if (!$deviceCheck['allowed']) {
                    return response()->json([
                        'status' => 'reject',
                        'message' => $deviceCheck['message'],
                    ], 403);
                }
            }

            // Create session record
            $this->createSession($router, $customer, $clientIp, $macAddress, $username);

            return response()->json([
                'status' => 'accept',
                'message' => 'Authentication successful',
            ]);
        }

        // Try voucher
        $voucher = Voucher::where('router_id', $router->id)
            ->where('voucher_code', $username)
            ->where('status', 'active')
            ->first();

        if ($voucher && $voucher->voucher_code === $password && $voucher->isValid()) {
            // Activate voucher (creates payment record and enables on MikroTik)
            $voucherService = new \App\Services\VoucherService();
            $voucherService->activate($voucher);

            // Create session record
            $this->createSession($router, $voucher->assigned_to ? \App\Models\Customer::find($voucher->assigned_to) : null, $clientIp, $macAddress, $username, $voucher);

            return response()->json([
                'status' => 'accept',
                'message' => 'Voucher authenticated',
            ]);
        }

        return response()->json([
            'status' => 'reject',
            'message' => 'Invalid credentials',
        ], 401);
    }

    /**
     * Logout user (called by MikroTik)
     * POST /api/hotspot/logout
     */
    public function logout(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'router_ip' => 'required|ip',
        ]);

        $username = $request->username;
        $routerIp = $request->router_ip;

        $router = Router::where('ip_address', $routerIp)->first();
        
        if (!$router) {
            return response()->json(['status' => 'error', 'message' => 'Router not found'], 404);
        }

        // End session
        $session = HotspotSession::where('router_id', $router->id)
            ->where('username', $username)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($session) {
            $session->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

            // Update usage stats
            $this->updateUsageStats($session);
        }

        return response()->json(['status' => 'ok', 'message' => 'Logged out']);
    }

    /**
     * Get user status (called by MikroTik or portal)
     * GET /api/hotspot/status
     */
    public function status(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'router_ip' => 'required|ip',
        ]);

        $username = $request->username;
        $routerIp = $request->router_ip;

        $router = Router::where('ip_address', $routerIp)->first();
        
        if (!$router) {
            return response()->json(['status' => 'error', 'message' => 'Router not found'], 404);
        }

        $session = HotspotSession::where('router_id', $router->id)
            ->where('username', $username)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'No active session'], 404);
        }

        $customer = $session->customer;
        $subscription = $customer?->activeSubscription();

        // Get real-time stats from MikroTik
        $mikrotik = new MikroTikService($router);
        $realTimeStats = [];
        
        if ($mikrotik->connect()) {
            try {
                $connection = $mikrotik->getConnection();
                
                // Only proceed if connection is available
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

                    if (!empty($activeSessions)) {
                        $activeSession = $activeSessions[0];
                        $realTimeStats = [
                            'bytes_uploaded' => $activeSession['bytes-up'] ?? 0,
                            'bytes_downloaded' => $activeSession['bytes-down'] ?? 0,
                            'uptime' => $activeSession['uptime'] ?? '0s',
                            'ip_address' => $activeSession['address'] ?? null,
                            'mac_address' => $activeSession['mac-address'] ?? null,
                        ];

                        // Update session record
                        $session->update([
                            'bytes_uploaded' => $realTimeStats['bytes_uploaded'],
                            'bytes_downloaded' => $realTimeStats['bytes_downloaded'],
                            'uptime_seconds' => $this->parseUptime($realTimeStats['uptime']),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to get real-time stats: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'ok',
            'session' => [
                'id' => $session->id,
                'username' => $session->username,
                'started_at' => $session->started_at->toIso8601String(),
                'bytes_uploaded' => $session->bytes_uploaded,
                'bytes_downloaded' => $session->bytes_downloaded,
                'total_bytes' => $session->total_bytes,
                'uptime_seconds' => $session->uptime_seconds,
            ],
            'customer' => $customer ? [
                'id' => $customer->id,
                'full_name' => $customer->full_name,
                'status' => $customer->status,
            ] : null,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan' => $subscription->plan->name,
                'end_date' => $subscription->end_date->toDateString(),
            ] : null,
            'real_time' => $realTimeStats,
        ]);
    }

    /**
     * Check device limit for customer
     * Returns array with 'allowed' boolean and 'message' string
     */
    protected function checkDeviceLimit(Customer $customer, string $macAddress): array
    {
        // Normalize MAC address
        $macAddress = strtolower(preg_replace('/[^a-f0-9]/i', '', $macAddress));
        if (strlen($macAddress) === 12) {
            $macAddress = implode(':', str_split($macAddress, 2));
        }

        // Get active subscription and plan
        $subscription = $customer->activeSubscription();
        if (!$subscription || !$subscription->plan) {
            return ['allowed' => true, 'message' => 'No active plan'];
        }

        $plan = $subscription->plan;

        // If device limit is null or 0, allow unlimited devices
        if (!$plan->device_limit || $plan->device_limit <= 0) {
            // Register device if not exists
            $this->registerDevice($customer, $macAddress);
            return ['allowed' => true, 'message' => 'Unlimited devices allowed'];
        }

        // Check if device is already registered and active
        $existingDevice = CustomerDevice::where('customer_id', $customer->id)
            ->where('mac_address', $macAddress)
            ->first();

        if ($existingDevice) {
            // If device is blocked, reject
            if ($existingDevice->is_blocked) {
                return [
                    'allowed' => false,
                    'message' => 'Device is blocked. Please contact support.'
                ];
            }

            // Update last seen
            $existingDevice->update([
                'last_seen_at' => now(),
                'is_active' => true,
            ]);

            return ['allowed' => true, 'message' => 'Device recognized'];
        }

        // Check current device count
        $activeDeviceCount = $customer->activeDevices()->count();

        if ($activeDeviceCount >= $plan->device_limit) {
            return [
                'allowed' => false,
                'message' => "Device limit reached ({$plan->device_limit} devices). Please remove a device or contact support."
            ];
        }

        // Register new device
        $this->registerDevice($customer, $macAddress);

        return ['allowed' => true, 'message' => 'Device registered'];
    }

    /**
     * Register a new device for customer
     */
    protected function registerDevice(Customer $customer, string $macAddress): CustomerDevice
    {
        return CustomerDevice::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'mac_address' => $macAddress,
            ],
            [
                'is_active' => true,
                'is_blocked' => false,
                'last_seen_at' => now(),
                'first_seen_at' => now(),
            ]
        );
    }

    /**
     * Create session record
     */
    protected function createSession(Router $router, ?Customer $customer, string $ip, ?string $mac, string $username, ?Voucher $voucher = null)
    {
        return HotspotSession::create([
            'isp_id' => $router->isp_id,
            'router_id' => $router->id,
            'customer_id' => $customer?->id,
            'username' => $username,
            'ip_address' => $ip,
            'mac_address' => $mac,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * Update usage stats when session ends
     */
    protected function updateUsageStats(HotspotSession $session)
    {
        if (!$session->customer_id) {
            return;
        }

        $customer = $session->customer ?? Customer::find($session->customer_id);
        $subscriptionId = $customer?->activeSubscription()?->id;

        $today = now()->toDateString();

        \App\Models\CustomerUsageStat::updateOrCreate(
            [
                'customer_id' => $session->customer_id,
                'date' => $today,
            ],
            [
                'subscription_id' => $subscriptionId,
                'bytes_uploaded' => \DB::raw('bytes_uploaded + ' . $session->bytes_uploaded),
                'bytes_downloaded' => \DB::raw('bytes_downloaded + ' . $session->bytes_downloaded),
                'total_bytes' => \DB::raw('total_bytes + ' . $session->total_bytes),
                'session_count' => \DB::raw('session_count + 1'),
                'total_uptime_seconds' => \DB::raw('total_uptime_seconds + ' . $session->uptime_seconds),
            ]
        );

        app(\App\Services\DataUsageRecordingService::class)->recordHotspotSessionEnd($session->fresh());
    }

    /**
     * Parse MikroTik uptime string to seconds
     */
    protected function parseUptime(string $uptime): int
    {
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

