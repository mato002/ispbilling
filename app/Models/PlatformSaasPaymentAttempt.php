<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSaasPaymentAttempt extends Model
{
    protected $fillable = [
        'isp_id', 'platform_saas_invoice_id', 'reference', 'amount', 'currency',
        'status', 'gateway_transaction_id', 'authorization_url', 'metadata', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformSaasInvoice::class, 'platform_saas_invoice_id');
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }
}
