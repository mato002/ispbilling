<?php

namespace App\Http\Middleware;

use App\Models\ISP;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces ISP tenant admins to finish PrimeBilling (platform) SaaS onboarding before the main console.
 */
class EnsureSaasOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin() || ! $user->isp_id) {
            return $next($request);
        }

        if (! $user->isIspAdmin() && ! $user->isIspStaff()) {
            return $next($request);
        }

        $isp = ISP::query()->find($user->isp_id);

        if (! $isp || ! $isp->needsSaasOnboarding()) {
            return $next($request);
        }

        if ($request->routeIs('isp.saas-onboarding.*')) {
            return $next($request);
        }

        return redirect()->route('isp.saas-onboarding.show');
    }
}
