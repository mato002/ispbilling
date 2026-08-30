<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSetting extends Model
{
    protected $fillable = [
        'isp_id',
        'form_key',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormFieldSetting::class)->orderBy('sort_order');
    }
}
