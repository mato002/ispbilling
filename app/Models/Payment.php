<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'isp_id',
        'customer_id',
        'invoice_id',
        'voucher_id',
        'payment_reference',
        'payment_method',
        'amount',
        'transaction_id',
        'phone_number',
        'status',
        'notes',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        if ($this->invoice) {
            $this->invoice->markAsPaid();
        }

        // Log payment success in ActivityLog (if user context available)
        if (auth()->check()) {
            $invoiceNumber = $this->invoice ? $this->invoice->invoice_number : 'N/A';
            \App\Services\ActivityLogService::logAction(
                'payment_success',
                "Payment completed: {$this->payment_reference} for invoice {$invoiceNumber}",
                [
                    'payment_id' => $this->id,
                    'invoice_id' => $this->invoice_id,
                    'customer_id' => $this->customer_id,
                    'amount' => $this->amount,
                    'payment_method' => $this->payment_method,
                ]
            );
        }

        // Dispatch job to reactivate user (handles MikroTik reactivation and subscription renewal)
        // This ensures reactivation happens asynchronously via queue
        \App\Jobs\ReactivatePaidUsers::dispatch($this);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);

        // Log payment failure in ActivityLog (if user context available)
        if (auth()->check()) {
            $invoiceNumber = $this->invoice ? $this->invoice->invoice_number : 'N/A';
            \App\Services\ActivityLogService::logAction(
                'payment_failure',
                "Payment failed: {$this->payment_reference} for invoice {$invoiceNumber}",
                [
                    'payment_id' => $this->id,
                    'invoice_id' => $this->invoice_id,
                    'customer_id' => $this->customer_id,
                    'amount' => $this->amount,
                    'payment_method' => $this->payment_method,
                ]
            );
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = static::generatePaymentReference();
            }
        });
    }

    protected static function generatePaymentReference(): string
    {
        return 'PAY-' . strtoupper(Str::random(12));
    }
}
