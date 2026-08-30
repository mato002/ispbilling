<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessageQueue extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_BOTH = 'both';

    public const MAX_RETRIES = 3;

    protected $table = 'sms_message_queues';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'customer_id',
        'recipient_phone',
        'recipient_email',
        'message_body',
        'channel',
        'priority',
        'status',
        'cost_charged',
        'gateway_response',
        'retry_count',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'cost_charged' => 'decimal:4',
            'retry_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'tenant_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CommunicationCampaign::class, 'campaign_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function canRetry(): bool
    {
        return $this->retry_count < self::MAX_RETRIES;
    }
}
