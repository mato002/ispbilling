<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerUsageStat extends Model
{
    protected $fillable = [
        'customer_id',
        'subscription_id',
        'date',
        'bytes_uploaded',
        'bytes_downloaded',
        'total_bytes',
        'session_count',
        'total_uptime_seconds',
    ];

    protected $casts = [
        'date' => 'date',
        'bytes_uploaded' => 'integer',
        'bytes_downloaded' => 'integer',
        'total_bytes' => 'integer',
        'session_count' => 'integer',
        'total_uptime_seconds' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
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

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}





