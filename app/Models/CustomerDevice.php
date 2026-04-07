<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDevice extends Model
{
    protected $fillable = [
        'customer_id',
        'mac_address',
        'device_name',
        'device_type',
        'is_active',
        'is_blocked',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Normalize MAC address to lowercase with colons
     */
    public function setMacAddressAttribute($value)
    {
        // Normalize MAC address: remove separators, convert to lowercase, add colons
        $value = strtolower(preg_replace('/[^a-f0-9]/i', '', $value));
        if (strlen($value) === 12) {
            $value = implode(':', str_split($value, 2));
        }
        $this->attributes['mac_address'] = $value;
    }

    /**
     * Scope to get only active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_blocked', false);
    }

    /**
     * Scope to get blocked devices
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }
}
