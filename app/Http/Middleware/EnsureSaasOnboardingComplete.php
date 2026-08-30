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

        $isp = $request->attributes->get(LoadTenantIsp::ATTRIBUTE)
            ?? ISP::query()->find($user->isp_id);

        if (! $isp || ! $isp->needsSaasOnboarding()) {
            return $next($request);
        }

        if (! $user->isIspAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'SaaS onboarding must be completed by your ISP administrator.',
                    'code' => 'saas_onboarding_pending_staff',
                ], 403);
            }

            return response()
                ->view('isp.saas-onboarding-pending-staff', ['isp' => $isp], 403);
        }

        // Let the real dashboard render so setup can overlay it; lock other console routes.
        if ($request->routeIs('isp.dashboard')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Complete platform SaaS onboarding before using the API.',
                'code' => 'saas_onboarding_required',
                'onboarding_url' => route('isp.dashboard'),
            ], 403);
        }

        return redirect()->route('isp.dashboard');
    }
}
