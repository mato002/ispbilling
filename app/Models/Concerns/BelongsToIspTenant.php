<?php

namespace App\Models\Concerns;

use App\Models\Scopes\IspTenantScope;
use App\Support\IspTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Global scope: HTTP requests only — ISP staff/admin queries are restricted to their isp_id.
 * Super admins, guests, console, and queue workers see unscoped data.
 * Tenant context is resolved via {@see \App\Support\IspTenant::resolveAuthenticatedUser()} (session id +
 * User::withoutGlobalScopes()) so loading the authenticated user does not recurse through this scope.
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
