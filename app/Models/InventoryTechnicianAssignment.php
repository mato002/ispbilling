<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTechnicianAssignment extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'inventory_item_id',
        'technician_user_id',
        'assigned_by_user_id',
        'assigned_at',
        'returned_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
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

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function scopeForIsp($query, int $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTechnician($query, int $userId)
    {
        return $query->where('technician_user_id', $userId);
    }
}
