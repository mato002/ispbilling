<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'customer_id',
        'plan_id',
        'start_date',
        'end_date',
        'renewal_date',
        'status',
        'auto_renew',
        'last_cap_enforced_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_date' => 'date',
        'auto_renew' => 'boolean',
        'last_cap_enforced_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'subscription_id');
    }

    public function isExpired(): bool
    {
        return $this->end_date < now() && $this->status === 'active';
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date >= now();
    }

    public function renew(): void
    {
        $this->update([
            'start_date' => now(),
            'end_date' => now()->addDays($this->plan->duration_days),
            'renewal_date' => now()->addDays($this->plan->duration_days),
            'status' => 'active',
        ]);
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
        $this->customer->update(['status' => 'expired']);
    }
}
