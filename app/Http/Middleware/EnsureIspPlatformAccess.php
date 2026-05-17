<?php

namespace App\Http\Middleware;

use App\Models\ISP;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks ISP staff when the tenant is suspended for SaaS / platform billing.
 */
class EnsureIspPlatformAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin() || ! $user->isp_id) {
            return $next($request);
        }

        $isp = $request->attributes->get(LoadTenantIsp::ATTRIBUTE)
            ?? ISP::query()->find($user->isp_id);

        if ($isp && $isp->platform_access_locked) {
            if ($request->routeIs('isp.platform-subscription.*')) {
                return $next($request);
            }

            return response()
                ->view('isp.platform-access-locked', [
                    'isp' => $isp,
                    'message' => $isp->platform_access_message,
                ], 403);
        }

        return $next($request);
    }
}
