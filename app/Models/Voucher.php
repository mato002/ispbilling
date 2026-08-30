<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use BelongsToIspTenant;

    public const CODE_LENGTH = 12;

    protected $fillable = [
        'isp_id',
        'router_id',
        'plan_id',
        'created_by',
        'voucher_code',
        'voucher_type',
        'time_limit_hours',
        'data_limit_mb',
        'speed_profile',
        'price',
        'status',
        'expires_at',
        'used_by',
        'assigned_to',
        'used_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'time_limit_hours' => 'integer',
        'data_limit_mb' => 'integer',
        'price' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'used_by');
    }

    public function assignedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'assigned_to');
    }

    public function isValid(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function markAsUsed(?int $customerId = null, ?string $ipAddress = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($ipAddress) {
            $metadata['used_by_ip'] = $ipAddress;
        }

        $this->update([
            'status' => 'used',
            'used_by' => $customerId,
            'used_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Generate a unique alphanumeric voucher code (hotspot portal expects 12 chars).
     */
    public static function generateCode(int $length = self::CODE_LENGTH): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (self::where('voucher_code', $code)->exists());

        return $code;
    }

    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (empty($voucher->voucher_code)) {
                $voucher->voucher_code = static::generateCode();
            }

            if (empty($voucher->isp_id) && auth()->check()) {
                $voucher->isp_id = auth()->user()->isp_id;
            }
        });
    }
}
