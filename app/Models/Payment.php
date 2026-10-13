<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use BelongsToIspTenant;

    public const POSTING_POSTED = 'posted';

    public const POSTING_UNPOSTED = 'unposted';

    protected $fillable = [
        'isp_id',
        'customer_id',
        'invoice_id',
        'voucher_id',
        'payment_reference',
        'payment_method',
        'amount',
        'platform_fee_amount',
        'platform_fee_rate',
        'transaction_id',
        'phone_number',
        'status',
        'posting_status',
        'assigned_by',
        'assigned_at',
        'notes',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'platform_fee_rate' => 'decimal:4',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'assigned_at' => 'datetime',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopePosted($query)
    {
        return $query->where('posting_status', self::POSTING_POSTED);
    }

    public function scopeUnposted($query)
    {
        return $query->where('posting_status', self::POSTING_UNPOSTED);
    }

    public function isUnposted(): bool
    {
        return $this->posting_status === self::POSTING_UNPOSTED;
    }

    public function isPlatformSaasPayment(): bool
    {
        return ($this->metadata['purpose'] ?? null) === 'platform_saas';
    }

    public function isSaasOnboardingVerification(): bool
    {
        return ($this->metadata['purpose'] ?? null) === 'saas_onboarding_verification';
    }

    public function isSmsWalletTopUp(): bool
    {
        return ($this->metadata['purpose'] ?? null) === \App\Services\PlatformSmsWalletService::PURPOSE;
    }

    /** Human-readable service category for tenant payment reporting. */
    public function serviceTypeLabel(): string
    {
        $serviceType = $this->metadata['service_type'] ?? null;

        if ($serviceType === 'hotspot' || $this->voucher_id || (! $serviceType && $this->customer?->connection_type === 'hotspot')) {
            return 'Hotspot';
        }

        return $serviceType === 'pppoe' || (! $serviceType && $this->customer?->connection_type === 'pppoe')
            ? 'PPPoE'
            : 'Unassigned';
    }

    /**
     * Locate a tenant payment row for an STK callback using the CheckoutRequestID.
     * After completion, {@see $transaction_id} holds the Mpesa receipt, so we also match metadata.
     */
    public static function findByMpesaCheckoutRequestId(?string $checkoutRequestId): ?self
    {
        if ($checkoutRequestId === null || $checkoutRequestId === '') {
            return null;
        }

        return static::query()
            ->where('transaction_id', $checkoutRequestId)
            ->orWhere('metadata->mpesa_checkout_request_id', $checkoutRequestId)
            ->orWhere('metadata->stk_push_response->CheckoutRequestID', $checkoutRequestId)
            ->first();
    }

    public function markAsCompleted(): void
    {
        $this->refresh();

        if ($this->status === 'completed') {
            return;
        }

        if ($this->isPlatformSaasPayment()) {
            $this->update([
                'status' => 'completed',
                'processed_at' => now(),
                'platform_fee_rate' => null,
                'platform_fee_amount' => null,
            ]);

            return;
        }

        if ($this->isSaasOnboardingVerification()) {
            $this->update([
                'status' => 'completed',
                'processed_at' => now(),
                'platform_fee_rate' => null,
                'platform_fee_amount' => null,
            ]);

            return;
        }

        if ($this->isSmsWalletTopUp()) {
            $this->update([
                'status' => 'completed',
                'processed_at' => now(),
                'platform_fee_rate' => null,
                'platform_fee_amount' => null,
            ]);

            return;
        }

        $rate = PlatformSetting::getDecimal('platform_commission_percent', 0.0);
        $feeAmount = null;
        $feeRate = null;
        if ($rate > 0 && $this->isp_id) {
            $calc = round((float) $this->amount * ($rate / 100), 2);
            if ($calc > 0) {
                $feeRate = $rate;
                $feeAmount = $calc;
            }
        }

        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'platform_fee_rate' => $feeRate,
            'platform_fee_amount' => $feeAmount,
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

            if (empty($payment->posting_status)) {
                $payment->posting_status = ($payment->customer_id === null && $payment->status === 'completed')
                    ? self::POSTING_UNPOSTED
                    : self::POSTING_POSTED;
            }

            // Snapshot the service category on the payment so later customer
            // edits cannot relabel historical revenue in admin reports.
            $metadata = $payment->metadata ?? [];
            if (empty($metadata['service_type'])) {
                if (! empty($payment->voucher_id)) {
                    $metadata['service_type'] = 'hotspot';
                } elseif (! empty($payment->customer_id)) {
                    $connectionType = Customer::query()->whereKey($payment->customer_id)->value('connection_type');
                    if (in_array($connectionType, ['hotspot', 'pppoe'], true)) {
                        $metadata['service_type'] = $connectionType;
                    }
                }
            }
            $payment->metadata = $metadata;
        });
    }

    protected static function generatePaymentReference(): string
    {
        return 'PAY-'.strtoupper(Str::random(12));
    }
}
