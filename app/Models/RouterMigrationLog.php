<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterMigrationLog extends Model
{
    public const ACTION_DISCONNECT = 'disconnect';

    public const ACTION_RECREATE_USER = 'recreate_user';

    public const ACTION_APPLY_QUEUE = 'apply_queue';

    public const ACTION_COMPLETED = 'completed';

    public const ACTION_FAILED = 'failed';

    public const ACTION_ROLLBACK = 'rollback';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'batch_id',
        'customer_id',
        'action',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(RouterMigrationBatch::class, 'batch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
