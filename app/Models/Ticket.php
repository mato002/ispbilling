<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'customer_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'subject',
        'message',
        'status',
        'priority',
        'channel',
        'ticket_kind',
    ];

    public const KIND_CUSTOMER = 'customer';

    public const KIND_PLATFORM = 'platform';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }

    public function isPlatformTicket(): bool
    {
        return $this->ticket_kind === self::KIND_PLATFORM;
    }

    public function isCustomerTicket(): bool
    {
        return $this->ticket_kind === self::KIND_CUSTOMER;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Ticket>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Ticket>
     */
    public function scopeCustomerTickets($query)
    {
        return $query->where('ticket_kind', self::KIND_CUSTOMER);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Ticket>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Ticket>
     */
    public function scopePlatformTickets($query)
    {
        return $query->where('ticket_kind', self::KIND_PLATFORM);
    }
}
