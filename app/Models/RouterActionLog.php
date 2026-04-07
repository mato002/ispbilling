<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterActionLog extends Model
{
    protected $fillable = [
        'isp_id',
        'router_id',
        'customer_id',
        'user_id',
        'action',
        'status',
        'description',
        'error_message',
        'request_data',
        'response_data',
        'ip_address',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Log a router action
     */
    public static function log(array $data): self
    {
        return self::create(array_merge([
            'ip_address' => request()->ip(),
        ], $data));
    }

    /**
     * Scope to filter by ISP
     */
    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }
}
