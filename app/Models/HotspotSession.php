<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class HotspotSession extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'router_id',
        'customer_id',
        'username',
        'ip_address',
        'mac_address',
        'mikrotik_session_id',
        'bytes_uploaded',
        'bytes_downloaded',
        'uptime_seconds',
        'started_at',
        'ended_at',
        'status',
        'termination_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'bytes_uploaded' => 'integer',
        'bytes_downloaded' => 'integer',
        'uptime_seconds' => 'integer',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function dataUsageRecord(): HasOne
    {
        return $this->hasOne(DataUsage::class, 'hotspot_session_id');
    }

    public function getTotalBytesAttribute(): int
    {
        return $this->bytes_uploaded + $this->bytes_downloaded;
    }

    public function getFormattedTotalBytesAttribute(): string
    {
        return $this->formatBytes($this->total_bytes);
    }

    public function getFormattedUploadedAttribute(): string
    {
        return $this->formatBytes($this->bytes_uploaded);
    }

    public function getFormattedDownloadedAttribute(): string
    {
        return $this->formatBytes($this->bytes_downloaded);
    }

    public function getUptimeFormattedAttribute(): string
    {
        $hours = floor($this->uptime_seconds / 3600);
        $minutes = floor(($this->uptime_seconds % 3600) / 60);
        $seconds = $this->uptime_seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        }
        return sprintf('%ds', $seconds);
    }

    public function getDurationAttribute(): int
    {
        $end = $this->ended_at ?? now();
        return $end->diffInSeconds($this->started_at);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }
}





