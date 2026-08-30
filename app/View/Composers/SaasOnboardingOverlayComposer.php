<?php

namespace App\View\Composers;

use App\Http\Middleware\LoadTenantIsp;
use App\Models\ISP;
use App\Models\PlatformSaasPlan;
use App\Support\SaasOnboardingContext;
use Illuminate\View\View;

/**
 * Injects blocking SaaS workspace-setup overlay props into the admin shell.
 */
class SaasOnboardingOverlayComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user || ! SaasOnboardingContext::canAccess($user) || ! $user->isIspAdmin()) {
            $view->with('pbSaasOnboardingOverlay', false);

            return;
        }

        $isp = request()->attributes->get(LoadTenantIsp::ATTRIBUTE)
            ?? SaasOnboardingContext::resolveIsp($user);

        if (! $isp instanceof ISP || ! $isp->needsSaasOnboarding()) {
            $view->with('pbSaasOnboardingOverlay', false);

            return;
        }

        $isp->loadMissing(['saasPlan', 'pendingSaasPlan', 'routers']);

        $plans = PlatformSaasPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $verifyAmount = max(1, (int) ceil((float) config('services.mpesa.saas_onboarding_verification_amount', 1)));

        $view->with([
            'pbSaasOnboardingOverlay' => true,
            'pbSaasOnboardingIsp' => $isp,
            'pbSaasOnboardingPlans' => $plans,
            'pbSaasOnboardingVerifyAmount' => $verifyAmount,
        ]);
    }
}
