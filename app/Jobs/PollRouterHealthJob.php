<?php

namespace App\Jobs;

use App\Models\Router;
use App\Models\RouterMetric;
use App\Models\SystemAnnouncement;
use App\Services\MikroTikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Pulls real telemetry from a MikroTik router, persists a row into
 * `router_metrics` (timeseries), and updates the cached `last_*` columns on
 * the router itself so the NOC view can render without joining.
 *
 * Idempotent and safe to run on the same router every minute.
 */
class PollRouterHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // do not retry; next tick will pick it up

    public int $timeout = 25;

    public function __construct(public int $routerId) {}

    public function uniqueId(): string
    {
        return 'router-poll-'.$this->routerId;
    }

    public function handle(): void
    {
        /** @var Router|null $router */
        $router = Router::find($this->routerId);
        if (! $router || ! $router->is_active) {
            return;
        }

        if ($router->reachability_status === 'broken') {
            return;
        }

        $prevFailures = (int) ($router->poll_failure_count ?? 0);

        $start = microtime(true);
        $service = new MikroTikService($router);
        $reachability = 'offline';
        $error = null;
        $latencyMs = null;
        $resource = null;
        $ifaces = null;
        $hotspot = null;
        $pppoe = null;

        try {
            $connected = $service->connect();
            $latencyMs = (int) round((microtime(true) - $start) * 1000);

            if (! $connected) {
                $reachability = 'offline';
                $error = 'Could not open RouterOS API session';
            } else {
                $resource = $service->getSystemResource();
                $ifaces = $service->getInterfaceCounts();
                $hotspot = $service->countActiveHotspotUsers();
                $pppoe = $service->countActivePppoeUsers();

                if ($resource === null && $ifaces === null) {
                    $reachability = 'degraded';
                    $error = 'Connected but no usable telemetry returned';
                } else {
                    $reachability = $latencyMs !== null && $latencyMs > 1200 ? 'degraded' : 'online';
                }
            }
        } catch (\Throwable $e) {
            $reachability = 'offline';
            $error = $e->getMessage();
            Log::warning('PollRouterHealthJob: '.$e->getMessage(), [
                'router_id' => $router->id,
            ]);
        }

        $cpu = $resource['cpu_pct'] ?? null;
        $mem = $resource['mem_pct'] ?? null;
        $health = $this->computeHealth($reachability, $latencyMs, $cpu, $mem);

        $pollSucceeded = ($reachability === 'online' || $reachability === 'degraded');
        $failureCount = $pollSucceeded ? 0 : min(1000, $prevFailures + 1);
        $storedReachability = $reachability;
        if ($failureCount >= 10) {
            $storedReachability = 'broken';
            if (Cache::add('router-circuit-opened:'.$router->id, 1, now()->addDays(14))) {
                try {
                    SystemAnnouncement::create([
                        'title' => 'Router circuit open: '.$router->name,
                        'body' => "NAS {$router->name} ({$router->ip_address}, ID {$router->id}) exceeded consecutive poll failures. Reconcile/health jobs will skip this device until a Super Admin clears the circuit from Admin → Network.",
                        'severity' => SystemAnnouncement::SEVERITY_CRITICAL,
                        'is_active' => true,
                        'starts_at' => now(),
                        'ends_at' => now()->addDays(30),
                        'created_by_user_id' => null,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('PollRouterHealthJob: failed to persist circuit announcement', [
                        'router_id' => $router->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                Log::critical('PollRouterHealthJob: router circuit breaker OPEN', [
                    'router_id' => $router->id,
                    'isp_id' => $router->isp_id,
                    'ip' => $router->ip_address,
                    'failures' => $failureCount,
                ]);
            }
        }

        $now = now();

        RouterMetric::create([
            'router_id' => $router->id,
            'isp_id' => $router->isp_id,
            'recorded_at' => $now,
            'latency_ms' => $latencyMs,
            'cpu_pct' => $cpu,
            'mem_pct' => $mem,
            'health_pct' => $health,
            'uptime_seconds' => $resource['uptime_seconds'] ?? null,
            'active_hotspot_users' => $hotspot,
            'active_pppoe_users' => $pppoe,
            'interfaces_up' => $ifaces['up'] ?? null,
            'interfaces_total' => $ifaces['total'] ?? null,
            'reachability' => $reachability,
            'error_message' => $error,
        ]);

        $update = [
            'reachability_status' => $storedReachability,
            'last_telemetry_at' => $now,
            'last_latency_ms' => $latencyMs,
            'last_health_pct' => $health,
            'last_cpu_pct' => $cpu,
            'last_mem_pct' => $mem,
            'last_uptime_seconds' => $resource['uptime_seconds'] ?? null,
            'last_firmware' => $resource['firmware'] ?? $router->last_firmware,
            'last_board' => $resource['board'] ?? $router->last_board,
            'last_active_hotspot_users' => $hotspot,
            'last_active_pppoe_users' => $pppoe,
            'last_interfaces_up' => $ifaces['up'] ?? null,
            'last_interfaces_total' => $ifaces['total'] ?? null,
            'last_poll_error' => $error,
            'poll_failure_count' => $failureCount,
        ];

        if ($pollSucceeded) {
            $update['last_seen_at'] = $now;
        }

        $router->update($update);
    }

    /**
     * Compose a 0-100 health score from reachability + key resource indicators.
     */
    protected function computeHealth(string $reachability, ?int $latencyMs, ?int $cpu, ?int $mem): int
    {
        if ($reachability === 'offline') {
            return 0;
        }

        $score = 100;
        if ($latencyMs !== null) {
            if ($latencyMs > 35) {
                $score -= min(40, (int) (($latencyMs - 35) * 0.8));
            }
        }
        if ($cpu !== null && $cpu > 70) {
            $score -= min(25, $cpu - 70);
        }
        if ($mem !== null && $mem > 80) {
            $score -= min(20, $mem - 80);
        }
        if ($reachability === 'degraded') {
            $score -= 15;
        }

        return max(5, min(100, $score));
    }
}
