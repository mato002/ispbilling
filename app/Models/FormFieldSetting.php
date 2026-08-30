<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormFieldSetting extends Model
{
    protected $fillable = [
        'form_setting_id',
        'field_key',
        'is_required',
        'is_visible',
        'is_hidden',
        'default_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'is_hidden' => 'boolean',
            'default_value' => 'array',
        ];
    }

    public function formSetting(): BelongsTo
    {
        return $this->belongsTo(FormSetting::class);
    }
}
