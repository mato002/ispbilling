<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCategoryThreshold extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'category',
        'low_stock_threshold',
    ];

    protected $casts = [
        'low_stock_threshold' => 'integer',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function scopeForIsp($query, int $ispId)
    {
        return $query->where('isp_id', $ispId);
    }
}
