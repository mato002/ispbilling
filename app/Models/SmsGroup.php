<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SmsGroup extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'name',
        'description',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'sms_group_customer', 'sms_group_id', 'customer_id')
            ->withTimestamps();
    }
}
