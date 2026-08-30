<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\LoadTenantIsp;
use App\Http\Requests\SaasOnboardingCompleteRequest;
use App\Models\ISP;
use App\Models\PlatformSaasPlan;
use App\Services\SaasOnboardingWizardService;
use App\Support\SaasOnboardingContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaasOnboardingController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();
        abort_unless(SaasOnboardingContext::canAccess($user), 403);

        $isp = request()->attributes->get(LoadTenantIsp::ATTRIBUTE)
            ?? SaasOnboardingContext::resolveIsp($user);

        if (! $isp instanceof ISP) {
            return redirect()
                ->route('admin.isps.index')
                ->with('info', 'Select an ISP from Admin → ISPs to run SaaS onboarding.');
        }

        $isp->loadMissing(['saasPlan', 'pendingSaasPlan', 'routers']);

        if (! $isp->needsSaasOnboarding()) {
            return $user->isSuperAdmin()
                ? redirect()->route('admin.isps.show', $isp)
                : redirect()->route('isp.dashboard');
        }

        // Tenant admins continue on the live dashboard with the locking overlay.
        if ($user->isIspAdmin() && ! $user->isSuperAdmin()) {
            return redirect()->route('isp.dashboard');
        }

        $plans = PlatformSaasPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $verifyAmount = max(1, (int) ceil((float) config('services.mpesa.saas_onboarding_verification_amount', 1)));

        return view('isp.saas-onboarding.show', compact('isp', 'plans', 'verifyAmount'));
    }

    public function store(SaasOnboardingCompleteRequest $request, SaasOnboardingWizardService $wizard): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        abort_unless(SaasOnboardingContext::canAccess($user), 403);

        $isp = SaasOnboardingContext::resolveIsp($user);
        abort_unless($isp instanceof ISP, 403);

        if (! $isp->needsSaasOnboarding()) {
            return $user->isSuperAdmin()
                ? redirect()->route('admin.isps.show', $isp)
                : redirect()->route('isp.dashboard');
        }

        return $wizard->processIspWizard($isp, $request);
    }
}
