<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpPool extends Model
{
    use BelongsToIspTenant;
    use HasFactory;

    protected $fillable = [
        'isp_id',
        'router_id',
        'name',
        'ranges',
        'next_pool',
        'comment',
        'is_active',
        'pool_type',
        'gateway_address',
        'netmask',
        'dns_servers',
        'domain',
        'wins_servers',
        'ntp_servers',
        'lease_time',
        'block_size',
        'block_count',
        'disabled',
        'config_file',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'disabled' => 'boolean',
        'ranges' => 'array',
        'dns_servers' => 'array',
        'wins_servers' => 'array',
        'ntp_servers' => 'array',
        'config_file' => 'array',
    ];

    /**
     * Get the ISP that owns the IP pool.
     */
    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class);
    }

    /**
     * Get the router that owns the IP pool.
     */
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Scope to get active IP pools.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('disabled', false);
    }

    /**
     * Scope to get IP pools for specific ISP.
     */
    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    /**
     * Get active IP pools for ISP.
     */
    public static function getActiveForIsp($ispId)
    {
        return self::forIsp($ispId)->active()->with('router')->get();
    }

    /**
     * Get the primary IP pool for ISP.
     */
    public static function getPrimaryForIsp($ispId)
    {
        return self::forIsp($ispId)->active()->first();
    }

    /**
     * Get the formatted range string.
     */
    public function getFormattedRangeAttribute()
    {
        if (empty($this->ranges)) {
            return 'N/A';
        }

        $formatted = [];
        foreach ($this->ranges as $range) {
            if (isset($range['start']) && isset($range['end'])) {
                $formatted[] = $range['start'] . ' - ' . $range['end'];
            } elseif (isset($range['address'])) {
                $formatted[] = $range['address'];
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * Get total number of IP addresses in the pool.
     */
    public function getTotalIpsAttribute()
    {
        $total = 0;
        if (!empty($this->ranges)) {
            foreach ($this->ranges as $range) {
                if (isset($range['start']) && isset($range['end'])) {
                    $start = ip2long($range['start']);
                    $end = ip2long($range['end']);
                    if ($start !== false && $end !== false) {
                        $total += ($end - $start + 1);
                    }
                } elseif (isset($range['address'])) {
                    $total += 1;
                }
            }
        }
        return $total;
    }

    /**
     * Get available IP addresses count.
     */
    public function getAvailableIpsAttribute()
    {
        // This would require integration with router to get actual usage
        // For now, return total as placeholder
        return $this->total_ips;
    }
}
