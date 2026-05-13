<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GlobalPromoCode extends Model
{
    public const KIND_FIRST_MONTH_FREE = 'first_month_free';

    public const KIND_REFERRAL = 'referral';

    public const KIND_PERCENT_OFF = 'percent_off';

    protected $fillable = [
        'code',
        'kind',
        'title',
        'percent_off',
        'max_redemptions',
        'redemption_count',
        'starts_at',
        'ends_at',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'percent_off' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GlobalPromoCode $row) {
            if (empty($row->code)) {
                $row->code = strtoupper(Str::random(10));
            } else {
                $row->code = strtoupper(trim($row->code));
            }
        });
    }

    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }
        if ($this->max_redemptions !== null && $this->redemption_count >= $this->max_redemptions) {
            return false;
        }

        return true;
    }
}
