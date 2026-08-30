<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use App\Support\InventoryCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryItem extends Model
{
    use BelongsToIspTenant;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_FAULTY = 'faulty';

    public const STATUS_RETIRED = 'retired';

    protected $fillable = [
        'isp_id',
        'inventory_purchase_id',
        'name',
        'brand',
        'model',
        'serial_number',
        'mac_address',
        'category',
        'status',
        'purchase_price',
        'purchase_date',
        'warranty_expiry',
        'location',
        'notes',
        'qr_code_path',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(InventoryPurchase::class, 'inventory_purchase_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(InventoryAssignment::class, 'inventory_item_id');
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(InventoryAssignment::class, 'inventory_item_id')
            ->where('is_active', true);
    }

    public function technicianAssignments(): HasMany
    {
        return $this->hasMany(InventoryTechnicianAssignment::class, 'inventory_item_id');
    }

    public function activeTechnicianAssignment(): HasOne
    {
        return $this->hasOne(InventoryTechnicianAssignment::class, 'inventory_item_id')
            ->where('is_active', true);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_item_id');
    }

    public function scopeForIsp($query, int $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeAssignable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function isAssignable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE
            && ! $this->activeTechnicianAssignment()->exists()
            && ! $this->activeAssignment()->exists();
    }

    public function isIssuableToTechnician(): bool
    {
        return $this->status === self::STATUS_AVAILABLE
            && ! $this->activeTechnicianAssignment()->exists()
            && ! $this->activeAssignment()->exists();
    }

    public function categoryLabel(): string
    {
        return InventoryCatalog::categoryLabel($this->category);
    }

    public function statusLabel(): string
    {
        return InventoryCatalog::statusLabel($this->status);
    }

    public function locationLabel(): string
    {
        return InventoryCatalog::locationLabel($this->location);
    }

    public function qrCodeUrl(): ?string
    {
        if (! $this->qr_code_path) {
            return null;
        }

        return asset($this->qr_code_path);
    }
}
