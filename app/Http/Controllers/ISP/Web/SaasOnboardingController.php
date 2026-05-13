<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\PlatformSaasPlan;
use App\Services\PlatformSaasBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaasOnboardingController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isp_id, 403);
        abort_unless($user->isIspAdmin(), 403);

        $isp = ISP::query()->with('saasPlan')->findOrFail($user->isp_id);

        if (! $isp->needsSaasOnboarding()) {
            return redirect()->route('isp.dashboard');
        }

        $plans = PlatformSaasPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('isp.saas-onboarding.show', compact('isp', 'plans'));
    }

    public function store(Request $request, PlatformSaasBillingService $billing): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isp_id, 403);
        abort_unless($user->isIspAdmin(), 403);

        $isp = ISP::query()->findOrFail($user->isp_id);

        if (! $isp->needsSaasOnboarding()) {
            return redirect()->route('isp.dashboard');
        }

        $validated = $request->validate([
            'platform_saas_plan_id' => ['required', 'exists:platform_saas_plans,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'saas_company_pin' => ['nullable', 'string', 'max:32'],
            'saas_billing_phone' => ['required', 'string', 'max:20'],
            'saas_billing_method' => ['nullable', 'in:mpesa,bank'],
            'saas_white_label_opt_in' => ['nullable', 'boolean'],
        ]);

        $plan = PlatformSaasPlan::query()->where('is_active', true)->findOrFail($validated['platform_saas_plan_id']);

        $billing->completeOnboarding($isp, $plan, [
            'company_name' => $validated['company_name'],
            'saas_company_pin' => $validated['saas_company_pin'] ?? null,
            'saas_billing_phone' => $validated['saas_billing_phone'],
            'saas_billing_method' => $validated['saas_billing_method'] ?? 'mpesa',
        ], $request->boolean('saas_white_label_opt_in'));

        return redirect()->route('isp.dashboard')->with('success', 'Your PrimeBilling plan is active. Enjoy your trial period.');
    }
}
