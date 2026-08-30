<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use App\Support\InventoryCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAssignment extends Model
{
    use BelongsToIspTenant;

    public const INSTALLATION_PENDING = 'pending';

    public const INSTALLATION_COMPLETED = 'completed';

    protected $fillable = [
        'isp_id',
        'inventory_item_id',
        'customer_id',
        'router_id',
        'technician_user_id',
        'assigned_by_user_id',
        'installation_date',
        'installation_location',
        'installation_gps',
        'installation_status',
        'assigned_at',
        'returned_at',
        'is_active',
        'replaced_by_assignment_id',
        'notes',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function replacedByAssignment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_assignment_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_assignment_id');
    }

    public function scopeForIsp($query, int $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function installationStatusLabel(): string
    {
        return InventoryCatalog::INSTALLATION_STATUSES[$this->installation_status] ?? ucfirst((string) $this->installation_status);
    }
}
