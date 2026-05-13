<?php

namespace App\Support;

/**
 * Resolves the current ISP tenant for authenticated ISP staff/admin.
 * Used by {@see \App\Models\Scopes\IspTenantScope} — not used for customers, webhooks, or the console.
 */
class IspTenant
{
    public static function shouldScopeQueries(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return false;
        }

        return (bool) ($user->isp_id ?? null);
    }

    /**
     * ISP id for query scoping (only when {@see shouldScopeQueries()} is true).
     */
    public static function id(): ?int
    {
        if (! self::shouldScopeQueries()) {
            return null;
        }

        return (int) auth()->user()->isp_id;
    }

    /**
     * Default isp_id when creating models as ISP staff (super admin excluded).
     */
    public static function defaultIspIdForCreating(): ?int
    {
        if (! auth()->check()) {
            return null;
        }

        $user = auth()->user();

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return null;
        }

        return $user->isp_id ? (int) $user->isp_id : null;
    }
}
