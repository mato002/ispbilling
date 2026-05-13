<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformSaasPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_customers',
        'monthly_price',
        'setup_fee',
        'trial_days',
        'features',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_customers' => 'integer',
            'monthly_price' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'trial_days' => 'integer',
            'features' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isps(): HasMany
    {
        return $this->hasMany(ISP::class, 'platform_saas_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PlatformSaasInvoice::class, 'platform_saas_plan_id');
    }

    public function hasFeature(string $key): bool
    {
        return (bool) ($this->features[$key] ?? false);
    }

    public function allowsCustomerCount(int $count): bool
    {
        if ($this->max_customers === null) {
            return true;
        }

        return $count <= $this->max_customers;
    }
}
