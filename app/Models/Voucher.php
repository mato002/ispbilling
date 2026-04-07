<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Voucher extends Model
{
    protected $fillable = [
        'isp_id',
        'plan_id',
        'code',
        'package_type',
        'package_name',
        'duration_hours',
        'data_limit_mb',
        'price',
        'status',
        'expires_at',
        'used_at',
        'used_by_customer_id',
        'used_by_ip',
        'notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'duration_hours' => 'integer',
        'data_limit_mb' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Get the ISP that owns the voucher
     */
    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    /**
     * Get the plan associated with the voucher
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Get the customer who used the voucher
     */
    public function usedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'used_by_customer_id');
    }

    /**
     * Check if voucher is valid (active and not expired)
     */
    public function isValid(): bool
    {
        return $this->status === 'active' 
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if voucher is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if voucher is used
     */
    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    /**
     * Mark voucher as used
     */
    public function markAsUsed(?int $customerId = null, ?string $ipAddress = null): void
    {
        $this->update([
            'status' => 'used',
            'used_by_customer_id' => $customerId,
            'used_by_ip' => $ipAddress,
            'used_at' => now(),
        ]);
    }

    /**
     * Mark voucher as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Generate unique voucher code
     */
    public static function generateCode(int $length = 8): string
    {
        do {
            // Generate alphanumeric code (uppercase letters and numbers)
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Get formatted package description
     */
    public function getPackageDescriptionAttribute(): string
    {
        if ($this->package_name) {
            return $this->package_name;
        }

        $description = '';
        if ($this->duration_hours) {
            if ($this->duration_hours == 1) {
                $description .= '1 Hour';
            } elseif ($this->duration_hours == 24) {
                $description .= 'Daily';
            } elseif ($this->duration_hours == 168) {
                $description .= 'Weekly';
            } elseif ($this->duration_hours == 720) {
                $description .= 'Monthly';
            } else {
                $description .= $this->duration_hours . ' Hours';
            }
        }

        if ($this->data_limit_mb) {
            if ($description) $description .= ' / ';
            if ($this->data_limit_mb < 1024) {
                $description .= $this->data_limit_mb . 'MB';
            } else {
                $description .= round($this->data_limit_mb / 1024, 1) . 'GB';
            }
        }

        return $description ?: 'Custom Package';
    }

    /**
     * Scope to filter by ISP
     */
    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    /**
     * Scope to filter active vouchers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to filter used vouchers
     */
    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    /**
     * Scope to filter expired vouchers
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
              ->orWhere(function ($q2) {
                  $q2->where('status', 'active')
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
              });
        });
    }

    /**
     * Scope to filter by package type
     */
    public function scopeByPackageType($query, $type)
    {
        return $query->where('package_type', $type);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (empty($voucher->code)) {
                $voucher->code = static::generateCode();
            }
            
            // Set ISP ID from authenticated user if not provided
            if (empty($voucher->isp_id) && auth()->check()) {
                $voucher->isp_id = auth()->user()->isp_id;
            }
        });
    }
}





