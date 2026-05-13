<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Invoice extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'customer_id',
        'subscription_id',
        'invoice_number',
        'amount',
        'tax',
        'total_amount',
        'issue_date',
        'due_date',
        'paid_date',
        'status',
        'penalty',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'penalty' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date < now() && $this->status === 'pending';
    }

    public function markAsPaid(): void
    {
        if ($this->status === 'paid') {
            return;
        }

        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
        ]);
    }

    public function calculatePenalty(float $penaltyRate = 0.05): float
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        $daysOverdue = now()->diffInDays($this->due_date);
        return $this->total_amount * $penaltyRate * $daysOverdue;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->isp_id);
            }
        });
    }

    protected static function generateInvoiceNumber(int $ispId): string
    {
        $isp = ISP::find($ispId);
        $prefix = $isp ? Str::upper(Str::substr($isp->name, 0, 3)) : 'INV';
        $year = now()->year;
        $number = static::where('isp_id', $ispId)
            ->whereYear('created_at', $year)
            ->count() + 1;
        return "{$prefix}-{$year}-" . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
