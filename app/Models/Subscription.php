<?php

namespace App\Models;

use App\Support\IspTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('isp_tenant_subscription', function (Builder $builder) {
            if (! IspTenant::shouldScopeQueries()) {
                return;
            }

            $tenantId = IspTenant::id();
            if ($tenantId === null) {
                return;
            }

            $table = $builder->getModel()->getTable();

            // Use SQL exists on `customers` instead of whereHas('customer'), so we do not nest
            // Customer's global scope (avoids pathological query/binding growth on some requests).
            $builder->whereExists(function ($query) use ($tenantId, $table) {
                $query->from('customers')
                    ->whereColumn('customers.id', "{$table}.customer_id")
                    ->where('customers.isp_id', $tenantId);
            });
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutIspTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('isp_tenant_subscription');
    }

    protected $fillable = [
        'customer_id',
        'plan_id',
        'start_date',
        'end_date',
        'ends_at',
        'renewal_date',
        'status',
        'auto_renew',
        'last_cap_enforced_at',
        'data_used_mb',
        'data_exhausted_at',
        'static_ip',
        'static_ip_active',
        'service_status',
        'parent_subscription_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'ends_at' => 'datetime',
        'renewal_date' => 'date',
        'auto_renew' => 'boolean',
        'last_cap_enforced_at' => 'datetime',
        'data_used_mb' => 'integer',
        'data_exhausted_at' => 'datetime',
        'static_ip_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function parentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'parent_subscription_id');
    }

    public function childSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'parent_subscription_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'subscription_id');
    }

    public function isExpired(): bool
    {
        $expired = $this->ends_at
            ? $this->ends_at->isPast()
            : ($this->end_date && $this->end_date->lt(today()));

        return $expired && $this->status === 'active';
    }

    public function isActive(): bool
    {
        $notExpired = $this->ends_at
            ? $this->ends_at->isFuture()
            : ($this->end_date && $this->end_date->gte(today()));

        return $this->status === 'active' && $notExpired;
    }

    public function renew(): void
    {
        $duration = max(1, (int) ($this->plan->duration_days ?? 30));
        $currentEnd = $this->ends_at ?? $this->end_date?->copy()->endOfDay();
        $baseEnd = ($currentEnd && $currentEnd->isFuture())
            ? $currentEnd->copy()
            : now();
        $newEnd = $this->plan?->hotspotExpiresAt($baseEnd)
            ?? $baseEnd->copy()->addDays($duration);
        $keepStart = $this->status === 'active' && $this->start_date;

        $updates = [
            'start_date' => $keepStart ? $this->start_date : now(),
            'end_date' => $newEnd,
            'ends_at' => $newEnd,
            'renewal_date' => $newEnd,
            'status' => 'active',
        ];

        if ($this->plan->tracksDataUsage()) {
            $updates['data_used_mb'] = 0;
            $updates['data_exhausted_at'] = null;
        }

        $this->update($updates);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'auto_renew' => false,
        ]);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
        if ($this->customer && $this->customer->status !== 'suspended') {
            $this->customer->update(['status' => 'expired']);
        }
    }

    public function isDataExhausted(): bool
    {
        return $this->data_exhausted_at !== null;
    }

    public function markDataExhausted(): void
    {
        $this->update([
            'data_exhausted_at' => now(),
            'status' => 'suspended',
        ]);
    }

    public function recordDataUsageMb(int $megabytes): void
    {
        $this->increment('data_used_mb', $megabytes);

        $plan = $this->plan;
        if ($plan && $plan->tracksDataUsage() && $plan->data_limit_mb) {
            if ($this->data_used_mb >= $plan->data_limit_mb) {
                $this->markDataExhausted();
            }
        }
    }
}
