<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryPurchase extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'vendor_name',
        'invoice_number',
        'total_cost',
        'purchase_date',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'inventory_purchase_id');
    }

    public function scopeForIsp($query, int $ispId)
    {
        return $query->where('isp_id', $ispId);
    }
}
