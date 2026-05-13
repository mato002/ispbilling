<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'name',
        'description',
        'access_type',
        'price',
        'duration_days',
        'speed_profile',
        'upload_speed_kbps',
        'download_speed_kbps',
        'billing_type',
        'data_limit_mb',
        'time_limit_hours',
        'device_limit',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'upload_speed_kbps' => 'integer',
        'download_speed_kbps' => 'integer',
        'data_limit_mb' => 'integer',
        'time_limit_hours' => 'integer',
        'device_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function getFormattedSpeedAttribute(): string
    {
        if ($this->download_speed_kbps) {
            $down = $this->download_speed_kbps >= 1000 
                ? round($this->download_speed_kbps / 1000, 1) . 'Mbps' 
                : $this->download_speed_kbps . 'Kbps';
            
            if ($this->upload_speed_kbps) {
                $up = $this->upload_speed_kbps >= 1000 
                    ? round($this->upload_speed_kbps / 1000, 1) . 'Mbps' 
                    : $this->upload_speed_kbps . 'Kbps';
                return "{$down}/{$up}";
            }
            return $down;
        }
        return 'Unlimited';
    }
}
