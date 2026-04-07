<?php

namespace App\Models;

use App\Models\HotspotServer;
use App\Models\IpPool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_ssl' => 'boolean',
        'port' => 'integer',
    ];

    protected $hidden = [
        'password',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'router_id');
    }

    /**
     * Get the hotspot servers for the router.
     */
    public function hotspotServers()
    {
        return $this->hasMany(HotspotServer::class);
    }

    /**
     * Get the active hotspot servers for the router.
     */
    public function activeHotspotServers()
    {
        return $this->hotspotServers()->where('is_active', true);
    }

    /**
     * Get the IP pools for the router.
     */
    public function ipPools()
    {
        return $this->hasMany(IpPool::class);
    }

    /**
     * Get the active IP pools for the router.
     */
    public function activeIpPools()
    {
        return $this->ipPools()->where('is_active', true)->where('disabled', false);
    }

    /**
     * Scope to get routers for a specific ISP.
     */
    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    /**
     * Scope to get active routers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getApiUrl(): string
    {
        $protocol = $this->use_ssl ? 'https' : 'http';
        return "{$protocol}://{$this->ip_address}:{$this->port}";
    }
}
