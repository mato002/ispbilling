<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkAuditLog extends Model
{
    use BelongsToIspTenant;

    public const ACTION_FORCE_MAC_BIND = 'force_mac_bind';

    public const ACTION_FLUSH_REBUILD_QUEUES = 'flush_session_rebuild_queues';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'isp_id',
        'user_id',
        'customer_id',
        'router_id',
        'action',
        'status',
        'description',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }
}
