<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\IpPool;
use App\Models\Router;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UserConnectionService
{
    protected $router;

    protected $mikrotikService;

    public function __construct(?Router $router = null)
    {
        $this->router = $router;
        if ($router) {
            $this->mikrotikService = new MikroTikService($router);
        }
    }

    /**
     * Create hotspot user on router
     */
    public function createHotspotUser(Customer $customer, Subscription $subscription): bool
    {
        try {
            if (! $customer->router || ! $customer->hotspotServer) {
                Log::error("Customer {$customer->id} missing router or hotspot server");

                return false;
            }

            $service = new MikroTikService($customer->router);
            $url = $customer->router->getApiUrl();

            // Prepare user data
            $userData = [
                '/ip/hotspot/user/add' => '',
                'name' => $customer->username,
                'password' => $customer->password ?? $customer->username,
                'server' => $customer->hotspotServer->name,
                'profile' => $subscription->plan->name ?? 'default',
            ];

            // Add IP pool if specified
            if ($subscription->plan->ip_pool_id) {
                $ipPool = IpPool::find($subscription->plan->ip_pool_id);
                if ($ipPool) {
                    $userData['address'] = $this->getNextAvailableIp($ipPool);
                }
            }

            $response = Http::timeout(10)
                ->withBasicAuth($customer->router->username, $customer->router->password)
                ->post($url, $userData);

            if ($response->successful()) {
                Log::info("Created hotspot user: {$customer->username}");

                return true;
            }

            Log::error("Failed to create hotspot user {$customer->username}: ".$response->body());

            return false;

        } catch (\Exception $e) {
            Log::error("Error creating hotspot user {$customer->username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Create PPPoE user on router
     */
    public function createPPPoEUser(Customer $customer, Subscription $subscription): bool
    {
        try {
            if (! $customer->router) return false;
            return Cache::lock('router-pppoe-ip-allocation:'.$customer->router_id, 60)->block(15, function () use ($customer, $subscription) {
            if (! $customer->router) {
                Log::error("Customer {$customer->id} missing router");

                return false;
            }

            $service = new MikroTikService($customer->router);
            $url = $customer->router->getApiUrl();

            // Prepare PPP secret data
            $secretData = [
                '/ppp/secret/add' => '',
                'name' => $customer->username,
                'password' => $customer->password ?? $customer->username,
                'service' => 'pppoe',
                'profile' => $subscription->plan->name ?? 'default',
                'comment' => "Customer ID: {$customer->id}",
            ];

            // Add IP pool if specified
            if ($subscription->plan->ip_pool_id) {
                $ipPool = IpPool::find($subscription->plan->ip_pool_id);
                if ($ipPool) {
                    $secretData['local-address'] = $ipPool->gateway_address;
                    $secretData['remote-address'] = $this->getNextAvailableIp($ipPool);
                }
            }

            $response = Http::timeout(10)
                ->withBasicAuth($customer->router->username, $customer->router->password)
                ->post($url, $secretData);

            if ($response->successful()) {
                Log::info("Created PPPoE user: {$customer->username}");

                return true;
            }

            Log::error("Failed to create PPPoE user {$customer->username}: ".$response->body());

            return false;

            });
        } catch (\Exception $e) {
            Log::error("Error creating PPPoE user {$customer->username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Disconnect user from router
     */
    public function disconnectUser(Customer $customer): bool
    {
        try {
            if (! $customer->router) {
                return false;
            }

            $service = new MikroTikService($customer->router);
            $url = $customer->router->getApiUrl();

            $success = true;

            // Disconnect from hotspot
            if ($customer->hotspotServer) {
                $response = Http::timeout(10)
                    ->withBasicAuth($customer->router->username, $customer->router->password)
                    ->post($url, [
                        '/ip/hotspot/user/remove' => '',
                        '.id' => $customer->username,
                    ]);

                if (! $response->successful()) {
                    Log::error("Failed to remove hotspot user {$customer->username}: ".$response->body());
                    $success = false;
                }
            }

            // Disconnect from PPPoE
            $response = Http::timeout(10)
                ->withBasicAuth($customer->router->username, $customer->router->password)
                ->post($url, [
                    '/ppp/secret/remove' => '',
                    '.id' => $customer->username,
                ]);

            if (! $response->successful()) {
                Log::error("Failed to remove PPPoE user {$customer->username}: ".$response->body());
                $success = false;
            }

            if ($success) {
                Log::info("Disconnected user: {$customer->username}");
            }

            return $success;

        } catch (\Exception $e) {
            Log::error("Error disconnecting user {$customer->username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Suspend user on router
     */
    public function suspendUser(Customer $customer): bool
    {
        try {
            if (! $customer->router) {
                return false;
            }

            $service = new MikroTikService($customer->router);
            $url = $customer->router->getApiUrl();

            $success = true;

            // Suspend hotspot user
            if ($customer->hotspotServer) {
                $response = Http::timeout(10)
                    ->withBasicAuth($customer->router->username, $customer->router->password)
                    ->post($url, [
                        '/ip/hotspot/user/set' => '',
                        '.id' => $customer->username,
                        'disabled' => 'true',
                    ]);

                if (! $response->successful()) {
                    Log::error("Failed to suspend hotspot user {$customer->username}: ".$response->body());
                    $success = false;
                }
            }

            // Suspend PPPoE user
            $response = Http::timeout(10)
                ->withBasicAuth($customer->router->username, $customer->router->password)
                ->post($url, [
                    '/ppp/secret/set' => '',
                    '.id' => $customer->username,
                    'disabled' => 'true',
                ]);

            if (! $response->successful()) {
                Log::error("Failed to suspend PPPoE user {$customer->username}: ".$response->body());
                $success = false;
            }

            if ($success) {
                Log::info("Suspended user: {$customer->username}");
            }

            return $success;

        } catch (\Exception $e) {
            Log::error("Error suspending user {$customer->username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Reconnect user on router
     */
    public function reconnectUser(Customer $customer, Subscription $subscription): bool
    {
        try {
            if (! $customer->router) {
                return false;
            }

            $service = new MikroTikService($customer->router);
            $url = $customer->router->getApiUrl();

            $success = true;

            // Reconnect hotspot user
            if ($customer->hotspotServer) {
                $response = Http::timeout(10)
                    ->withBasicAuth($customer->router->username, $customer->router->password)
                    ->post($url, [
                        '/ip/hotspot/user/set' => '',
                        '.id' => $customer->username,
                        'disabled' => 'false',
                    ]);

                if (! $response->successful()) {
                    Log::error("Failed to reconnect hotspot user {$customer->username}: ".$response->body());
                    $success = false;
                }
            }

            // Reconnect PPPoE user
            $response = Http::timeout(10)
                ->withBasicAuth($customer->router->username, $customer->router->password)
                ->post($url, [
                    '/ppp/secret/set' => '',
                    '.id' => $customer->username,
                    'disabled' => 'false',
                ]);

            if (! $response->successful()) {
                Log::error("Failed to reconnect PPPoE user {$customer->username}: ".$response->body());
                $success = false;
            }

            if ($success) {
                Log::info("Reconnected user: {$customer->username}");
            }

            return $success;

        } catch (\Exception $e) {
            Log::error("Error reconnecting user {$customer->username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Get next available IP from pool
     */
    private function getNextAvailableIp(IpPool $ipPool): ?string
    {
        $used = [];
        $router = $ipPool->router;
        if ($router) {
            $service = new MikroTikService($router);
            if (! $service->connect()) throw new \RuntimeException('Could not verify addresses on the router.');
            foreach ($service->getConnection()->comm('/ppp/secret/print') ?: [] as $secret) {
                if (! empty($secret['remote-address'])) $used[$secret['remote-address']] = true;
            }
            foreach ($service->getConnection()->comm('/ppp/active/print') ?: [] as $active) {
                if (! empty($active['address'])) $used[$active['address']] = true;
            }
        }
        foreach ($ipPool->ranges ?? [] as $range) {
            $start = isset($range['start']) ? ip2long($range['start']) : false;
            $end = isset($range['end']) ? ip2long($range['end']) : false;
            if ($start !== false && $end !== false && $end >= $start) {
                for ($candidate = $start; $candidate <= $end; $candidate++) {
                    $address = long2ip($candidate);
                    if (! isset($used[$address])) return $address;
                }
            } elseif (! empty($range['address']) && ! isset($used[$range['address']])) {
                return $range['address'];
            }
        }
        throw new \RuntimeException('The configured IP pool has no available addresses.');
    }

    /**
     * Apply bandwidth limits to user
     */
    public function applyBandwidthLimits(Customer $customer, Subscription $subscription): bool
    {
        try {
            if (! $customer->router || ! $subscription->plan->bandwidth_profile_id) {
                return false;
            }

            $bandwidthProfile = $subscription->plan->bandwidthProfile;
            if (! $bandwidthProfile) {
                return false;
            }

            $service = new MikroTikService($customer->router);
            $url = $customer->router->getApiUrl();

            // Create queue simple for user
            $queueData = [
                '/queue/simple/add' => '',
                'name' => "queue_{$customer->username}",
                'target' => $customer->username,
                'max-limit' => ($bandwidthProfile->download_limit / 1000).'k/'.($bandwidthProfile->upload_limit / 1000).'k',
                'priority' => $bandwidthProfile->priority,
                'comment' => "Customer ID: {$customer->id}",
            ];

            // Add burst settings if configured
            if ($bandwidthProfile->download_burst_limit && $bandwidthProfile->upload_burst_limit) {
                $queueData['burst-limit'] = ($bandwidthProfile->download_burst_limit / 1000).'k/'.($bandwidthProfile->upload_burst_limit / 1000).'k';
                $queueData['burst-threshold'] = ($bandwidthProfile->download_burst_threshold / 1000).'k/'.($bandwidthProfile->upload_burst_threshold / 1000).'k';
                $queueData['burst-time'] = ($bandwidthProfile->download_burst_time ?? 10).'s/'.($bandwidthProfile->upload_burst_time ?? 10).'s';
            }

            $response = Http::timeout(10)
                ->withBasicAuth($customer->router->username, $customer->router->password)
                ->post($url, $queueData);

            if ($response->successful()) {
                Log::info("Applied bandwidth limits for user: {$customer->username}");

                return true;
            }

            Log::error("Failed to apply bandwidth limits for {$customer->username}: ".$response->body());

            return false;

        } catch (\Exception $e) {
            Log::error("Error applying bandwidth limits for {$customer->username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Remove bandwidth limits from user
     */
    public function removeBandwidthLimits(Customer $customer): bool
    {
        try {
            if (! $customer->router) {
                return false;
            }

            $service = new MikroTikService($customer->router);
            $url = $customer->router->getApiUrl();

            $response = Http::timeout(10)
                ->withBasicAuth($customer->router->username, $customer->router->password)
                ->post($url, [
                    '/queue/simple/remove' => '',
                    '.id' => "queue_{$customer->username}",
                ]);

            if ($response->successful()) {
                Log::info("Removed bandwidth limits for user: {$customer->username}");

                return true;
            }

            Log::error("Failed to remove bandwidth limits for {$customer->username}: ".$response->body());

            return false;

        } catch (\Exception $e) {
            Log::error("Error removing bandwidth limits for {$customer->username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Get user connection status from router
     */
    public function getUserConnectionStatus(Customer $customer): array
    {
        try {
            if (! $customer->router) {
                return [
                    'hotspot' => 'unknown',
                    'pppoe' => 'unknown',
                    'overall' => 'unknown',
                    'message' => 'No router configured for this customer.',
                ];
            }

            $url = $customer->router->getApiUrl();

            // Check hotspot user status
            $hotspotResponse = Http::timeout(10)
                ->withBasicAuth($customer->router->username, $customer->router->password)
                ->post($url, [
                    '/ip/hotspot/user/print' => '',
                    '?name' => $customer->username,
                ]);

            // Check PPPoE user status
            $pppoeResponse = Http::timeout(10)
                ->withBasicAuth($customer->router->username, $customer->router->password)
                ->post($url, [
                    '/ppp/secret/print' => '',
                    '?name' => $customer->username,
                ]);

            $status = [
                'hotspot' => 'not_found',
                'pppoe' => 'not_found',
                'overall' => 'disconnected',
            ];

            if ($hotspotResponse->successful()) {
                $hotspotData = $this->parseUserResponse($hotspotResponse->body());
                if (! empty($hotspotData)) {
                    $status['hotspot'] = $hotspotData[0]['disabled'] === 'true' ? 'disabled' : 'active';
                }
            }

            if ($pppoeResponse->successful()) {
                $pppoeData = $this->parseUserResponse($pppoeResponse->body());
                if (! empty($pppoeData)) {
                    $status['pppoe'] = $pppoeData[0]['disabled'] === 'true' ? 'disabled' : 'active';
                }
            }

            // Determine overall status
            if ($status['hotspot'] === 'active' || $status['pppoe'] === 'active') {
                $status['overall'] = 'connected';
            } elseif ($status['hotspot'] === 'disabled' || $status['pppoe'] === 'disabled') {
                $status['overall'] = 'suspended';
            }

            return $status;

        } catch (\Exception $e) {
            Log::error("Error getting connection status for {$customer->username}: ".$e->getMessage());

            return [
                'hotspot' => 'unknown',
                'pppoe' => 'unknown',
                'overall' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse MikroTik user response
     */
    private function parseUserResponse($response): array
    {
        $users = [];
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            if (empty($line) || strpos($line, '!done') === 0) {
                continue;
            }

            $data = [];
            $parts = explode(' ', $line);

            foreach ($parts as $part) {
                if (strpos($part, '=') !== false) {
                    [$key, $value] = explode('=', $part, 2);
                    $data[$key] = $value;
                }
            }

            if (! empty($data)) {
                $users[] = $data;
            }
        }

        return $users;
    }
}
