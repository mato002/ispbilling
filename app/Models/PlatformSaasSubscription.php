<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform (PrimeBilling) SaaS subscription row for an ISP tenant — trial and plan linkage.
 */
class PlatformSaasSubscription extends Model
{
    public const STATUS_TRIALING = 'trialing';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'isp_id',
        'platform_saas_plan_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlatformSaasPlan::class, 'platform_saas_plan_id');
    }
}
