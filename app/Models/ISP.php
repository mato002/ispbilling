<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ISP extends Model
{
    protected $table = 'i_s_p_s';

    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'logo',
        'currency',
        'timezone',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function routers(): HasMany
    {
        return $this->hasMany(Router::class, 'isp_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'isp_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class, 'isp_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'isp_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'isp_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'isp_id');
    }

    public function activeCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'isp_id')->where('status', 'active');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'isp_id');
    }
}
