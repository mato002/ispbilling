<?php

namespace App\Services;

use App\Models\Router;
use App\Models\Customer;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;
use Exception;

class MikroTikService
{
    protected $router;
    protected $connection;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Connect to MikroTik router
     */
    public function connect(): bool
    {
        try {
            // Check if RouterOS API package is available
            // Try different possible class names
            $routerOsClass = null;
            $routerOsConfigClass = null;
            
            if (class_exists('\RouterOS\Client')) {
                $routerOsClass = '\RouterOS\Client';
                $routerOsConfigClass = class_exists('\RouterOS\Config') ? '\RouterOS\Config' : null;
            } elseif (class_exists('\RouterOS\API')) {
                $routerOsClass = '\RouterOS\API';
            } elseif (class_exists('\PEAR2\Net\RouterOS\Client')) {
                $routerOsClass = '\PEAR2\Net\RouterOS\Client';
            }

            if (!$routerOsClass) {
                // For testing without RouterOS package, log warning and return false
                // Don't throw exception to allow testing without physical router
                Log::warning('RouterOS API package not installed. Install with: composer require evgeny-rodionov/routeros-api-php', [
                    'router_id' => $this->router->id,
                    'router_ip' => $this->router->ip_address,
                ]);
                $this->connection = null;
                return false;
            }

            // Using RouterOS API PHP library
            // Package: evgeny-rodionov/routeros-api-php
            if ($routerOsClass === '\RouterOS\Client' && $routerOsConfigClass) {
                // Newer version uses Client with Config
                $config = new $routerOsConfigClass([
                    'host' => $this->router->ip_address,
                    'user' => $this->router->username,
                    'pass' => $this->router->password,
                    'port' => $this->router->port ?? 8728,
                ]);
                $this->connection = new $routerOsClass($config);
            } else {
                // Older version or different package structure
                $this->connection = new $routerOsClass(
                    $this->router->ip_address,
                    $this->router->username,
                    $this->router->password,
                    $this->router->port ?? 8728
                );
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('MikroTik connection failed: ' . $e->getMessage(), [
                'router_id' => $this->router->id,
                'router_ip' => $this->router->ip_address,
                'exception' => get_class($e),
            ]);
            $this->connection = null;
            return false;
        }
    }

    /**
     * Get the connection instance
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Create a Hotspot user
     */
    public function createHotspotUser(Customer $customer, Plan $plan): bool
    {
        if (!$this->connect()) {
            // In local/testing environment, simulate success for testing
            if (app()->environment(['local', 'testing']) && !$this->connection) {
                Log::info('MikroTikService: Simulating hotspot user creation (RouterOS package not installed)', [
                    'customer_id' => $customer->id,
                    'username' => $customer->username,
                ]);
                // Simulate success by setting a fake mikrotik_user_id
                $customer->update(['mikrotik_user_id' => 'sim-' . $customer->id]);
                return true;
            }
            return false;
        }

        try {
            $params = [
                'name' => $customer->username,
                'password' => $customer->password,
                'profile' => $plan->speed_profile ?? 'default',
            ];
            
            if ($plan->billing_type === 'time' && $plan->time_limit_hours) {
                $params['limit-uptime'] = $plan->time_limit_hours . 'h';
            }
            
            if ($plan->billing_type === 'data' && $plan->data_limit_mb) {
                $params['limit-bytes-total'] = $plan->data_limit_mb . 'M';
            }

            // Check if connection has comm method (old API) or query method (new API)
            if (method_exists($this->connection, 'comm')) {
                $response = $this->connection->comm('/ip/hotspot/user/add', $params);
            } elseif (method_exists($this->connection, 'query')) {
                $query = new \RouterOS\Query('/ip/hotspot/user/add');
                foreach ($params as $key => $value) {
                    $query->equal($key, $value);
                }
                $response = $this->connection->query($query)->read();
            } else {
                throw new Exception('Unknown RouterOS API method');
            }
            
            if (isset($response[0]['.id'])) {
                $customer->update(['mikrotik_user_id' => $response[0]['.id']]);
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('Failed to create hotspot user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Activate hotspot user for customer
     * 
     * - Connects to MikroTik API using router credentials
     * - Disconnects any previous active sessions
     * - Creates user if doesn't exist (using customer_id, phone, or username)
     * - Sets password (customer-defined or random)
     * - Sets session expiry based on plan duration
     * - Sets speed limits from plan
     * - Enables the user
     * - Logs all actions for ISP admin
     */
    public function activateHotspotUser(Customer $customer): bool
    {
        if ($customer->connection_type !== 'hotspot') {
            $this->logAction('activate_hotspot_user', $customer, 'failed', 'Customer is not a hotspot user');
            return false;
        }

        if (!$this->connect()) {
            $this->logAction('activate_hotspot_user', $customer, 'failed', 'Failed to connect to router');
            return false;
        }

        try {
            // Step 1: Disconnect any previous active sessions
            $this->disconnectPreviousSessions($customer);

            // Step 2: Get active subscription and plan
            $subscription = $customer->activeSubscription();
            $plan = $subscription?->plan;

            if (!$plan) {
                $this->logAction('activate_hotspot_user', $customer, 'failed', 'No active plan found for customer');
                return false;
            }

            // Step 3: Determine username (prefer customer_id or phone, fallback to username)
            $username = $customer->username;
            if (empty($username)) {
                // Use customer ID or phone as username
                $username = $customer->phone_number ?: 'CUST' . $customer->id;
                $customer->update(['username' => $username]);
            }

            // Step 4: Ensure password is set (use customer password or generate random)
            $password = $customer->password;
            if (empty($password)) {
                $password = \Illuminate\Support\Str::random(12);
                $customer->update(['password' => \Illuminate\Support\Facades\Hash::make($password)]);
            }

            // Step 5: Create user on MikroTik if doesn't exist
            if (!$customer->mikrotik_user_id) {
                $created = $this->createHotspotUserWithPlan($customer, $plan, $username, $password);
                if (!$created) {
                    $this->logAction('activate_hotspot_user', $customer, 'failed', 'Failed to create user on router');
                    return false;
                }
            } else {
                // Update existing user with current plan settings
                $this->updateUserWithPlan($customer, $plan, $username, $password);
            }

            // Step 6: Enable user
            $enabled = $this->enableUser($customer);
            
            if ($enabled) {
                $this->logAction('activate_hotspot_user', $customer, 'success', 
                    "Hotspot user activated: {$username}, Plan: {$plan->name}, Duration: {$plan->duration_days} days");
                return true;
            } else {
                $this->logAction('activate_hotspot_user', $customer, 'failed', 'Failed to enable user on router');
                return false;
            }

        } catch (Exception $e) {
            $this->logAction('activate_hotspot_user', $customer, 'failed', 
                'Exception: ' . $e->getMessage(), $e->getMessage());
            Log::error('activateHotspotUser failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Disconnect previous active sessions for customer
     */
    protected function disconnectPreviousSessions(Customer $customer): void
    {
        try {
            if (!$this->connect()) {
                return;
            }

            // Get all active sessions for this username
            $activeSessions = $this->connection->comm('/ip/hotspot/active/print', [
                '?user' => $customer->username,
            ]);

            if (!empty($activeSessions)) {
                foreach ($activeSessions as $session) {
                    $sessionId = $session['.id'] ?? null;
                    if ($sessionId) {
                        $this->connection->comm('/ip/hotspot/active/remove', [
                            '.id' => $sessionId,
                        ]);
                        $this->logAction('disconnect_session', $customer, 'success', 
                            "Disconnected previous session: {$sessionId}");
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to disconnect previous sessions: ' . $e->getMessage());
            $this->logAction('disconnect_session', $customer, 'failed', 
                'Failed to disconnect: ' . $e->getMessage(), $e->getMessage());
        }
    }

    /**
     * Create hotspot user with specific username and password
     */
    protected function createHotspotUserWithPlan(Customer $customer, Plan $plan, string $username, string $password): bool
    {
        try {
            $params = [
                'name' => $username,
                'password' => $password,
                'profile' => $plan->speed_profile ?? 'default',
            ];
            
            // Set session expiry based on plan duration
            if ($plan->duration_days) {
                $params['limit-uptime'] = ($plan->duration_days * 24) . 'h';
            }
            
            // Set time limit if specified
            if ($plan->billing_type === 'time' && $plan->time_limit_hours) {
                $params['limit-uptime'] = $plan->time_limit_hours . 'h';
            }
            
            // Set data limit if specified
            if ($plan->billing_type === 'data' && $plan->data_limit_mb) {
                $params['limit-bytes-total'] = $plan->data_limit_mb . 'M';
            }

            // Set speed limits if specified
            if ($plan->download_speed_kbps) {
                $params['limit-bytes-out'] = ($plan->download_speed_kbps * 1024) . 'bps';
            }
            if ($plan->upload_speed_kbps) {
                $params['limit-bytes-in'] = ($plan->upload_speed_kbps * 1024) . 'bps';
            }

            $response = $this->connection->comm('/ip/hotspot/user/add', $params);
            
            if (isset($response[0]['.id'])) {
                $customer->update(['mikrotik_user_id' => $response[0]['.id']]);
                $this->logAction('create_hotspot_user', $customer, 'success', 
                    "Created user: {$username}", $params, $response[0] ?? []);
                return true;
            }

            $this->logAction('create_hotspot_user', $customer, 'failed', 
                'Invalid response from router', $params, $response ?? []);
            return false;
        } catch (Exception $e) {
            Log::error('Failed to create hotspot user: ' . $e->getMessage());
            $this->logAction('create_hotspot_user', $customer, 'failed', 
                'Exception: ' . $e->getMessage(), null, null, $e->getMessage());
            return false;
        }
    }

    /**
     * Update existing user with plan settings
     */
    protected function updateUserWithPlan(Customer $customer, Plan $plan, string $username, string $password): bool
    {
        try {
            $params = [
                '.id' => $customer->mikrotik_user_id,
                'name' => $username,
                'password' => $password,
                'profile' => $plan->speed_profile ?? 'default',
            ];
            
            // Update session expiry
            if ($plan->duration_days) {
                $params['limit-uptime'] = ($plan->duration_days * 24) . 'h';
            }
            
            if ($plan->billing_type === 'time' && $plan->time_limit_hours) {
                $params['limit-uptime'] = $plan->time_limit_hours . 'h';
            }
            
            if ($plan->billing_type === 'data' && $plan->data_limit_mb) {
                $params['limit-bytes-total'] = $plan->data_limit_mb . 'M';
            }

            // Update speed limits
            if ($plan->download_speed_kbps) {
                $params['limit-bytes-out'] = ($plan->download_speed_kbps * 1024) . 'bps';
            }
            if ($plan->upload_speed_kbps) {
                $params['limit-bytes-in'] = ($plan->upload_speed_kbps * 1024) . 'bps';
            }

            $response = $this->connection->comm('/ip/hotspot/user/set', $params);
            $this->logAction('update_hotspot_user', $customer, 'success', 
                "Updated user: {$username}", $params, $response ?? []);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to update hotspot user: ' . $e->getMessage());
            $this->logAction('update_hotspot_user', $customer, 'failed', 
                'Exception: ' . $e->getMessage(), null, null, $e->getMessage());
            return false;
        }
    }

    /**
     * Log router action for ISP admin
     */
    protected function logAction(
        string $action,
        Customer $customer,
        string $status,
        string $description,
        ?array $requestData = null,
        ?array $responseData = null,
        ?string $errorMessage = null
    ): void {
        try {
            \App\Models\RouterActionLog::log([
                'isp_id' => $this->router->isp_id,
                'router_id' => $this->router->id,
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'action' => $action,
                'status' => $status,
                'description' => $description,
                'error_message' => $errorMessage,
                'request_data' => $requestData,
                'response_data' => $responseData,
            ]);
        } catch (\Exception $e) {
            // Don't fail if logging fails
            Log::error('Failed to log router action: ' . $e->getMessage());
        }
    }

    /**
     * Create a PPPoE user
     */
    public function createPppoeUser(Customer $customer, Plan $plan): bool
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            $params = [
                'name' => $customer->username,
                'password' => $customer->password,
                'profile' => $plan->speed_profile ?? 'default',
                'service' => 'pppoe',
                'remote-address' => $this->getNextIpAddress(),
            ];

            $response = $this->connection->comm('/ppp/secret/add', $params);
            
            if (isset($response[0]['.id'])) {
                $customer->update(['mikrotik_user_id' => $response[0]['.id']]);
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('Failed to create PPPoE user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enable user (activate)
     */
    public function enableUser(Customer $customer): bool
    {
        if (!$this->connect() || !$customer->mikrotik_user_id) {
            $this->logAction('enable_user', $customer, 'failed', 'Failed to connect or user ID missing');
            return false;
        }

        try {
            $path = $customer->connection_type === 'hotspot' 
                ? '/ip/hotspot/user/enable' 
                : '/ppp/secret/enable';

            $this->connection->comm($path, ['.id' => $customer->mikrotik_user_id]);
            
            $this->logAction('enable_user', $customer, 'success', 
                "Enabled {$customer->connection_type} user: {$customer->username}");
            return true;
        } catch (Exception $e) {
            Log::error('Failed to enable user: ' . $e->getMessage());
            $this->logAction('enable_user', $customer, 'failed', 
                'Failed to enable user: ' . $e->getMessage(), null, null, $e->getMessage());
            return false;
        }
    }

    /**
     * Disable user (suspend)
     */
    public function disableUser(Customer $customer): bool
    {
        if (!$this->connect() || !$customer->mikrotik_user_id) {
            $this->logAction('disable_user', $customer, 'failed', 'Failed to connect or user ID missing');
            return false;
        }

        try {
            $path = $customer->connection_type === 'hotspot' 
                ? '/ip/hotspot/user/disable' 
                : '/ppp/secret/disable';

            $this->connection->comm($path, ['.id' => $customer->mikrotik_user_id]);
            
            $this->logAction('disable_user', $customer, 'success', 
                "Disabled {$customer->connection_type} user: {$customer->username}");
            return true;
        } catch (Exception $e) {
            Log::error('Failed to disable user: ' . $e->getMessage());
            $this->logAction('disable_user', $customer, 'failed', 
                'Failed to disable user: ' . $e->getMessage(), null, null, $e->getMessage());
            return false;
        }
    }

    /**
     * Remove user
     */
    public function removeUser(Customer $customer): bool
    {
        if (!$this->connect() || !$customer->mikrotik_user_id) {
            return false;
        }

        try {
            $path = $customer->connection_type === 'hotspot' 
                ? '/ip/hotspot/user/remove' 
                : '/ppp/secret/remove';

            $this->connection->comm($path, ['.id' => $customer->mikrotik_user_id]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to remove user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Disconnect active session
     */
    public function disconnectSession(Customer $customer): bool
    {
        if (!$this->connect()) {
            $this->logAction('disconnect_session', $customer, 'failed', 'Failed to connect to router');
            return false;
        }

        try {
            if ($customer->connection_type === 'hotspot') {
                $this->connection->comm('/ip/hotspot/active/remove', ['user' => $customer->username]);
            } else {
                $this->connection->comm('/ppp/active/remove', ['name' => $customer->username]);
            }

            $this->logAction('disconnect_session', $customer, 'success', 
                "Disconnected active session for user: {$customer->username}");
            return true;
        } catch (Exception $e) {
            Log::error('Failed to disconnect session: ' . $e->getMessage());
            $this->logAction('disconnect_session', $customer, 'failed', 
                'Failed to disconnect session: ' . $e->getMessage(), null, null, $e->getMessage());
            return false;
        }
    }

    /**
     * Update user profile/speed
     */
    public function updateUserProfile(Customer $customer, Plan $plan): bool
    {
        if (!$this->connect() || !$customer->mikrotik_user_id) {
            return false;
        }

        try {
            $path = $customer->connection_type === 'hotspot' 
                ? '/ip/hotspot/user/set' 
                : '/ppp/secret/set';

            $this->connection->comm($path, [
                '.id' => $customer->mikrotik_user_id,
                'profile' => $plan->speed_profile ?? 'default',
            ]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to update user profile: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get next available IP address for PPPoE
     */
    protected function getNextIpAddress(): string
    {
        // This should be configured per router/IP pool
        // For now, return a placeholder
        return '192.168.1.100';
    }

    /**
     * Get user statistics
     */
    public function getUserStats(Customer $customer): array
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            if ($customer->connection_type === 'hotspot') {
                $response = $this->connection->comm('/ip/hotspot/user/print', [
                    '?name' => $customer->username,
                ]);
            } else {
                $response = $this->connection->comm('/ppp/secret/print', [
                    '?name' => $customer->username,
                ]);
            }

            return $response[0] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get user stats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all active hotspot sessions
     */
    public function getActiveHotspotSessions(): array
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            $response = $this->connection->comm('/ip/hotspot/active/print');
            return $response ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get active hotspot sessions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active PPPoE sessions
     */
    public function getActivePppoeSessions(): array
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            $response = $this->connection->comm('/ppp/active/print');
            return $response ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get active PPPoE sessions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync active sessions with database
     */
    public function syncActiveSessions(): int
    {
        if (!$this->connect()) {
            return 0;
        }

        $synced = 0;
        try {
            $activeSessions = $this->getActiveHotspotSessions();
            
            foreach ($activeSessions as $session) {
                $username = $session['user'] ?? null;
                if (!$username) continue;

                // Find customer
                $customer = Customer::where('username', $username)
                    ->where('router_id', $this->router->id)
                    ->first();

                // Update or create session record
                \App\Models\HotspotSession::updateOrCreate(
                    [
                        'username' => $username,
                        'router_id' => $this->router->id,
                        'status' => 'active',
                    ],
                    [
                        'isp_id' => $this->router->isp_id,
                        'customer_id' => $customer?->id,
                        'ip_address' => $session['address'] ?? null,
                        'mac_address' => $session['mac-address'] ?? null,
                        'mikrotik_session_id' => $session['.id'] ?? null,
                        'bytes_uploaded' => $session['bytes-up'] ?? 0,
                        'bytes_downloaded' => $session['bytes-down'] ?? 0,
                        'uptime_seconds' => $this->parseUptime($session['uptime'] ?? '0s'),
                        'started_at' => now(),
                    ]
                );
                $synced++;
            }

            // Mark sessions that are no longer active
            $activeUsernames = collect($activeSessions)->pluck('user')->filter();
            \App\Models\HotspotSession::where('router_id', $this->router->id)
                ->where('status', 'active')
                ->whereNotIn('username', $activeUsernames)
                ->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                ]);

        } catch (Exception $e) {
            Log::error('Failed to sync active sessions: ' . $e->getMessage());
        }

        return $synced;
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

