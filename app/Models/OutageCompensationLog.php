<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutageCompensationLog extends Model
{
    use BelongsToIspTenant;

    public const TYPE_SUBSCRIPTION = 'subscription_extension';

    public const TYPE_VOUCHER = 'voucher_extension';

    protected $fillable = [
        'outage_id',
        'isp_id',
        'admin_user_id',
        'customer_id',
        'compensation_type',
        'duration_value',
        'duration_unit',
        'subscription_id',
        'voucher_id',
        'previous_expires_at',
        'new_expires_at',
        'notes',
    ];

    protected $casts = [
        'duration_value' => 'integer',
    ];

    public function outage(): BelongsTo
    {
        return $this->belongsTo(Outage::class, 'outage_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function durationLabel(): string
    {
        $unit = $this->duration_value === 1
            ? rtrim($this->duration_unit, 's')
            : $this->duration_unit;

        return "{$this->duration_value} {$unit}";
    }
}
