<?php

namespace App\Http\Middleware;

use App\Models\ISP;
use App\Support\SaasOnboardingContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's ISP once per request for tenant routes.
 * Other middleware and view composers reuse {@see Request::attributes} key `tenant_isp`.
 */
class LoadTenantIsp
{
    public const ATTRIBUTE = 'tenant_isp';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $request->attributes->get(self::ATTRIBUTE) && $user) {
            if ($user->isp_id) {
                $request->attributes->set(self::ATTRIBUTE, ISP::query()->find($user->isp_id));
            } elseif ($user->isSuperAdmin()) {
                $isp = SaasOnboardingContext::resolveIsp($user, $request);
                if ($isp) {
                    $request->attributes->set(self::ATTRIBUTE, $isp);
                }
            }
        }

        return $next($request);
    }
}
