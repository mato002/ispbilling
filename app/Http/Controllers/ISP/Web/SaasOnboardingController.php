<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaasOnboardingCompleteRequest;
use App\Models\ISP;
use App\Models\PlatformSaasPlan;
use App\Services\SaasOnboardingWizardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaasOnboardingController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isp_id, 403);
        abort_unless($user->isIspAdmin(), 403);

        $isp = ISP::query()->with(['saasPlan', 'pendingSaasPlan'])->findOrFail($user->isp_id);

        if (! $isp->needsSaasOnboarding()) {
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

    public function store(SaasOnboardingCompleteRequest $request, SaasOnboardingWizardService $wizard): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isp_id, 403);
        abort_unless($user->isIspAdmin(), 403);

        $isp = ISP::query()->findOrFail($user->isp_id);

        if (! $isp->needsSaasOnboarding()) {
            return redirect()->route('isp.dashboard');
        }

        return $wizard->processIspWizard($isp, $request);
    }
}
