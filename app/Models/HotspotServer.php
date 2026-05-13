<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotspotServer extends Model
{
    use BelongsToIspTenant;
    use HasFactory;

    protected $fillable = [
        'isp_id',
        'router_id',
        'name',
        'interface',
        'is_active',
        'server_profile',
        'default_profile',
        'login_by',
        'use_radius',
        'radius_accounting',
        'mac_cookie_timeout',
        'idle_timeout',
        'keepalive_timeout',
        'status_autologout',
        'allowed_users',
        'shared_users',
        'mac_format',
        'mac_password',
        'radius_mac_format',
        'radius_mac_password',
        'trial_uptime',
        'trial_uptime_reset',
        'trial_profile',
        'trial_users',
        'config_file',
        'config_backup',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_radius' => 'boolean',
        'radius_accounting' => 'boolean',
        'status_autologout' => 'boolean',
        'config_file' => 'array',
        'config_backup' => 'array',
    ];

    /**
     * Get the ISP that owns the hotspot server.
     */
    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class);
    }

    /**
     * Get the router that owns the hotspot server.
     */
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Scope to get active hotspot servers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get hotspot servers for specific ISP.
     */
    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    /**
     * Get active hotspot servers for ISP.
     */
    public static function getActiveForIsp($ispId)
    {
        return self::forIsp($ispId)->active()->with('router')->get();
    }

    /**
     * Get the primary hotspot server for ISP.
     */
    public static function getPrimaryForIsp($ispId)
    {
        return self::forIsp($ispId)->active()->first();
    }
}
