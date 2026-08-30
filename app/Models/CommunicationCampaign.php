<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationCampaign extends Model
{
    use BelongsToIspTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_BOTH = 'both';

    protected $fillable = [
        'isp_id',
        'created_by_user_id',
        'title',
        'message_body',
        'channel',
        'audience_filters',
        'status',
        'scheduled_at',
        'sent_count',
        'failed_count',
        'queued_count',
        'last_error',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'audience_filters' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'queued_count' => 'integer',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function queueMessages(): HasMany
    {
        return $this->hasMany(SmsMessageQueue::class, 'campaign_id');
    }

    public function isLaunchable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED], true);
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }
}
