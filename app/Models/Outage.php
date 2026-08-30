<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outage extends Model
{
    use BelongsToIspTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'isp_id',
        'router_id',
        'created_by_user_id',
        'title',
        'description',
        'started_at',
        'ended_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function compensationLogs(): HasMany
    {
        return $this->hasMany(OutageCompensationLog::class, 'outage_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForIsp($query, int $ispId)
    {
        return $query->where('isp_id', $ispId);
    }
}
