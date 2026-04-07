<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BandwidthProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'isp_id',
        'name',
        'download_limit',
        'upload_limit',
        'download_burst_limit',
        'upload_burst_limit',
        'download_burst_threshold',
        'upload_burst_threshold',
        'download_burst_time',
        'upload_burst_time',
        'priority',
        'limit_at',
        'max_limit',
        'burst_limit',
        'burst_threshold',
        'burst_time',
        'target',
        'comment',
        'is_active',
        'parent_profile_id',
        'queue_type',
        'rate_limit_rx',
        'rate_limit_tx',
        'rate_limit_rx_min',
        'rate_limit_tx_min',
        'rate_limit_rx_max',
        'rate_limit_tx_max',
        'config_file',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'download_limit' => 'integer',
        'upload_limit' => 'integer',
        'download_burst_limit' => 'integer',
        'upload_burst_limit' => 'integer',
        'download_burst_threshold' => 'integer',
        'upload_burst_threshold' => 'integer',
        'download_burst_time' => 'integer',
        'upload_burst_time' => 'integer',
        'priority' => 'integer',
        'limit_at' => 'integer',
        'max_limit' => 'integer',
        'burst_limit' => 'integer',
        'burst_threshold' => 'integer',
        'burst_time' => 'integer',
        'parent_profile_id' => 'integer',
        'rate_limit_rx' => 'integer',
        'rate_limit_tx' => 'integer',
        'rate_limit_rx_min' => 'integer',
        'rate_limit_tx_min' => 'integer',
        'rate_limit_rx_max' => 'integer',
        'rate_limit_tx_max' => 'integer',
        'config_file' => 'array',
    ];

    /**
     * Get the ISP that owns the bandwidth profile.
     */
    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class);
    }

    /**
     * Get the parent profile.
     */
    public function parentProfile(): BelongsTo
    {
        return $this->belongsTo(BandwidthProfile::class, 'parent_profile_id');
    }

    /**
     * Get the child profiles.
     */
    public function childProfiles(): HasMany
    {
        return $this->hasMany(BandwidthProfile::class, 'parent_profile_id');
    }

    /**
     * Get plans that use this profile.
     */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class, 'bandwidth_profile_id');
    }

    /**
     * Scope to get active bandwidth profiles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get bandwidth profiles for specific ISP.
     */
    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    /**
     * Get active bandwidth profiles for ISP.
     */
    public static function getActiveForIsp($ispId)
    {
        return self::forIsp($ispId)->active()->orderBy('name')->get();
    }

    /**
     * Get formatted download speed.
     */
    public function getFormattedDownloadSpeedAttribute()
    {
        return $this->formatSpeed($this->download_limit);
    }

    /**
     * Get formatted upload speed.
     */
    public function getFormattedUploadSpeedAttribute()
    {
        return $this->formatSpeed($this->upload_limit);
    }

    /**
     * Format speed in Mbps/Kbps.
     */
    private function formatSpeed($speed)
    {
        if ($speed >= 1000000) {
            return number_format($speed / 1000000, 1) . ' Mbps';
        } elseif ($speed >= 1000) {
            return number_format($speed / 1000, 1) . ' Mbps';
        } else {
            return $speed . ' Kbps';
        }
    }

    /**
     * Get MikroTik queue tree configuration.
     */
    public function getMikrotikConfig()
    {
        return [
            'name' => $this->name,
            'max-limit' => $this->download_limit . '/' . $this->upload_limit,
            'burst-limit' => ($this->download_burst_limit ?? $this->download_limit) . '/' . ($this->upload_burst_limit ?? $this->upload_limit),
            'burst-threshold' => ($this->download_burst_threshold ?? ($this->download_limit * 0.8)) . '/' . ($this->upload_burst_threshold ?? ($this->upload_limit * 0.8)),
            'burst-time' => ($this->download_burst_time ?? 10) . '/' . ($this->upload_burst_time ?? 10),
            'priority' => $this->priority ?? 8,
        ];
    }

    /**
     * Create MikroTik queue simple configuration.
     */
    public function createMikrotikQueueSimple()
    {
        return [
            'name' => $this->name,
            'target' => $this->target ?? '',
            'max-limit' => ($this->download_limit / 1000) . 'k/' . ($this->upload_limit / 1000) . 'k',
            'limit-at' => $this->limit_at ? ($this->limit_at / 1000) . 'k/' . ($this->limit_at / 1000) . 'k' : '',
            'burst-limit' => $this->burst_limit ? ($this->burst_limit / 1000) . 'k/' . ($this->burst_limit / 1000) . 'k' : '',
            'burst-threshold' => $this->burst_threshold ? ($this->burst_threshold / 1000) . 'k/' . ($this->burst_threshold / 1000) . 'k' : '',
            'burst-time' => $this->burst_time ?? '10s',
            'priority' => $this->priority ?? 8,
            'comment' => $this->comment ?? '',
        ];
    }
}
