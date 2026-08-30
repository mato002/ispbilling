<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\HotspotSession;
use App\Models\ISP;
use App\Models\Router;
use App\Support\Provisioning\ProvisioningSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProvisioningSnapshotService
{
    public function cacheKey(int $ispId): string
    {
        return "provisioning.snapshot.{$ispId}";
    }

    public function metaKey(int $ispId): string
    {
        return "provisioning.snapshot.meta.{$ispId}";
    }

    /**
     * @return array{refreshed_at: string|null, isp_id: int, isp_name: string, orphan_router_usernames: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    public function getPayload(int $ispId): array
    {
        $cached = $this->cache()->get($this->cacheKey($ispId));

        if (is_array($cached) && isset($cached['rows'])) {
            return $cached;
        }

        return $this->refresh($ispId);
    }

    /**
     * @return array<int, ProvisioningSnapshot>
     */
    public function getSnapshots(int $ispId): array
    {
        $payload = $this->getPayload($ispId);

        return array_map(
            fn (array $row) => ProvisioningSnapshot::fromArray($row),
            $payload['rows'] ?? [],
        );
    }

    /**
     * @return array{refreshed_at: string|null, isp_id: int, isp_name: string, orphan_router_usernames: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    public function refresh(int $ispId): array
    {
        $isp = ISP::query()->findOrFail($ispId);
        $now = now()->toIso8601String();

        $routerSessionMap = $this->collectLiveSessionMacs($ispId);
        $routerQueueMap = $this->collectRouterQueues($ispId);
        $routerUsernames = $this->collectRouterUsernames($ispId);
        $dbUsernames = $this->collectDbUsernames($ispId);

        $orphanUsernames = array_values(array_diff($routerUsernames, $dbUsernames));

        $activeSessions = HotspotSession::query()
            ->where('isp_id', $ispId)
            ->where('status', 'active')
            ->get(['customer_id', 'mac_address', 'username', 'router_id'])
            ->groupBy('customer_id');

        $rows = [];

        Customer::query()
            ->forIsp($ispId)
            ->whereNotNull('router_id')
            ->with([
                'router:id,name,isp_id',
                'inventoryItem:id,mac_address,name',
                'devices' => fn ($q) => $q->where('is_active', true)->where('is_blocked', false),
                'subscriptions' => fn ($q) => $q->with('plan:id,name,speed_profile,download_speed_kbps,upload_speed_kbps')
                    ->where('status', 'active')
                    ->where('end_date', '>=', now()->startOfDay())
                    ->orderByDesc('end_date'),
            ])
            ->select([
                'id', 'isp_id', 'router_id', 'full_name', 'username', 'connection_type',
                'status', 'inventory_item_id', 'mikrotik_user_id',
            ])
            ->orderBy('full_name')
            ->chunkById(200, function ($customers) use (
                $isp,
                $activeSessions,
                $routerSessionMap,
                $routerQueueMap,
                $orphanUsernames,
                &$rows,
            ) {
                foreach ($customers as $customer) {
                    $rows[] = $this->buildRow(
                        $customer,
                        $isp->name,
                        $activeSessions,
                        $routerSessionMap,
                        $routerQueueMap,
                        $orphanUsernames,
                    )->toArray();
                }
            });

        $payload = [
            'refreshed_at' => $now,
            'isp_id' => $ispId,
            'isp_name' => $isp->name,
            'orphan_router_usernames' => $orphanUsernames,
            'rows' => $rows,
        ];

        $ttl = config('provisioning.snapshot_ttl_seconds', 120);
        $this->cache()->put($this->cacheKey($ispId), $payload, $ttl);
        $this->cache()->put($this->metaKey($ispId), ['refreshed_at' => $now], $ttl);

        return $payload;
    }

    public function refreshedAt(int $ispId): ?string
    {
        $meta = $this->cache()->get($this->metaKey($ispId));

        return is_array($meta) ? ($meta['refreshed_at'] ?? null) : null;
    }

    /**
     * @param  Collection<int, Collection<int, HotspotSession>>  $activeSessions
     * @param  array<string, array<int, string>>  $routerSessionMap
     * @param  array<string, array<string, mixed>>  $routerQueueMap
     * @param  array<int, string>  $orphanUsernames
     */
    protected function buildRow(
        Customer $customer,
        string $ispName,
        Collection $activeSessions,
        array $routerSessionMap,
        array $routerQueueMap,
        array $orphanUsernames,
    ): ProvisioningSnapshot {
        $subscription = $customer->subscriptions->first();
        $plan = $subscription?->plan;

        $hardwareMac = $this->normalizeMac(
            $customer->inventoryItem?->mac_address
            ?? $customer->devices->first()?->mac_address
        );

        $sessionMac = $this->resolveSessionMac($customer, $activeSessions, $routerSessionMap);

        $queueRateLimit = $this->formatQueueRateLimit($plan);
        $queueKey = $customer->username ? strtolower("queue_{$customer->username}") : null;
        $queueInfo = $queueKey ? ($routerQueueMap[$queueKey] ?? null) : null;
        $queueState = $this->resolveQueueState($queueInfo, $customer, $plan);

        $macVerified = $this->isMacVerified($hardwareMac, $sessionMac, $customer);
        $macMismatch = $this->isMacMismatch($hardwareMac, $sessionMac);
        $throttled = $queueState === 'throttled'
            || $customer->status === 'suspended'
            || ($subscription?->last_cap_enforced_at !== null && $subscription?->data_exhausted_at !== null);

        $orphanProfile = ($customer->mikrotik_user_id && ! $subscription && $customer->status !== 'active')
            || ($customer->username && in_array($customer->username, $orphanUsernames, true));

        return new ProvisioningSnapshot(
            customerId: $customer->id,
            customerName: $customer->full_name,
            ispTenantName: $ispName,
            planName: $plan?->name,
            hardwareMac: $hardwareMac,
            sessionMac: $sessionMac,
            queueRateLimit: $queueRateLimit,
            queueState: $queueState,
            routerId: $customer->router_id,
            routerName: $customer->router?->name,
            connectionType: $customer->connection_type ?? 'hotspot',
            customerStatus: $customer->status,
            statusFlags: [
                'mac_verified' => $macVerified,
                'mac_mismatch' => $macMismatch,
                'throttled' => $throttled,
                'orphan_router_profile' => $orphanProfile,
            ],
        );
    }

    /**
     * @param  Collection<int, Collection<int, HotspotSession>>  $activeSessions
     * @param  array<string, array<int, string>>  $routerSessionMap
     */
    protected function resolveSessionMac(
        Customer $customer,
        Collection $activeSessions,
        array $routerSessionMap,
    ): ?string {
        $dbSession = $activeSessions->get($customer->id)?->first();
        if ($dbSession?->mac_address) {
            return $this->normalizeMac($dbSession->mac_address);
        }

        if (! $customer->username || ! $customer->router_id) {
            return null;
        }

        $routerKey = "{$customer->router_id}:{$customer->username}";
        $liveMac = $routerSessionMap[$routerKey] ?? null;

        return $liveMac ? $this->normalizeMac($liveMac) : null;
    }

    protected function isMacVerified(?string $hardwareMac, ?string $sessionMac, Customer $customer): bool
    {
        if (! $hardwareMac) {
            return false;
        }

        if ($sessionMac) {
            return $hardwareMac === $sessionMac;
        }

        return $customer->status === 'active' && $customer->inventory_item_id !== null;
    }

    protected function isMacMismatch(?string $hardwareMac, ?string $sessionMac): bool
    {
        if (! $hardwareMac || ! $sessionMac) {
            return false;
        }

        return $hardwareMac !== $sessionMac;
    }

    protected function formatQueueRateLimit(?object $plan): ?string
    {
        if (! $plan) {
            return null;
        }

        if ($plan->download_speed_kbps || $plan->upload_speed_kbps) {
            $down = $this->formatSpeedLabel((int) ($plan->download_speed_kbps ?? 0));
            $up = $this->formatSpeedLabel((int) ($plan->upload_speed_kbps ?? 0));

            return "{$down}/{$up}";
        }

        return $plan->speed_profile ?: null;
    }

    protected function formatSpeedLabel(int $kbps): string
    {
        if ($kbps <= 0) {
            return '∞';
        }

        if ($kbps >= 1000 && $kbps % 1000 === 0) {
            return ($kbps / 1000).'M';
        }

        if ($kbps >= 1000) {
            return round($kbps / 1000, 1).'M';
        }

        return $kbps.'K';
    }

    /**
     * @param  array<string, mixed>|null  $queueInfo
     */
    protected function resolveQueueState(?array $queueInfo, Customer $customer, ?object $plan): string
    {
        if ($customer->status === 'suspended') {
            return 'throttled';
        }

        if (! $queueInfo) {
            return 'normal';
        }

        $threshold = (float) config('provisioning.queue_saturation_threshold', 0.90);
        $rate = (float) ($queueInfo['rate_ratio'] ?? 0);

        if ($rate >= 1.0) {
            return 'throttled';
        }

        if ($rate >= $threshold) {
            return 'near_limit';
        }

        if (($queueInfo['disabled'] ?? false) === true) {
            return 'throttled';
        }

        return 'normal';
    }

    /**
     * @return array<string, string> router_id:username => mac
     */
    protected function collectLiveSessionMacs(int $ispId): array
    {
        $map = [];

        Router::query()
            ->forIsp($ispId)
            ->where('is_active', true)
            ->where('reachability_status', '!=', 'broken')
            ->get(['id'])
            ->each(function (Router $router) use (&$map) {
                try {
                    $service = new MikroTikService($router);

                    foreach ($service->getActiveHotspotSessions() as $session) {
                        $user = $session['user'] ?? null;
                        $mac = $session['mac-address'] ?? null;
                        if ($user && $mac) {
                            $map["{$router->id}:{$user}"] = $mac;
                        }
                    }

                    foreach ($service->getActivePppoeSessions() as $session) {
                        $user = $session['name'] ?? null;
                        $mac = $session['caller-id'] ?? null;
                        if ($user && $mac) {
                            $map["{$router->id}:{$user}"] = $mac;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Provisioning snapshot: router session poll failed', [
                        'router_id' => $router->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return $map;
    }

    /**
     * @return array<string, array<string, mixed>> keyed by lowercase queue name
     */
    protected function collectRouterQueues(int $ispId): array
    {
        $map = [];

        Router::query()
            ->forIsp($ispId)
            ->where('is_active', true)
            ->where('reachability_status', '!=', 'broken')
            ->get(['id'])
            ->each(function (Router $router) use (&$map) {
                try {
                    $service = new MikroTikService($router);
                    foreach ($service->listSimpleQueues() as $queue) {
                        $name = strtolower((string) ($queue['name'] ?? ''));
                        if ($name !== '') {
                            $map[$name] = $queue;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Provisioning snapshot: router queue poll failed', [
                        'router_id' => $router->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return $map;
    }

    /**
     * @return array<int, string>
     */
    protected function collectRouterUsernames(int $ispId): array
    {
        $names = [];

        Router::query()
            ->forIsp($ispId)
            ->where('is_active', true)
            ->where('reachability_status', '!=', 'broken')
            ->get(['id'])
            ->each(function (Router $router) use (&$names) {
                try {
                    $service = new MikroTikService($router);
                    $names = array_merge($names, $service->listProvisionedUsernames());
                } catch (\Throwable $e) {
                    Log::warning('Provisioning snapshot: router user poll failed', [
                        'router_id' => $router->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return array_values(array_unique(array_filter($names)));
    }

    /**
     * @return array<int, string>
     */
    protected function collectDbUsernames(int $ispId): array
    {
        return Customer::query()
            ->forIsp($ispId)
            ->whereNotNull('username')
            ->pluck('username')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeMac(?string $mac): ?string
    {
        if (! $mac) {
            return null;
        }

        $value = strtolower(preg_replace('/[^a-f0-9]/i', '', $mac) ?? '');

        if (strlen($value) !== 12) {
            return strtolower(trim($mac));
        }

        return implode(':', str_split($value, 2));
    }

    protected function cache(): \Illuminate\Contracts\Cache\Repository
    {
        $store = config('provisioning.cache_store');

        try {
            return Cache::store($store);
        } catch (\Throwable) {
            return Cache::store(config('cache.default'));
        }
    }
}
