<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use App\Support\PlanPackageTypes;
use Illuminate\Database\Eloquent\Builder;
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
        'package_type',
        'access_type',
        'price',
        'duration_days',
        'validity_period',
        'speed_profile',
        'upload_speed_kbps',
        'download_speed_kbps',
        'billing_type',
        'data_limit_mb',
        'time_limit_hours',
        'time_limit_minutes',
        'is_popular',
        'is_best_value',
        'device_limit',
        'is_active',
        'is_one_time',
        'is_recurring',
        'service_type',
        'requires_pppoe_plan',
        'customer_visible',
        'archived_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'upload_speed_kbps' => 'integer',
        'download_speed_kbps' => 'integer',
        'data_limit_mb' => 'integer',
        'time_limit_hours' => 'integer',
        'time_limit_minutes' => 'integer',
        'is_popular' => 'boolean',
        'is_best_value' => 'boolean',
        'device_limit' => 'integer',
        'is_active' => 'boolean',
        'is_one_time' => 'boolean',
        'is_recurring' => 'boolean',
        'requires_pppoe_plan' => 'boolean',
        'customer_visible' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeConnectionPackages(Builder $query): Builder
    {
        return $query->whereIn('package_type', PlanPackageTypes::connectionPackages());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePrimarySellable(Builder $query): Builder
    {
        return $query->whereIn('package_type', [
            PlanPackageTypes::HOTSPOT,
            PlanPackageTypes::PPPOE,
            PlanPackageTypes::DATA_BUNDLE,
            PlanPackageTypes::UNLIMITED,
        ]);
    }

    public function packageTypeLabel(): string
    {
        return PlanPackageTypes::label($this->package_type ?? $this->access_type);
    }

    public function isHotspot(): bool
    {
        return $this->package_type === PlanPackageTypes::HOTSPOT
            || $this->access_type === PlanPackageTypes::HOTSPOT;
    }

    public function isPppoe(): bool
    {
        return ($this->package_type ?? $this->access_type) === PlanPackageTypes::PPPOE;
    }

    public function isDataBundle(): bool
    {
        return $this->package_type === PlanPackageTypes::DATA_BUNDLE;
    }

    public function isUnlimitedPackage(): bool
    {
        return $this->package_type === PlanPackageTypes::UNLIMITED
            || $this->billing_type === 'unlimited';
    }

    public function isStaticIpAddon(): bool
    {
        return $this->package_type === PlanPackageTypes::STATIC_IP_ADDON;
    }

    public function isInstallationService(): bool
    {
        return $this->package_type === PlanPackageTypes::INSTALLATION_SERVICE;
    }

    public function isAddonService(): bool
    {
        return $this->package_type === PlanPackageTypes::ADDON_SERVICE;
    }

    public function isConnectionPackage(): bool
    {
        return in_array($this->package_type, PlanPackageTypes::connectionPackages(), true);
    }

    public function tracksDataUsage(): bool
    {
        return $this->isDataBundle()
            || $this->billing_type === 'data'
            || ($this->data_limit_mb !== null && $this->data_limit_mb > 0);
    }

    /** Return the MikroTik uptime limit for this package, if it has one. */
    public function hotspotUptimeLimit(): ?string
    {
        if ($this->time_limit_minutes) {
            $hours = intdiv($this->time_limit_minutes, 60);
            $minutes = $this->time_limit_minutes % 60;

            return ($hours ? $hours.'h' : '').($minutes ? $minutes.'m' : '');
        }

        if ($this->time_limit_hours) {
            return $this->time_limit_hours.'h';
        }

        return $this->duration_days ? ($this->duration_days * 24).'h' : null;
    }

    public function hotspotExpiresAt(?\Illuminate\Support\Carbon $startsAt = null): ?\Illuminate\Support\Carbon
    {
        $expiresAt = ($startsAt ?? now())->copy();

        if ($this->time_limit_minutes) {
            return $expiresAt->addMinutes($this->time_limit_minutes);
        }

        if ($this->time_limit_hours) {
            return $expiresAt->addHours($this->time_limit_hours);
        }

        return $this->duration_days ? $expiresAt->addDays($this->duration_days) : null;
    }

    public function getFormattedSpeedAttribute(): string
    {
        if ($this->download_speed_kbps) {
            $down = $this->download_speed_kbps >= 1000
                ? round($this->download_speed_kbps / 1000, 1).'Mbps'
                : $this->download_speed_kbps.'Kbps';

            if ($this->upload_speed_kbps) {
                $up = $this->upload_speed_kbps >= 1000
                    ? round($this->upload_speed_kbps / 1000, 1).'Mbps'
                    : $this->upload_speed_kbps.'Kbps';

                return "{$down}/{$up}";
            }

            return $down;
        }

        return 'Unlimited';
    }
}
