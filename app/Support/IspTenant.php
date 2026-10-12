<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\SessionGuard;

/**
 * Resolves the current ISP tenant for authenticated ISP staff/admin.
 * Used by {@see \App\Models\Scopes\IspTenantScope} — not used for customers, webhooks, or the console.
 *
 * Never call {@see auth()->user()} or {@see auth()->check()} here: the {@see User} model uses the
 * tenant global scope, and the session guard loads the user via a scoped query, which would recurse.
 * We read the session user id from {@see SessionGuard} and load with {@see User::withoutGlobalScopes()}.
 */
class IspTenant
{
    private const REQUEST_RESOLVED_USER_KEY = '_isp_tenant_resolved_auth_user';

    public static function shouldScopeQueries(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        $user = self::resolveAuthenticatedUser();

        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return SuperAdminWorkspaceContext::ispId() !== null;
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

        $user = self::resolveAuthenticatedUser();

        if ($user && $user->isSuperAdmin()) {
            return SuperAdminWorkspaceContext::ispId();
        }

        return $user && $user->isp_id ? (int) $user->isp_id : null;
    }

    /**
     * Default isp_id when creating models as ISP staff (super admin excluded).
     */
    public static function defaultIspIdForCreating(): ?int
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $user = self::resolveAuthenticatedUser();

        if ($user === null) {
            return null;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return SuperAdminWorkspaceContext::ispId();
        }

        return $user->isp_id ? (int) $user->isp_id : null;
    }

    /**
     * Current web user for tenant decisions, without triggering scoped {@see User} queries.
     */
    public static function resolveAuthenticatedUser(): ?User
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $request = request();
        if ($request !== null && $request->attributes->has(self::REQUEST_RESOLVED_USER_KEY)) {
            return $request->attributes->get(self::REQUEST_RESOLVED_USER_KEY);
        }

        $guard = auth()->guard();

        $user = null;

        if ($guard instanceof SessionGuard) {
            $id = $guard->getSession()?->get($guard->getName());
            if ($id !== null) {
                $user = User::withoutGlobalScopes()->find($id);
            }
        }

        $request?->attributes->set(self::REQUEST_RESOLVED_USER_KEY, $user);

        return $user;
    }
}
