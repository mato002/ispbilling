<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouterMigrationBatch extends Model
{
    use BelongsToIspTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ROLLED_BACK = 'rolled_back';

    protected $fillable = [
        'isp_id',
        'old_router_id',
        'new_router_id',
        'status',
        'total_customers',
        'processed_customers',
        'success_count',
        'failed_count',
        'created_by',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function oldRouter(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'old_router_id');
    }

    public function newRouter(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'new_router_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RouterMigrationLog::class, 'batch_id');
    }

    public function progressPercent(): int
    {
        if ($this->total_customers <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->processed_customers / $this->total_customers) * 100));
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_ROLLED_BACK,
        ], true);
    }

    public function canRollback(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true)
            && $this->success_count > 0;
    }
}
