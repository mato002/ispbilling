<?php

namespace App\Http\Middleware;

use App\Models\ISP;
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

        if ($user && $user->isp_id && ! $request->attributes->get(self::ATTRIBUTE)) {
            $request->attributes->set(
                self::ATTRIBUTE,
                ISP::query()->find($user->isp_id)
            );
        }

        return $next($request);
    }
}
