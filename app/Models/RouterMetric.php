<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterMetric extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'router_id',
        'isp_id',
        'recorded_at',
        'latency_ms',
        'cpu_pct',
        'mem_pct',
        'health_pct',
        'uptime_seconds',
        'active_hotspot_users',
        'active_pppoe_users',
        'rx_bytes_per_sec',
        'tx_bytes_per_sec',
        'interfaces_up',
        'interfaces_total',
        'reachability',
        'error_message',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latency_ms' => 'integer',
        'cpu_pct' => 'integer',
        'mem_pct' => 'integer',
        'health_pct' => 'integer',
        'uptime_seconds' => 'integer',
        'active_hotspot_users' => 'integer',
        'active_pppoe_users' => 'integer',
        'rx_bytes_per_sec' => 'integer',
        'tx_bytes_per_sec' => 'integer',
        'interfaces_up' => 'integer',
        'interfaces_total' => 'integer',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }
}
