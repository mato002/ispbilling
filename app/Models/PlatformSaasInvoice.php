<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSaasInvoice extends Model
{
    use BelongsToIspTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'isp_id',
        'platform_saas_plan_id',
        'invoice_number',
        'subscription_amount',
        'setup_fee_amount',
        'currency',
        'period_start',
        'period_end',
        'due_date',
        'status',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subscription_amount' => 'decimal:2',
            'setup_fee_amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlatformSaasPlan::class, 'platform_saas_plan_id');
    }

    public function totalAmount(): float
    {
        return (float) $this->subscription_amount + (float) $this->setup_fee_amount;
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'PB-SAAS-'.now()->format('Ym').'-';

        $last = static::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
