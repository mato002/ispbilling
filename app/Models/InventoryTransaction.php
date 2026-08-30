<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use App\Support\InventoryCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use BelongsToIspTenant;

    public const UPDATED_AT = null;

    public const ACTION_ADDED = 'added';

    public const ACTION_ASSIGNED = 'assigned';

    public const ACTION_RETURNED = 'returned';

    public const ACTION_REPLACED = 'replaced';

    public const ACTION_FAULTY = 'faulty';

    public const ACTION_RETIRED = 'retired';

    public const ACTION_TECHNICIAN_ASSIGNED = 'technician_assigned';

    public const ACTION_TECHNICIAN_RETURNED = 'technician_returned';

    public const ACTION_INSTALLED = 'installed';

    protected $fillable = [
        'isp_id',
        'inventory_item_id',
        'action',
        'performed_by_user_id',
        'customer_id',
        'inventory_assignment_id',
        'related_item_id',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(InventoryAssignment::class, 'inventory_assignment_id');
    }

    public function relatedItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'related_item_id');
    }

    public function scopeForIsp($query, int $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    public function actionLabel(): string
    {
        return InventoryCatalog::actionLabel($this->action);
    }
}
