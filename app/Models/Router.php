<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'name',
        'ip_address',
        'username',
        'password',
        'port',
        'use_ssl',
        'location',
        'is_active',
        // Asset / CMDB
        'vendor',
        'model',
        'serial_number',
        'mac_address',
        'latitude',
        'longitude',
        'branch',
        'tags',
        'owner_user_id',
        'lifecycle_status',
        'purchase_date',
        'warranty_expiry',
        'monthly_cost',
        // Cached telemetry (written by PollRouterHealthJob)
        'reachability_status',
        'last_seen_at',
        'last_telemetry_at',
        'last_latency_ms',
        'last_health_pct',
        'last_cpu_pct',
        'last_mem_pct',
        'last_uptime_seconds',
        'last_firmware',
        'last_board',
        'last_active_hotspot_users',
        'last_active_pppoe_users',
        'last_interfaces_up',
        'last_interfaces_total',
        'last_poll_error',
        'poll_failure_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_ssl' => 'boolean',
        'port' => 'integer',
        // Encrypt the RouterOS credential at rest. Existing plaintext rows will
        // be re-saved on next edit/poll; until then `decrypt()` may throw and
        // MikroTikService falls back to raw value via accessor below.
        'password' => 'encrypted',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'monthly_cost' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'last_seen_at' => 'datetime',
        'last_telemetry_at' => 'datetime',
        'last_latency_ms' => 'integer',
        'last_health_pct' => 'integer',
        'last_cpu_pct' => 'integer',
        'last_mem_pct' => 'integer',
        'last_uptime_seconds' => 'integer',
        'last_active_hotspot_users' => 'integer',
        'last_active_pppoe_users' => 'integer',
        'last_interfaces_up' => 'integer',
        'last_interfaces_total' => 'integer',
        'poll_failure_count' => 'integer',
    ];

    protected $hidden = [
        'password',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'router_id');
    }

    public function hotspotServers()
    {
        return $this->hasMany(HotspotServer::class);
    }

    public function activeHotspotServers()
    {
        return $this->hotspotServers()->where('is_active', true);
    }

    public function ipPools()
    {
        return $this->hasMany(IpPool::class);
    }

    public function activeIpPools()
    {
        return $this->ipPools()->where('is_active', true)->where('disabled', false);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(RouterMetric::class)->orderByDesc('recorded_at');
    }

    public function backups(): HasMany
    {
        return $this->hasMany(RouterBackup::class)->latest();
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(RouterActionLog::class)->latest();
    }

    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getApiUrl(): string
    {
        $protocol = $this->use_ssl ? 'https' : 'http';

        return "{$protocol}://{$this->ip_address}:{$this->port}";
    }

    /**
     * Comma-separated tags as a clean array.
     *
     * @return array<int, string>
     */
    public function tagsArray(): array
    {
        if (! $this->tags) {
            return [];
        }

        return collect(explode(',', $this->tags))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * True when telemetry has been written by PollRouterHealthJob.
     */
    public function hasLiveTelemetry(): bool
    {
        return $this->last_telemetry_at !== null;
    }

    /**
     * Returns the recent metrics points used to power the sparkline.
     * Newest-last ordering for chart rendering.
     *
     * @return array<int, int>
     */
    public function recentSparkline(int $points = 8, string $field = 'health_pct'): array
    {
        $rows = $this->metrics()
            ->orderByDesc('recorded_at')
            ->limit($points)
            ->pluck($field)
            ->reverse()
            ->values()
            ->all();

        return array_map(fn ($v) => (int) max(0, $v ?? 0), $rows);
    }

    /**
     * Snapshot used by the NOC view. Reads cached columns if present,
     * otherwise returns an explicit "no data yet" shape so the page can show
     * a clear empty state instead of synthetic numbers.
     *
     * @return array<string, mixed>
     */
    public function operationalSnapshot(): array
    {
        if (! $this->is_active) {
            return $this->emptySnapshot('offline', 'Router marked inactive');
        }

        if (! $this->hasLiveTelemetry()) {
            return $this->emptySnapshot('unknown', 'Awaiting first telemetry poll');
        }

        $reach = $this->reachability_status ?: 'online';
        $latency = $this->last_latency_ms;
        $warn = $reach === 'degraded' || ($latency !== null && $latency > 35);

        $spark = $this->recentSparkline(8, 'health_pct');
        if (count($spark) < 2) {
            $spark = array_fill(0, 8, $this->last_health_pct ?? 0);
        }

        $lastSyncSec = $this->last_telemetry_at
            ? max(0, now()->diffInSeconds($this->last_telemetry_at))
            : null;

        return [
            'reachability' => $reach,
            'latency_ms' => $latency,
            'uptime_pct' => $this->last_uptime_seconds
                ? min(100, (int) round(($this->last_uptime_seconds / max(1, $this->last_uptime_seconds + 60)) * 100))
                : null,
            'last_sync_sec' => $lastSyncSec,
            'health_pct' => $this->last_health_pct ?? 0,
            'api_status' => $warn ? 'degraded' : ($reach === 'offline' ? 'disconnected' : 'connected'),
            'api_label' => $warn ? 'API · elevated latency' : ($reach === 'offline' ? 'API offline' : 'API connected'),
            'model' => $this->last_board ?: ($this->model ?: 'MikroTik'),
            'spark' => $spark,
            'traffic_score' => count($spark) ? (int) round(array_sum($spark) / count($spark)) : 0,
            'firmware' => $this->last_firmware ?: '—',
            'cpu_pct' => $this->last_cpu_pct,
            'mem_pct' => $this->last_mem_pct,
            'hotspot_users' => $this->last_active_hotspot_users,
            'pppoe_users' => $this->last_active_pppoe_users,
            'interfaces_up' => $this->last_interfaces_up ?? 0,
            'interfaces_total' => $this->last_interfaces_total ?? 0,
            'has_live' => true,
            'last_seen_human' => $this->last_seen_at?->diffForHumans(),
            'last_poll_error' => $this->last_poll_error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptySnapshot(string $reach, string $reason): array
    {
        return [
            'reachability' => $reach,
            'latency_ms' => null,
            'uptime_pct' => 0,
            'last_sync_sec' => null,
            'health_pct' => 0,
            'api_status' => $reach === 'offline' ? 'disconnected' : 'unknown',
            'api_label' => $reason,
            'model' => $this->model ?: 'MikroTik',
            'spark' => array_fill(0, 8, 0),
            'traffic_score' => 0,
            'firmware' => '—',
            'cpu_pct' => null,
            'mem_pct' => null,
            'hotspot_users' => null,
            'pppoe_users' => null,
            'interfaces_up' => 0,
            'interfaces_total' => 0,
            'has_live' => false,
            'last_seen_human' => null,
            'last_poll_error' => $this->last_poll_error,
        ];
    }
}
