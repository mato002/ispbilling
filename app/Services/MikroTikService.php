<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\HotspotServer;
use App\Models\InventoryItem;
use App\Models\Plan;
use App\Models\Router;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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

            if (! $routerOsClass) {
                // For testing without RouterOS package, log warning and return false
                // Don't throw exception to allow testing without physical router
                Log::warning('RouterOS API package not installed. Run: composer require evilfreelancer/routeros-api-php', [
                    'router_id' => $this->router->id,
                    'router_ip' => $this->router->ip_address,
                ]);
                $this->connection = null;

                return false;
            }

            // Using RouterOS API PHP library
            // Package: evilfreelancer/routeros-api-php
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
            Log::error('MikroTik connection failed: '.$e->getMessage(), [
                'router_id' => $this->router->id,
                'router_ip' => $this->router->ip_address,
                'exception' => get_class($e),
            ]);
            try {
                \App\Services\ActivityLogService::logRouterApiConnectionFailed(
                    $this->router,
                    $e->getMessage()
                );
            } catch (\Throwable) {
            }
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
     * Verify API connectivity and return hotspot server definitions for import.
     *
     * @return array{success: bool, error?: string, hotspot_servers: array<int, array<string, mixed>>, hotspot_servers_count: int, router_info?: array<string, mixed>}
     */
    public function testConnection(): array
    {
        $empty = fn (string $error) => [
            'success' => false,
            'error' => $error,
            'hotspot_servers' => [],
            'hotspot_servers_count' => 0,
            'router_info' => null,
        ];

        if (! $this->routerOsLibraryAvailable()) {
            return $empty('RouterOS API PHP library is not installed. Run: composer require evilfreelancer/routeros-api-php. On XAMPP/Windows, enable extension=sockets in php.ini and restart Apache.');
        }

        if (! $this->connect()) {
            return $empty('Could not open RouterOS API session. Check IP/hostname, API port (default 8728), username, password, and firewall rules.');
        }

        try {
            $identityRows = $this->apiPrint('/system/identity/print');
            $routerInfo = $identityRows[0] ?? ['name' => $this->router->ip_address];

            $hotspotRows = $this->apiPrint('/ip/hotspot/print');
            $hotspotServers = [];
            foreach ($hotspotRows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $hotspotServers[] = $this->mapHotspotServerImportRow($row);
            }

            return [
                'success' => true,
                'hotspot_servers' => $hotspotServers,
                'hotspot_servers_count' => count($hotspotServers),
                'router_info' => $routerInfo,
            ];
        } catch (\Throwable $e) {
            Log::warning('MikroTik testConnection failed: '.$e->getMessage(), [
                'router_id' => $this->router->id ?? null,
                'ip' => $this->router->ip_address ?? null,
            ]);

            return $empty('RouterOS API error: '.$e->getMessage());
        }
    }

    /**
     * Hotspot user profile names from the router (for billing plan import).
     *
     * @return array<int, array{name:string,shared_users:?int,rate_limit:?string}>
     */
    public function listHotspotUserProfiles(): array
    {
        if (! $this->routerOsLibraryAvailable() || ! $this->connect()) {
            return [];
        }

        try {
            $rows = $this->apiPrint('/ip/hotspot/user/profile/print');
            $out = [];
            foreach ($rows as $row) {
                if (! is_array($row) || empty($row['name'])) {
                    continue;
                }
                $out[] = [
                    'name' => (string) $row['name'],
                    'shared_users' => isset($row['shared-users']) ? (int) $row['shared-users'] : null,
                    'rate_limit' => isset($row['rate-limit']) ? (string) $row['rate-limit'] : null,
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('MikroTik listHotspotUserProfiles failed: '.$e->getMessage());

            return [];
        }
    }

    /** Create or update the RouterOS hotspot profile represented by a billing plan. */
    public function syncHotspotPlanProfile(Plan $plan): bool
    {
        $profileName = trim((string) $plan->speed_profile);
        if ($profileName === '' || ! $this->routerOsLibraryAvailable() || ! $this->connect()) {
            return false;
        }

        try {
            $profiles = $this->apiPrint('/ip/hotspot/user/profile/print');
            $existing = collect($profiles)->first(fn ($profile) => is_array($profile) && ($profile['name'] ?? null) === $profileName);
            $params = ['name' => $profileName];

            if ($plan->upload_speed_kbps || $plan->download_speed_kbps) {
                $params['rate-limit'] = ($plan->upload_speed_kbps ? $plan->upload_speed_kbps.'k' : '0')
                    .'/'.($plan->download_speed_kbps ? $plan->download_speed_kbps.'k' : '0');
            }

            if ($existing && ! isset($params['rate-limit'])) {
                return true;
            }

            if ($existing) {
                $params['.id'] = $existing['.id'] ?? null;
                if (! $params['.id']) {
                    return false;
                }
                $path = '/ip/hotspot/user/profile/set';
            } else {
                $path = '/ip/hotspot/user/profile/add';
            }

            return $this->apiCommand($path, $params);
        } catch (\Throwable $e) {
            Log::warning('MikroTik hotspot plan profile sync failed', [
                'router_id' => $this->router->id,
                'plan_id' => $plan->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** Push idle and keepalive behavior to the Hotspot user profile used by this server. */
    public function syncHotspotSessionTimeouts(HotspotServer $server): bool
    {
        $profileName = trim((string) $server->default_profile);
        if ($profileName === '' || ! $this->routerOsLibraryAvailable() || ! $this->connect()) {
            return false;
        }

        try {
            $profiles = $this->apiPrint('/ip/hotspot/user/profile/print');
            $profile = collect($profiles)->first(fn ($row) => is_array($row) && ($row['name'] ?? null) === $profileName);
            if (! $profile || empty($profile['.id'])) {
                return false;
            }

            $duration = static fn ($seconds) => (int) $seconds > 0 ? (int) $seconds.'s' : 'none';
            return $this->apiCommand('/ip/hotspot/user/profile/set', [
                '.id' => $profile['.id'],
                'idle-timeout' => $duration($server->idle_timeout),
                'keepalive-timeout' => $duration($server->keepalive_timeout),
            ]);
        } catch (\Throwable $e) {
            Log::warning('MikroTik hotspot timeout profile sync failed', [
                'router_id' => $this->router->id,
                'hotspot_server_id' => $server->id,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /** @param array<string, string|null> $params */
    protected function apiCommand(string $path, array $params): bool
    {
        if (! $this->connection) {
            return false;
        }

        if (method_exists($this->connection, 'comm')) {
            $response = $this->connection->comm($path, array_filter($params, fn ($value) => $value !== null));
            return ! collect(is_array($response) ? $response : [])->contains(fn ($row) => isset($row['!trap']) || isset($row['!fatal']));
        }

        if (method_exists($this->connection, 'query')) {
            $query = new \RouterOS\Query($path);
            foreach ($params as $key => $value) {
                if ($value !== null) {
                    $query->equal($key, $value);
                }
            }
            $response = $this->connection->query($query)->read();
            return ! collect(is_array($response) ? $response : [])->contains(fn ($row) => isset($row['!trap']) || isset($row['!fatal']));
        }

        return false;
    }

    protected function routerOsLibraryAvailable(): bool
    {
        if (class_exists('\RouterOS\Client')) {
            return true;
        }
        if (class_exists('\RouterOS\API')) {
            return true;
        }
        if (class_exists('\PEAR2\Net\RouterOS\Client')) {
            return true;
        }

        return false;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function apiPrint(string $path): array
    {
        if (! $this->connection) {
            return [];
        }

        if (method_exists($this->connection, 'comm')) {
            $response = $this->connection->comm($path);

            return is_array($response) ? $response : [];
        }

        if (method_exists($this->connection, 'query')) {
            $query = new \RouterOS\Query($path);
            $response = $this->connection->query($query)->read();

            return is_array($response) ? $response : [];
        }

        return [];
    }

    /**
     * Map a row from /ip/hotspot/print to the shape expected by RouterController::importHotspotServers().
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapHotspotServerImportRow(array $row): array
    {
        $bool = fn ($v): bool => in_array((string) $v, ['true', 'yes', '1'], true);

        return [
            'name' => (string) ($row['name'] ?? 'hotspot'),
            'interface' => (string) ($row['interface'] ?? 'unknown'),
            'server_profile' => $row['profile'] ?? null,
            'default_profile' => $row['profile'] ?? null,
            'login_by' => $this->normalizeLoginBy((string) ($row['login-by'] ?? 'cookie')),
            'use_radius' => $bool($row['use-radius'] ?? false),
            'radius_accounting' => $bool($row['radius-accounting'] ?? false),
            'mac_cookie_timeout' => $this->parseMtDurationToSeconds($row['http-cookie-lifetime'] ?? null) ?? 300,
            'idle_timeout' => $this->parseMtDurationToSeconds($row['idle-timeout'] ?? null) ?? 300,
            'keepalive_timeout' => $this->parseMtDurationToSeconds($row['keepalive-timeout'] ?? null) ?? 60,
            'status_autologout' => $bool($row['status-autologout'] ?? false),
            'allowed_users' => (int) ($row['addresses-per-mac'] ?? 0) ?: 0,
            'shared_users' => (int) ($row['shared-users'] ?? 0) ?: 0,
            'mac_format' => $row['mac-format'] ?? null,
            'mac_password' => $row['mac-password'] ?? null,
            'radius_mac_format' => $row['radius-mac-format'] ?? null,
            'radius_mac_password' => $row['radius-mac-password'] ?? null,
            'trial_uptime' => (int) ($row['trial-uptime-limit'] ?? 0),
            'trial_uptime_reset' => (int) ($row['trial-uptime-reset'] ?? 0),
            'trial_profile' => $row['trial-user-profile'] ?? null,
            'trial_users' => (int) ($row['trial-users'] ?? 0),
            'disabled' => $bool($row['disabled'] ?? false),
            'running' => ! $bool($row['disabled'] ?? false),
        ];
    }

    protected function normalizeLoginBy(string $loginBy): string
    {
        $loginBy = strtolower(trim(explode(',', $loginBy)[0] ?? 'cookie'));

        return in_array($loginBy, ['cookie', 'http-chap', 'mac'], true) ? $loginBy : 'cookie';
    }

    protected function parseMtDurationToSeconds(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $s = (string) $value;
        $seconds = 0;
        if (preg_match('/(\d+)w/i', $s, $m)) {
            $seconds += (int) $m[1] * 604800;
        }
        if (preg_match('/(\d+)d/i', $s, $m)) {
            $seconds += (int) $m[1] * 86400;
        }
        if (preg_match('/(\d+)h/i', $s, $m)) {
            $seconds += (int) $m[1] * 3600;
        }
        if (preg_match('/(\d+)m/i', $s, $m)) {
            $seconds += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)s/i', $s, $m)) {
            $seconds += (int) $m[1];
        }

        return $seconds > 0 ? $seconds : null;
    }

    /**
     * Create a Hotspot user
     *
     * @param  string|null  $plainPassword  Plaintext password for the router (required when DB stores a hash)
     */
    public function createHotspotUser(Customer $customer, Plan $plan, ?string $plainPassword = null): bool
    {
        $passwordForApi = $plainPassword ?? $customer->hotspot_password;
        if (! $passwordForApi) {
            $passwordForApi = \Illuminate\Support\Str::random(24);
            $customer->forceFill(['hotspot_password' => $passwordForApi])->save();
        }

        if (! $this->connect()) {
            // In local/testing environment, simulate success for testing
            if (app()->environment(['local', 'testing']) && ! $this->connection) {
                Log::info('MikroTikService: Simulating hotspot user creation (RouterOS package not installed)', [
                    'customer_id' => $customer->id,
                    'username' => $customer->username,
                ]);
                // Simulate success by setting a fake mikrotik_user_id
                $customer->update(['mikrotik_user_id' => 'sim-'.$customer->id]);

                return true;
            }

            return false;
        }

        try {
            $params = [
                'name' => $customer->username,
                'password' => $passwordForApi,
                'profile' => $plan->speed_profile ?? 'default',
            ];

            if ($customer->hotspot_mac_address) {
                $params['mac-address'] = $customer->hotspot_mac_address;
            }

            if ($plan->upload_speed_kbps || $plan->download_speed_kbps) {
                $params['rate-limit'] = ($plan->upload_speed_kbps ?: 0).'k/'.($plan->download_speed_kbps ?: 0).'k';
            }

            if ($plan->tracksDataUsage() && $plan->data_limit_mb > 0) {
                $params['limit-bytes-total'] = (string) ((int) $plan->data_limit_mb * 1024 * 1024);
            } else {
                $params['limit-bytes-total'] = '0';
            }

            if ($plan->billing_type === 'time' && $plan->time_limit_hours) {
                $params['limit-uptime'] = $plan->time_limit_hours.'h';
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
            Log::error('Failed to create hotspot user: '.$e->getMessage());

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

        if (! $this->connect()) {
            $this->logAction('activate_hotspot_user', $customer, 'failed', 'Failed to connect to router');

            return false;
        }

        try {
            // Step 1: Disconnect any previous active sessions
            $this->disconnectPreviousSessions($customer);

            // Step 2: Get active subscription and plan
            $subscription = $customer->activeSubscription();
            $plan = $subscription?->plan;

            if (! $plan) {
                $this->logAction('activate_hotspot_user', $customer, 'failed', 'No active plan found for customer');

                return false;
            }

            // Step 3: Determine username (prefer customer_id or phone, fallback to username)
            $username = $customer->username;
            if (empty($username)) {
                // Use customer ID or phone as username
                $username = $customer->phone_number ?: 'CUST'.$customer->id;
                $customer->update(['username' => $username]);
            }

            // Step 4: Ensure password is set (use customer password or generate random)
            $password = $customer->hotspot_password;
            if (empty($password)) {
                $password = \Illuminate\Support\Str::random(24);
                $customer->forceFill(['hotspot_password' => $password])->save();
            }

            // Step 5: Create user on MikroTik if doesn't exist
            if (! $customer->mikrotik_user_id) {
                $created = $this->createHotspotUserWithPlan($customer, $plan, $username, $password);
                if (! $created) {
                    $this->logAction('activate_hotspot_user', $customer, 'failed', 'Failed to create user on router');

                    return false;
                }
            } else {
                // Update existing user with current plan settings
                if (! $this->updateUserWithPlan($customer, $plan, $username, $password)) {
                    $this->logAction('activate_hotspot_user', $customer, 'failed', 'Failed to apply plan settings to router user');

                    return false;
                }
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
                'Exception: '.$e->getMessage(), $e->getMessage());
            Log::error('activateHotspotUser failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Disconnect previous active sessions for customer
     */
    protected function disconnectPreviousSessions(Customer $customer): void
    {
        try {
            if (! $this->connect()) {
                return;
            }

            // Get all active sessions for this username
            $activeSessions = $this->connection->comm('/ip/hotspot/active/print', [
                '?user' => $customer->username,
            ]);

            if (! empty($activeSessions)) {
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
            Log::error('Failed to disconnect previous sessions: '.$e->getMessage());
            $this->logAction('disconnect_session', $customer, 'failed',
                'Failed to disconnect: '.$e->getMessage(), $e->getMessage());
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
            if ($customer->hotspot_mac_address) {
                $params['mac-address'] = $customer->hotspot_mac_address;
            }

            // Set session expiry based on plan duration
            if ($plan->duration_days) {
                $params['limit-uptime'] = ($plan->duration_days * 24).'h';
            }

            // Set time limit if specified
            if ($plan->billing_type === 'time' && $plan->time_limit_hours) {
                $params['limit-uptime'] = $plan->time_limit_hours.'h';
            }

            // Set data limit if specified
            if ($plan->tracksDataUsage() && $plan->data_limit_mb > 0) {
                $params['limit-bytes-total'] = (string) ((int) $plan->data_limit_mb * 1024 * 1024);
            } else {
                // Clear a prior data quota when a customer changes to an unlimited plan.
                $params['limit-bytes-total'] = '0';
            }

            if ($plan->upload_speed_kbps || $plan->download_speed_kbps) {
                $params['rate-limit'] = ($plan->upload_speed_kbps ?: 0).'k/'.($plan->download_speed_kbps ?: 0).'k';
            }
            // Set speed limits if specified
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
            Log::error('Failed to create hotspot user: '.$e->getMessage());
            $this->logAction('create_hotspot_user', $customer, 'failed',
                'Exception: '.$e->getMessage(), null, null, $e->getMessage());

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
            if ($customer->hotspot_mac_address) {
                $params['mac-address'] = $customer->hotspot_mac_address;
            }

            // Update session expiry
            if ($plan->duration_days) {
                $params['limit-uptime'] = ($plan->duration_days * 24).'h';
            }

            if ($plan->billing_type === 'time' && $plan->time_limit_hours) {
                $params['limit-uptime'] = $plan->time_limit_hours.'h';
            }

            if ($plan->tracksDataUsage() && $plan->data_limit_mb > 0) {
                $params['limit-bytes-total'] = (string) ((int) $plan->data_limit_mb * 1024 * 1024);
            }

            if ($plan->upload_speed_kbps || $plan->download_speed_kbps) {
                $params['rate-limit'] = ($plan->upload_speed_kbps ?: 0).'k/'.($plan->download_speed_kbps ?: 0).'k';
            }
            $response = $this->connection->comm('/ip/hotspot/user/set', $params);
            $this->logAction('update_hotspot_user', $customer, 'success',
                "Updated user: {$username}", $params, $response ?? []);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to update hotspot user: '.$e->getMessage());
            $this->logAction('update_hotspot_user', $customer, 'failed',
                'Exception: '.$e->getMessage(), null, null, $e->getMessage());

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
            Log::error('Failed to log router action: '.$e->getMessage());
        }
    }

    /**
     * Create a PPPoE user
     *
     * @param  string|null  $plainPassword  Plaintext password for the router (required when DB stores a hash)
     */
    public function createPppoeUser(Customer $customer, Plan $plan, ?string $plainPassword = null): bool
    {
        $passwordForApi = $plainPassword ?? $customer->password;

        if (! $this->connect()) {
            if (app()->environment(['local', 'testing']) && ! $this->connection) {
                Log::info('MikroTikService: Simulating PPPoE user creation (RouterOS package not installed)', [
                    'customer_id' => $customer->id,
                    'username' => $customer->username,
                ]);
                $customer->update(['mikrotik_user_id' => 'sim-pppoe-'.$customer->id]);

                return true;
            }

            return false;
        }

        try {
            return Cache::lock('router-pppoe-ip-allocation:'.$this->router->id, 60)->block(15, function () use ($customer, $plan, $passwordForApi) {
            $params = [
                'name' => $customer->username,
                'password' => $passwordForApi,
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
            });
        } catch (Exception $e) {
            Log::error('Failed to create PPPoE user: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Bind an installed inventory device to the customer's router profile (PPPoE caller-id or Hotspot MAC).
     */
    public function bindInventoryDeviceToCustomer(Customer $customer, InventoryItem $item): bool
    {
        if (! $this->connect()) {
            $this->logAction('bind_inventory_device', $customer, 'failed', 'Failed to connect to router');

            return false;
        }

        $dotId = $this->resolveMikrotikInternalUserId($customer)
            ?? ($customer->mikrotik_user_id ? (string) $customer->mikrotik_user_id : null);

        if (! $dotId && $customer->connection_type === 'pppoe') {
            $subscription = $customer->activeSubscription();
            if ($subscription?->plan && $this->createPppoeUser($customer, $subscription->plan)) {
                $dotId = $this->resolveMikrotikInternalUserId($customer)
                    ?? ($customer->fresh()->mikrotik_user_id ? (string) $customer->fresh()->mikrotik_user_id : null);
            }
        }

        if (! $dotId) {
            $this->logAction('bind_inventory_device', $customer, 'failed',
                'No RouterOS user found to bind inventory device — username/MAC not applied on router.');

            return false;
        }

        if ((string) ($customer->mikrotik_user_id ?? '') !== $dotId) {
            $customer->forceFill(['mikrotik_user_id' => $dotId])->saveQuietly();
        }

        try {
            $params = ['.id' => $dotId];
            $description = "Bound inventory device {$item->serial_number} to {$customer->username}";

            if ($item->mac_address) {
                $mac = $this->normalizeMacAddress($item->mac_address);
                if ($customer->connection_type === 'hotspot') {
                    $params['mac-address'] = $mac;
                    $description .= " (MAC {$mac})";
                } else {
                    $params['caller-id'] = $mac;
                    $description .= " (caller-id {$mac})";
                }
            } else {
                $description .= ' (username profile only)';
            }

            $path = $customer->connection_type === 'hotspot'
                ? '/ip/hotspot/user/set'
                : '/ppp/secret/set';

            $this->connection->comm($path, $params);

            $this->logAction('bind_inventory_device', $customer, 'success', $description, $params);

            return true;
        } catch (Exception $e) {
            Log::error('bindInventoryDeviceToCustomer failed: '.$e->getMessage());
            $this->logAction('bind_inventory_device', $customer, 'failed',
                'Failed to bind device: '.$e->getMessage(), null, null, $e->getMessage());

            return false;
        }
    }

    protected function normalizeMacAddress(string $mac): string
    {
        $value = strtolower(preg_replace('/[^a-f0-9]/i', '', $mac) ?? '');

        if (strlen($value) === 12) {
            return implode(':', str_split($value, 2));
        }

        return $mac;
    }

    /**
     * Resolve RouterOS internal .id for this customer: match by username, then by comment "PB-ID:{customer.id}".
     */
    protected function resolveMikrotikInternalUserId(Customer $customer): ?string
    {
        if (! $this->connection) {
            return null;
        }

        $tag = 'PB-ID:'.$customer->id;

        try {
            if ($customer->connection_type === 'hotspot') {
                if ($customer->username) {
                    $rows = $this->connection->comm('/ip/hotspot/user/print', ['?name' => $customer->username]);
                    if (! empty($rows[0]['.id'])) {
                        return (string) $rows[0]['.id'];
                    }
                }
                $rows = $this->connection->comm('/ip/hotspot/user/print', ['?comment' => $tag]);
                if (! empty($rows[0]['.id'])) {
                    return (string) $rows[0]['.id'];
                }
            } else {
                if ($customer->username) {
                    $rows = $this->connection->comm('/ppp/secret/print', ['?name' => $customer->username]);
                    if (! empty($rows[0]['.id'])) {
                        return (string) $rows[0]['.id'];
                    }
                }
                $rows = $this->connection->comm('/ppp/secret/print', ['?comment' => $tag]);
                if (! empty($rows[0]['.id'])) {
                    return (string) $rows[0]['.id'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('resolveMikrotikInternalUserId: '.$e->getMessage(), [
                'customer_id' => $customer->id,
                'router_id' => $this->router->id,
            ]);
        }

        return null;
    }

    /**
     * Enable user (activate)
     */
    public function enableUser(Customer $customer): bool
    {
        if (! $this->connect()) {
            $this->logAction('enable_user', $customer, 'failed', 'Failed to connect to router');

            return false;
        }

        $dotId = $this->resolveMikrotikInternalUserId($customer)
            ?? ($customer->mikrotik_user_id ? (string) $customer->mikrotik_user_id : null);

        if (! $dotId) {
            $this->logAction('enable_user', $customer, 'failed', 'Could not resolve RouterOS .id');

            return false;
        }

        if ((string) ($customer->mikrotik_user_id ?? '') !== $dotId) {
            $customer->forceFill(['mikrotik_user_id' => $dotId])->saveQuietly();
        }

        try {
            $path = $customer->connection_type === 'hotspot'
                ? '/ip/hotspot/user/enable'
                : '/ppp/secret/enable';

            $this->connection->comm($path, ['.id' => $dotId]);

            $this->logAction('enable_user', $customer, 'success',
                "Enabled {$customer->connection_type} user: {$customer->username}");

            return true;
        } catch (Exception $e) {
            Log::error('Failed to enable user: '.$e->getMessage());
            $this->logAction('enable_user', $customer, 'failed',
                'Failed to enable user: '.$e->getMessage(), null, null, $e->getMessage());

            return false;
        }
    }

    /**
     * Disable user (suspend)
     */
    public function disableUser(Customer $customer): bool
    {
        if (! $this->connect()) {
            $this->logAction('disable_user', $customer, 'failed', 'Failed to connect to router');

            return false;
        }

        $dotId = $this->resolveMikrotikInternalUserId($customer)
            ?? ($customer->mikrotik_user_id ? (string) $customer->mikrotik_user_id : null);

        if (! $dotId) {
            $this->logAction('disable_user', $customer, 'failed', 'Could not resolve RouterOS .id');

            return false;
        }

        if ((string) ($customer->mikrotik_user_id ?? '') !== $dotId) {
            $customer->forceFill(['mikrotik_user_id' => $dotId])->saveQuietly();
        }

        try {
            $path = $customer->connection_type === 'hotspot'
                ? '/ip/hotspot/user/disable'
                : '/ppp/secret/disable';

            $this->connection->comm($path, ['.id' => $dotId]);

            $this->logAction('disable_user', $customer, 'success',
                "Disabled {$customer->connection_type} user: {$customer->username}");

            return true;
        } catch (Exception $e) {
            Log::error('Failed to disable user: '.$e->getMessage());
            $this->logAction('disable_user', $customer, 'failed',
                'Failed to disable user: '.$e->getMessage(), null, null, $e->getMessage());

            return false;
        }
    }

    /**
     * Remove user
     */
    public function removeUser(Customer $customer): bool
    {
        if (! $customer->mikrotik_user_id) {
            return true;
        }

        return $this->removeUserByDotId(
            (string) $customer->mikrotik_user_id,
            $customer->connection_type ?? 'pppoe',
        );
    }

    public function removeUserByDotId(string $dotId, string $connectionType = 'pppoe'): bool
    {
        if (! $this->connect()) {
            return false;
        }

        try {
            $path = $connectionType === 'hotspot'
                ? '/ip/hotspot/user/remove'
                : '/ppp/secret/remove';

            $this->connection->comm($path, ['.id' => $dotId]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to remove user by id: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Disconnect active session
     */
    public function disconnectSession(Customer $customer): bool
    {
        if (! $this->connect()) {
            $this->logAction('disconnect_session', $customer, 'failed', 'Failed to connect to router');

            return false;
        }

        try {
            if ($customer->connection_type === 'hotspot') {
                $sessions = $this->connection->comm('/ip/hotspot/active/print', ['?user' => $customer->username]);
                foreach ($sessions as $session) {
                    if (! empty($session['.id'])) {
                        $this->connection->comm('/ip/hotspot/active/remove', ['.id' => $session['.id']]);
                    }
                }
            } else {
                $sessions = $this->connection->comm('/ppp/active/print', ['?name' => $customer->username]);
                foreach ($sessions as $session) {
                    if (! empty($session['.id'])) {
                        $this->connection->comm('/ppp/active/remove', ['.id' => $session['.id']]);
                    }
                }
            }

            $this->logAction('disconnect_session', $customer, 'success',
                "Disconnected active session for user: {$customer->username}");

            return true;
        } catch (Exception $e) {
            Log::error('Failed to disconnect session: '.$e->getMessage());
            $this->logAction('disconnect_session', $customer, 'failed',
                'Failed to disconnect session: '.$e->getMessage(), null, null, $e->getMessage());

            return false;
        }
    }

    /** Reset RouterOS' cumulative hotspot uptime and traffic counters at paid renewal. */
    public function resetHotspotUserCounters(Customer $customer): bool
    {
        if (! $customer->mikrotik_user_id) {
            return true;
        }
        if (! $this->connect()) {
            return false;
        }

        try {
            $this->connection->comm('/ip/hotspot/user/reset-counters', [
                '.id' => $customer->mikrotik_user_id,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to reset RouterOS hotspot counters', [
                'customer_id' => $customer->id,
                'router_id' => $this->router->id,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update user profile/speed
     */
    public function updateUserProfile(Customer $customer, Plan $plan): bool
    {
        if (! $this->connect() || ! $customer->mikrotik_user_id) {
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
            Log::error('Failed to update user profile: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Sync username / password / profile for an existing RouterOS user.
     * Creates the user when it is missing and a plan + plain password are provided.
     */
    public function syncUserCredentials(Customer $customer, ?string $plainPassword = null, ?Plan $plan = null): bool
    {
        if (! $this->connect()) {
            return false;
        }

        $dotId = $this->resolveMikrotikInternalUserId($customer)
            ?? ($customer->mikrotik_user_id ? (string) $customer->mikrotik_user_id : null);

        if (! $dotId) {
            if ($plan && $plainPassword) {
                return $customer->connection_type === 'hotspot'
                    ? $this->createHotspotUser($customer, $plan, $plainPassword)
                    : $this->createPppoeUser($customer, $plan, $plainPassword);
            }

            return false;
        }

        if ((string) ($customer->mikrotik_user_id ?? '') !== $dotId) {
            $customer->forceFill(['mikrotik_user_id' => $dotId])->saveQuietly();
        }

        try {
            $path = $customer->connection_type === 'hotspot'
                ? '/ip/hotspot/user/set'
                : '/ppp/secret/set';

            $params = [
                '.id' => $dotId,
                'name' => (string) $customer->username,
            ];

            if ($plainPassword !== null && $plainPassword !== '') {
                $params['password'] = $plainPassword;
            }

            if ($plan) {
                $params['profile'] = $plan->speed_profile ?? 'default';
            }

            $this->connection->comm($path, $params);
            $this->logAction('sync_user_credentials', $customer, 'success',
                "Synced {$customer->connection_type} credentials for {$customer->username}");

            return true;
        } catch (Exception $e) {
            Log::error('Failed to sync user credentials: '.$e->getMessage());
            $this->logAction('sync_user_credentials', $customer, 'failed',
                'Failed to sync credentials: '.$e->getMessage(), null, null, $e->getMessage());

            return false;
        }
    }

    /**
     * Get next available IP address for PPPoE
     */
    protected function getNextIpAddress(): string
    {
        $pools = \App\Models\IpPool::forIsp($this->router->isp_id)
            ->where('router_id', $this->router->id)->active()->get();
        if ($pools->isEmpty()) {
            throw new \RuntimeException('No active IP pool is configured for this router.');
        }

        $occupied = [];
        foreach ($this->connection->comm('/ppp/secret/print') ?: [] as $secret) {
            if (! empty($secret['remote-address'])) $occupied[$secret['remote-address']] = true;
        }
        foreach ($this->connection->comm('/ppp/active/print') ?: [] as $active) {
            if (! empty($active['address'])) $occupied[$active['address']] = true;
        }

        foreach ($pools as $pool) {
            foreach ($pool->ranges ?? [] as $range) {
                $start = isset($range['start']) ? ip2long($range['start']) : false;
                $end = isset($range['end']) ? ip2long($range['end']) : false;
                if ($start !== false && $end !== false && $end >= $start) {
                    $allocated = [];
                    foreach ($occupied as $address => $_) {
                        $numericAddress = ip2long($address);
                        if ($numericAddress !== false && $numericAddress >= $start && $numericAddress <= $end) $allocated[] = $numericAddress;
                    }
                    sort($allocated, SORT_NUMERIC);
                    $candidate = $start;
                    foreach ($allocated as $usedAddress) {
                        if ($usedAddress < $candidate) continue;
                        if ($usedAddress > $candidate) break;
                        $candidate++;
                    }
                    if ($candidate <= $end) return long2ip($candidate);
                } elseif (! empty($range['address']) && ! isset($occupied[$range['address']])) {
                    return $range['address'];
                }
            }
        }

        throw new \RuntimeException('The configured IP pool has no available addresses.');
    }

    /**
     * Get user statistics
     */
    public function getUserStats(Customer $customer): array
    {
        if (! $this->connect()) {
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
            Log::error('Failed to get user stats: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Whether RouterOS reports the user as disabled. Null when unknown (offline, missing row, etc.).
     */
    public function getUserDisabledState(Customer $customer): ?bool
    {
        $row = $this->getUserStats($customer);
        if ($row === []) {
            return null;
        }

        $d = $row['disabled'] ?? null;
        if ($d === true || $d === 'true' || $d === 'yes' || $d === '1') {
            return true;
        }
        if ($d === false || $d === 'false' || $d === 'no' || $d === '0') {
            return false;
        }

        return null;
    }

    /**
     * Get all active hotspot sessions
     */
    public function getActiveHotspotSessions(): array
    {
        if (! $this->connect()) {
            return [];
        }

        try {
            $response = $this->connection->comm('/ip/hotspot/active/print');

            return $response ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get active hotspot sessions: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Alias used by ActiveUsersController realtime panel.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHotspotActiveUsers(): array
    {
        return $this->getActiveHotspotSessions();
    }

    /**
     * Get active PPPoE sessions
     */
    public function getActivePppoeSessions(): array
    {
        if (! $this->connect()) {
            return [];
        }

        try {
            $response = $this->connection->comm('/ppp/active/print');

            return $response ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get active PPPoE sessions: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Alias used by ActiveUsersController realtime panel.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPPPoEActiveUsers(): array
    {
        return $this->getActivePppoeSessions();
    }

    /**
     * Disconnect a hotspot active session by RouterOS .id (or username fallback).
     */
    public function disconnectHotspotUser(string $userId): bool
    {
        if (! $this->connect()) {
            return false;
        }

        try {
            if (str_starts_with($userId, '*') || is_numeric($userId)) {
                $this->connection->comm('/ip/hotspot/active/remove', ['.id' => $userId]);
            } else {
                $this->connection->comm('/ip/hotspot/active/remove', ['user' => $userId]);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to disconnect hotspot user: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Disconnect a PPPoE active session by RouterOS .id (or name fallback).
     */
    public function disconnectPPPoEUser(string $userId): bool
    {
        if (! $this->connect()) {
            return false;
        }

        try {
            if (str_starts_with($userId, '*') || is_numeric($userId)) {
                $this->connection->comm('/ppp/active/remove', ['.id' => $userId]);
            } else {
                $this->connection->comm('/ppp/active/remove', ['name' => $userId]);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to disconnect PPPoE user: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Sync hotspot servers from RouterOS into the database for this router.
     *
     * @return array{success: bool, message?: string, total: int, created: int, updated: int}
     */
    public function syncHotspotServers(): array
    {
        if (! $this->connect()) {
            return [
                'success' => false,
                'message' => 'Could not connect to router',
                'total' => 0,
                'created' => 0,
                'updated' => 0,
            ];
        }

        try {
            $rows = $this->apiPrint('/ip/hotspot/print');
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                if (! is_array($row) || empty($row['name'])) {
                    continue;
                }

                $mapped = $this->mapHotspotServerImportRow($row);
                $existing = \App\Models\HotspotServer::query()
                    ->where('router_id', $this->router->id)
                    ->where('name', $mapped['name'])
                    ->first();

                \App\Models\HotspotServer::updateOrCreate(
                    [
                        'router_id' => $this->router->id,
                        'name' => $mapped['name'],
                    ],
                    [
                        'isp_id' => $this->router->isp_id,
                        'interface' => $mapped['interface'] ?? 'unknown',
                        'server_profile' => $mapped['server_profile'] ?? null,
                        'default_profile' => $mapped['default_profile'] ?? null,
                        'login_by' => $mapped['login_by'] ?? 'cookie',
                        'use_radius' => $mapped['use_radius'] ?? false,
                        'radius_accounting' => $mapped['radius_accounting'] ?? false,
                        'mac_cookie_timeout' => $mapped['mac_cookie_timeout'] ?? 300,
                        'idle_timeout' => $mapped['idle_timeout'] ?? 300,
                        'keepalive_timeout' => $mapped['keepalive_timeout'] ?? 60,
                        'status_autologout' => $mapped['status_autologout'] ?? false,
                        'allowed_users' => $mapped['allowed_users'] ?? 0,
                        'shared_users' => $mapped['shared_users'] ?? 0,
                        'mac_format' => $mapped['mac_format'] ?? null,
                        'mac_password' => $mapped['mac_password'] ?? null,
                        'radius_mac_format' => $mapped['radius_mac_format'] ?? null,
                        'radius_mac_password' => $mapped['radius_mac_password'] ?? null,
                        'trial_uptime' => $mapped['trial_uptime'] ?? 0,
                        'trial_uptime_reset' => $mapped['trial_uptime_reset'] ?? 0,
                        'trial_profile' => $mapped['trial_profile'] ?? null,
                        'trial_users' => $mapped['trial_users'] ?? 0,
                        'is_active' => ! ($mapped['disabled'] ?? false),
                    ]
                );

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }
            }

            return [
                'success' => true,
                'total' => $created + $updated,
                'created' => $created,
                'updated' => $updated,
            ];
        } catch (\Throwable $e) {
            Log::error('syncHotspotServers failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'total' => 0,
                'created' => 0,
                'updated' => 0,
            ];
        }
    }

    /**
     * Create an IP pool on RouterOS.
     *
     * @param  array{name: string, ranges: array<int, array{start?: string, end?: string, address?: string}>, comment?: string|null}  $config
     */
    public function createIpPool(array $config): bool
    {
        if (! $this->connect()) {
            return false;
        }

        try {
            $ranges = $this->formatIpPoolRangesForMikrotik($config['ranges'] ?? []);
            if ($ranges === '') {
                return false;
            }

            $params = [
                'name' => (string) ($config['name'] ?? 'pool'),
                'ranges' => $ranges,
            ];

            if (! empty($config['comment'])) {
                $params['comment'] = (string) $config['comment'];
            }

            $this->connection->comm('/ip/pool/add', $params);

            return true;
        } catch (\Throwable $e) {
            Log::error('createIpPool failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Sync IP pools from RouterOS into the database for this router.
     *
     * @return array{success: bool, message?: string, total: int, created: int, updated: int}
     */
    public function syncIpPools(): array
    {
        if (! $this->connect()) {
            return [
                'success' => false,
                'message' => 'Could not connect to router',
                'total' => 0,
                'created' => 0,
                'updated' => 0,
            ];
        }

        try {
            $rows = $this->apiPrint('/ip/pool/print');
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                if (! is_array($row) || empty($row['name'])) {
                    continue;
                }

                $ranges = $this->parseMikrotikIpPoolRanges((string) ($row['ranges'] ?? ''));
                $existing = \App\Models\IpPool::query()
                    ->where('router_id', $this->router->id)
                    ->where('name', $row['name'])
                    ->first();

                \App\Models\IpPool::updateOrCreate(
                    [
                        'router_id' => $this->router->id,
                        'name' => (string) $row['name'],
                    ],
                    [
                        'isp_id' => $this->router->isp_id,
                        'ranges' => $ranges,
                        'comment' => $row['comment'] ?? null,
                        'next_pool' => $row['next-pool'] ?? null,
                        'is_active' => ! in_array((string) ($row['disabled'] ?? 'false'), ['true', 'yes', '1'], true),
                        'disabled' => in_array((string) ($row['disabled'] ?? 'false'), ['true', 'yes', '1'], true),
                    ]
                );

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }
            }

            return [
                'success' => true,
                'total' => $created + $updated,
                'created' => $created,
                'updated' => $updated,
            ];
        } catch (\Throwable $e) {
            Log::error('syncIpPools failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'total' => 0,
                'created' => 0,
                'updated' => 0,
            ];
        }
    }

    /**
     * @param  array<int, array{start?: string, end?: string, address?: string}>  $ranges
     */
    protected function formatIpPoolRangesForMikrotik(array $ranges): string
    {
        $parts = [];
        foreach ($ranges as $range) {
            if (! empty($range['start']) && ! empty($range['end'])) {
                $parts[] = $range['start'].'-'.$range['end'];
            } elseif (! empty($range['address'])) {
                $parts[] = (string) $range['address'];
            }
        }

        return implode(',', $parts);
    }

    /**
     * @return array<int, array{start?: string, end?: string, address?: string}>
     */
    protected function parseMikrotikIpPoolRanges(string $ranges): array
    {
        $out = [];
        foreach (array_filter(array_map('trim', explode(',', $ranges))) as $part) {
            if (str_contains($part, '-')) {
                [$start, $end] = array_map('trim', explode('-', $part, 2));
                if (filter_var($start, FILTER_VALIDATE_IP) && filter_var($end, FILTER_VALIDATE_IP)) {
                    $out[] = ['start' => $start, 'end' => $end];
                    continue;
                }
            }
            if (filter_var($part, FILTER_VALIDATE_IP) || str_contains($part, '/')) {
                $out[] = ['address' => $part];
            }
        }

        return $out;
    }

    /**
     * Sync active sessions with database
     */
    public function syncActiveSessions(): int
    {
        if (! $this->connect()) {
            return 0;
        }

        $synced = 0;
        try {
            $activeSessions = $this->getActiveHotspotSessions();

            foreach ($activeSessions as $session) {
                $username = $session['user'] ?? null;
                if (! $username) {
                    continue;
                }

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
            Log::error('Failed to sync active sessions: '.$e->getMessage());
        }

        return $synced;
    }

    /**
     * Parse MikroTik uptime string to seconds
     */
    protected function parseUptime(string $uptime): int
    {
        $seconds = 0;

        if (preg_match('/(\d+)w/', $uptime, $matches)) {
            $seconds += (int) $matches[1] * 604800;
        }
        if (preg_match('/(\d+)d/', $uptime, $matches)) {
            $seconds += (int) $matches[1] * 86400;
        }
        if (preg_match('/(\d+)h/', $uptime, $matches)) {
            $seconds += (int) $matches[1] * 3600;
        }
        if (preg_match('/(\d+)m/', $uptime, $matches)) {
            $seconds += (int) $matches[1] * 60;
        }
        if (preg_match('/(\d+)s/', $uptime, $matches)) {
            $seconds += (int) $matches[1];
        }

        return $seconds;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Live telemetry / NOC operations
    // Used by PollRouterHealthJob and the routers detail page.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Pull /system/resource. Returns null on failure so callers can degrade gracefully.
     *
     * @return array{cpu_pct:?int,mem_pct:?int,uptime_seconds:?int,firmware:?string,board:?string}|null
     */
    public function getSystemResource(): ?array
    {
        if (! $this->connect()) {
            return null;
        }

        try {
            $rows = $this->apiPrint('/system/resource/print');
            $row = $rows[0] ?? null;
            if (! is_array($row)) {
                return null;
            }

            $totalMem = (int) ($row['total-memory'] ?? 0);
            $freeMem = (int) ($row['free-memory'] ?? 0);
            $memPct = $totalMem > 0 ? (int) round((($totalMem - $freeMem) / $totalMem) * 100) : null;

            return [
                'cpu_pct' => isset($row['cpu-load']) ? max(0, min(100, (int) $row['cpu-load'])) : null,
                'mem_pct' => $memPct,
                'uptime_seconds' => isset($row['uptime']) ? $this->parseUptime((string) $row['uptime']) : null,
                'firmware' => isset($row['version']) ? (string) $row['version'] : null,
                'board' => isset($row['board-name']) ? (string) $row['board-name'] : null,
            ];
        } catch (\Throwable $e) {
            Log::warning('MikroTik getSystemResource failed: '.$e->getMessage(), [
                'router_id' => $this->router->id ?? null,
            ]);

            return null;
        }
    }

    /**
     * Interface up/total count.
     *
     * @return array{up:int,total:int}|null
     */
    public function getInterfaceCounts(): ?array
    {
        if (! $this->connect()) {
            return null;
        }

        try {
            $rows = $this->apiPrint('/interface/print');
            if (! is_array($rows)) {
                return null;
            }
            $bool = fn ($v): bool => in_array((string) $v, ['true', 'yes', '1'], true);
            $total = count($rows);
            $up = 0;
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ($bool($row['running'] ?? false) && ! $bool($row['disabled'] ?? false)) {
                    $up++;
                }
            }

            return ['up' => $up, 'total' => $total];
        } catch (\Throwable $e) {
            Log::warning('MikroTik getInterfaceCounts failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Count active hotspot users on the router.
     */
    public function countActiveHotspotUsers(): ?int
    {
        if (! $this->connect()) {
            return null;
        }
        try {
            $rows = $this->apiPrint('/ip/hotspot/active/print');

            return is_array($rows) ? count($rows) : 0;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Count active PPP (PPPoE / L2TP / pptp) sessions.
     */
    public function countActivePppoeUsers(): ?int
    {
        if (! $this->connect()) {
            return null;
        }
        try {
            $rows = $this->apiPrint('/ppp/active/print');

            return is_array($rows) ? count($rows) : 0;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Run a ping from the router. Returns avg round-trip ms or null if unreachable.
     *
     * @return array{avg_ms:?int,sent:int,received:int,raw:array<int,array<string,mixed>>}
     */
    public function ping(string $address, int $count = 4): array
    {
        $result = [
            'avg_ms' => null,
            'sent' => $count,
            'received' => 0,
            'raw' => [],
        ];

        if (! $this->connect()) {
            return $result;
        }

        try {
            $params = ['address' => $address, 'count' => (string) $count];
            $rows = $this->commWithParams('/ping', $params);
            if (! is_array($rows)) {
                return $result;
            }
            $result['raw'] = $rows;
            $received = 0;
            $sumTime = 0.0;
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (isset($row['time'])) {
                    $received++;
                    $sumTime += $this->parseTimeMs((string) $row['time']);
                }
                if (isset($row['received'])) {
                    $received = max($received, (int) $row['received']);
                }
                if (isset($row['avg-rtt'])) {
                    $result['avg_ms'] = (int) round($this->parseTimeMs((string) $row['avg-rtt']));
                }
                if (isset($row['sent'])) {
                    $result['sent'] = (int) $row['sent'];
                }
            }
            $result['received'] = $received;
            if ($result['avg_ms'] === null && $received > 0) {
                $result['avg_ms'] = (int) round($sumTime / $received);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('MikroTik ping failed: '.$e->getMessage());

            return $result;
        }
    }

    /**
     * Reboot the router via /system/reboot. Non-blocking; the device drops the API session.
     */
    public function reboot(): bool
    {
        if (! $this->connect()) {
            return false;
        }
        try {
            $this->commWithParams('/system/reboot', []);

            return true;
        } catch (\Throwable $e) {
            // /system/reboot typically closes the API socket which surfaces here.
            // Treat that as a successful issue of the command — the router IS rebooting.
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'closed') || str_contains($msg, 'reset') || str_contains($msg, 'eof')) {
                return true;
            }
            Log::warning('MikroTik reboot failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Pull the router's `/export` text (textual config). Big-but-bounded.
     */
    public function exportConfig(): ?string
    {
        if (! $this->connect()) {
            return null;
        }
        try {
            $rows = $this->apiPrint('/export');
            if (! is_array($rows)) {
                return null;
            }
            $lines = [];
            foreach ($rows as $row) {
                if (is_array($row) && isset($row['ret'])) {
                    $lines[] = (string) $row['ret'];
                } elseif (is_string($row)) {
                    $lines[] = $row;
                }
            }

            return $lines ? implode("\n", $lines) : null;
        } catch (\Throwable $e) {
            Log::warning('MikroTik exportConfig failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Send an API command with parameters, normalising the two client flavours.
     *
     * @param  array<string,string>  $params
     * @return array<int,array<string,mixed>>
     */
    protected function commWithParams(string $path, array $params): array
    {
        if (! $this->connection) {
            return [];
        }

        if (method_exists($this->connection, 'comm')) {
            $response = $this->connection->comm($path, $params);

            return is_array($response) ? $response : [];
        }

        if (method_exists($this->connection, 'query')) {
            $query = new \RouterOS\Query($path);
            foreach ($params as $k => $v) {
                $query->equal($k, $v);
            }
            $response = $this->connection->query($query)->read();

            return is_array($response) ? $response : [];
        }

        return [];
    }

    /**
     * Convert MikroTik time strings ("12ms", "1m30s") to milliseconds.
     */
    protected function parseTimeMs(string $value): float
    {
        $value = trim($value);
        if ($value === '') {
            return 0.0;
        }
        if (preg_match('/^([\d.]+)ms$/i', $value, $m)) {
            return (float) $m[1];
        }
        if (preg_match('/^([\d.]+)us$/i', $value, $m)) {
            return ((float) $m[1]) / 1000.0;
        }
        if (preg_match('/^([\d.]+)s$/i', $value, $m)) {
            return ((float) $m[1]) * 1000.0;
        }

        return (float) $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSimpleQueues(): array
    {
        if (! $this->connect()) {
            return [];
        }

        try {
            $rows = $this->apiPrint('/queue/simple/print');
            $out = [];

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $maxLimit = (string) ($row['max-limit'] ?? '');
                $rate = (string) ($row['rate'] ?? '');
                $rateRatio = $this->estimateQueueRateRatio($maxLimit, $rate);
                $disabled = in_array((string) ($row['disabled'] ?? 'false'), ['true', 'yes', '1'], true);

                $out[] = [
                    'name' => (string) ($row['name'] ?? ''),
                    'target' => (string) ($row['target'] ?? ''),
                    'max_limit' => $maxLimit,
                    'rate' => $rate,
                    'rate_ratio' => $rateRatio,
                    'disabled' => $disabled,
                    'id' => $row['.id'] ?? null,
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('MikroTik listSimpleQueues failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    public function listProvisionedUsernames(): array
    {
        if (! $this->connect()) {
            return [];
        }

        $names = [];

        try {
            foreach ($this->apiPrint('/ip/hotspot/user/print') as $row) {
                if (is_array($row) && ! empty($row['name'])) {
                    $names[] = (string) $row['name'];
                }
            }

            foreach ($this->apiPrint('/ppp/secret/print') as $row) {
                if (is_array($row) && ! empty($row['name'])) {
                    $names[] = (string) $row['name'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('MikroTik listProvisionedUsernames failed: '.$e->getMessage());
        }

        return array_values(array_unique($names));
    }

    public function removeSimpleQueueByName(string $queueName): bool
    {
        if (! $this->connect()) {
            return false;
        }

        try {
            $rows = $this->connection->comm('/queue/simple/print', ['?name' => $queueName]);
            foreach ($rows ?? [] as $row) {
                if (! empty($row['.id'])) {
                    $this->connection->comm('/queue/simple/remove', ['.id' => $row['.id']]);
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('removeSimpleQueueByName failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function createQueueSimple(array $config): bool
    {
        if (! $this->connect()) {
            return false;
        }

        try {
            $params = $this->normalizeQueueConfig($config, includeTarget: true);

            // Upsert by name so redeploy is idempotent.
            $existing = $this->connection->comm('/queue/simple/print', ['?name' => $params['name']]);
            if (! empty($existing[0]['.id'])) {
                $params['.id'] = $existing[0]['.id'];
                $this->connection->comm('/queue/simple/set', $params);
            } else {
                $this->connection->comm('/queue/simple/add', $params);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('createQueueSimple failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function createQueueTree(array $config): bool
    {
        if (! $this->connect()) {
            return false;
        }

        try {
            $params = $this->normalizeQueueConfig($config, includeTarget: false);
            $params['parent'] = (string) ($config['parent'] ?? 'global');

            $existing = $this->connection->comm('/queue/tree/print', ['?name' => $params['name']]);
            if (! empty($existing[0]['.id'])) {
                $params['.id'] = $existing[0]['.id'];
                $this->connection->comm('/queue/tree/set', $params);
            } else {
                $this->connection->comm('/queue/tree/add', $params);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('createQueueTree failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Normalize bandwidth deploy config into RouterOS queue params.
     * Accepts max-limit style keys or download_limit/upload_limit (Kbps).
     *
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    protected function normalizeQueueConfig(array $config, bool $includeTarget = true): array
    {
        $name = (string) ($config['name'] ?? 'queue');
        $maxLimit = (string) ($config['max-limit'] ?? $config['max_limit'] ?? '');

        if ($maxLimit === '' && (isset($config['download_limit']) || isset($config['upload_limit']))) {
            $dl = (int) ($config['download_limit'] ?? 0);
            $ul = (int) ($config['upload_limit'] ?? 0);
            $maxLimit = $dl.'k/'.$ul.'k';
        }

        if ($maxLimit === '') {
            $maxLimit = '0/0';
        }

        $params = [
            'name' => $name,
            'max-limit' => $maxLimit,
        ];

        if ($includeTarget) {
            $params['target'] = (string) ($config['target'] ?? '');
        }

        if (! empty($config['priority'])) {
            $params['priority'] = (string) $config['priority'];
        }

        if (! empty($config['comment'])) {
            $params['comment'] = (string) $config['comment'];
        }

        $burstLimit = $config['burst-limit'] ?? $config['burst_limit'] ?? null;
        if ($burstLimit) {
            $params['burst-limit'] = (string) $burstLimit;
        }

        $burstThreshold = $config['burst-threshold'] ?? $config['burst_threshold'] ?? null;
        if ($burstThreshold) {
            $params['burst-threshold'] = (string) $burstThreshold;
        }

        $burstTime = $config['burst-time'] ?? $config['burst_time'] ?? null;
        if ($burstTime) {
            $params['burst-time'] = (string) $burstTime;
        }

        return $params;
    }

    protected function estimateQueueRateRatio(string $maxLimit, string $rate): float
    {
        if ($maxLimit === '' || $rate === '' || $maxLimit === '0/0') {
            return 0.0;
        }

        $maxParts = explode('/', $maxLimit);
        $rateParts = explode('/', $rate);

        $ratios = [];
        foreach ([0, 1] as $idx) {
            $maxBps = $this->parseRateToBps($maxParts[$idx] ?? '0');
            $rateBps = $this->parseRateToBps($rateParts[$idx] ?? '0');
            if ($maxBps > 0 && $rateBps > 0) {
                $ratios[] = $rateBps / $maxBps;
            }
        }

        return $ratios ? max($ratios) : 0.0;
    }

    protected function parseRateToBps(string $value): float
    {
        $value = trim(strtolower($value));
        if ($value === '' || $value === '0') {
            return 0.0;
        }

        if (preg_match('/^([\d.]+)([kmg]?)(bps)?$/', $value, $m)) {
            $num = (float) $m[1];
            $unit = $m[2] ?? '';

            return match ($unit) {
                'g' => $num * 1_000_000_000,
                'm' => $num * 1_000_000,
                'k' => $num * 1_000,
                default => $num,
            };
        }

        return (float) $value;
    }
}
