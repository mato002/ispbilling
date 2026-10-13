<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAdjustment extends Model
{
    protected $fillable = [
        'invoice_id', 'user_id', 'type', 'amount', 'before_total', 'after_total', 'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'before_total' => 'decimal:2',
        'after_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
