<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaasOnboardingCompleteRequest;
use App\Models\ISP;
use App\Models\PlatformSaasPlan;
use App\Services\PlatformSaasBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IspSaasOnboardingController extends Controller
{
    public function show(ISP $isp): View|RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $isp->needsSaasOnboarding()) {
            return redirect()
                ->route('admin.isps.show', $isp)
                ->with('info', 'This ISP has already completed PrimeBilling SaaS onboarding.');
        }

        $plans = PlatformSaasPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.isps.saas-onboarding', compact('isp', 'plans'));
    }

    public function store(SaasOnboardingCompleteRequest $request, ISP $isp, PlatformSaasBillingService $billing): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $isp->needsSaasOnboarding()) {
            return redirect()
                ->route('admin.isps.show', $isp)
                ->with('info', 'This ISP has already completed PrimeBilling SaaS onboarding.');
        }

        $plan = PlatformSaasPlan::query()->where('is_active', true)->findOrFail($request->integer('platform_saas_plan_id'));

        $billing->completeOnboarding($isp, $plan, $request->billingPayload(), $request->boolean('saas_white_label_opt_in'));

        return redirect()
            ->route('admin.isps.show', $isp)
            ->with('success', 'PrimeBilling SaaS onboarding saved for '.$isp->name.' (same data as self-service onboarding).');
    }
}
