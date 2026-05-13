<?php

namespace App\Models\Scopes;

use App\Support\IspTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class IspTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Console: never apply (queues, artisan, schedule). Web user resolution uses
        // User::withoutGlobalScopes() in IspTenant to avoid recursion with User's own tenant scope.
        if (app()->runningInConsole()) {
            return;
        }

        if (! IspTenant::shouldScopeQueries()) {
            return;
        }

        $tenantId = IspTenant::id();
        if ($tenantId === null) {
            return;
        }

        $column = $model->qualifyColumn('isp_id');

        $builder->where($column, $tenantId);
    }
}
