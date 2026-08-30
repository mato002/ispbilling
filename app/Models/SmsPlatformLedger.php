<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsPlatformLedger extends Model
{
    use HasUuids;

    public const TYPE_TOPUP = 'topup';

    public const TYPE_DEDUCTION = 'deduction';

    protected $table = 'sms_platform_ledgers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'amount',
        'type',
        'sms_count',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'sms_count' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'tenant_id');
    }
}
