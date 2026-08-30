<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataUsage extends Model
{
    use BelongsToIspTenant;

    public const SOURCE_HOTSPOT = 'hotspot';

    public const SOURCE_PPPOE = 'pppoe';

    public const SOURCE_RADIUS = 'radius';

    public const SOURCE_SNAPSHOT = 'snapshot';

    protected $fillable = [
        'isp_id',
        'customer_id',
        'package_id',
        'router_id',
        'upload_bytes',
        'download_bytes',
        'total_bytes',
        'recorded_at',
        'hotspot_session_id',
        'ip_address',
        'mac_address',
        'source',
        'started_at',
        'ended_at',
        'external_session_id',
    ];

    protected $casts = [
        'upload_bytes' => 'integer',
        'download_bytes' => 'integer',
        'total_bytes' => 'integer',
        'recorded_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'package_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    public function hotspotSession(): BelongsTo
    {
        return $this->belongsTo(HotspotSession::class, 'hotspot_session_id');
    }

    public function getFormattedTotalBytesAttribute(): string
    {
        return self::formatBytes($this->total_bytes);
    }

    public function getFormattedUploadBytesAttribute(): string
    {
        return self::formatBytes($this->upload_bytes);
    }

    public function getFormattedDownloadBytesAttribute(): string
    {
        return self::formatBytes($this->download_bytes);
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }
}
