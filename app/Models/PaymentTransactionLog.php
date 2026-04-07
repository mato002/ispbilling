<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransactionLog extends Model
{
    protected $fillable = [
        'payment_id',
        'isp_id',
        'transaction_type',
        'payment_method',
        'payment_reference',
        'transaction_id',
        'status',
        'amount',
        'request_data',
        'response_data',
        'ip_address',
        'user_agent',
        'error_message',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    /**
     * Log a transaction
     */
    public static function log(array $data): self
    {
        return self::create([
            'payment_id' => $data['payment_id'] ?? null,
            'isp_id' => $data['isp_id'] ?? null,
            'transaction_type' => $data['transaction_type'] ?? 'unknown',
            'payment_method' => $data['payment_method'] ?? 'unknown',
            'payment_reference' => $data['payment_reference'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'status' => $data['status'] ?? null,
            'amount' => $data['amount'] ?? null,
            'request_data' => $data['request_data'] ?? null,
            'response_data' => $data['response_data'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'error_message' => $data['error_message'] ?? null,
        ]);
    }
}
