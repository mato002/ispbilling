<?php

namespace App\Models\Concerns;

use App\Models\Scopes\IspTenantScope;
use App\Support\IspTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Global scope: all queries for ISP staff/admin are restricted to their isp_id.
 * Super admins, guests, console, and queue workers (no auth) see unscoped data.
 */
trait BelongsToIspTenant
{
    public static function bootBelongsToIspTenant(): void
    {
        static::addGlobalScope('isp_tenant', new IspTenantScope);

        static::creating(function (Model $model) {
            if (! $model->isFillable('isp_id')) {
                return;
            }

            $current = $model->getAttribute('isp_id');
            if ($current !== null && $current !== '') {
                return;
            }

            $default = IspTenant::defaultIspIdForCreating();
            if ($default !== null) {
                $model->setAttribute('isp_id', $default);
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutIspTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('isp_tenant');
    }
}
