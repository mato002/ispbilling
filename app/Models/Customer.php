<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Customer extends Model
{
    use BelongsToIspTenant;
    use Notifiable;

    protected $fillable = [
        'isp_id',
        'router_id',
        'full_name',
        'phone_number',
        'email',
        'location',
        'connection_type',
        'username',
        'password',
        'status',
        'installation_date',
        'mikrotik_user_id',
        'notes',
        'custom_attributes',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'custom_attributes' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'customer_id');
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latest()
            ->first();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'customer_id');
    }

    public function hotspotSessions(): HasMany
    {
        return $this->hasMany(HotspotSession::class, 'customer_id');
    }

    public function usageStats(): HasMany
    {
        return $this->hasMany(CustomerUsageStat::class, 'customer_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(CustomerDevice::class, 'customer_id');
    }

    public function activeDevices(): HasMany
    {
        return $this->hasMany(CustomerDevice::class, 'customer_id')->where('is_active', true)->where('is_blocked', false);
    }

    /**
     * Scope to get customers for a specific ISP.
     */
    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    /**
     * Scope to get active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->activeSubscription() !== null;
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->username)) {
                $customer->username = static::generateUsername($customer->isp_id);
            }
        });
    }

    protected static function generateUsername(int $ispId): string
    {
        $isp = ISP::find($ispId);
        $prefix = $isp ? Str::upper(Str::substr($isp->name, 0, 3)) : 'ISP';
        $number = static::where('isp_id', $ispId)->max('id') + 1;

        return $prefix.str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
