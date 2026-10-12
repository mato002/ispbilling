<?php

namespace App\Http\Controllers;

use App\Models\ISP;
use App\Models\Plan;
use App\Models\PlatformSaasPlan;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function show(Request $request): View
    {
        /** @var ISP|null $landingIsp */
        $landingIsp = $request->attributes->get('landing_isp');

        $landingPlans = collect();
        $platformSaasPlans = collect();

        if ($landingIsp) {
            $landingPlans = Plan::query()
                ->where('isp_id', $landingIsp->id)
                ->where('is_active', true)
                ->where('customer_visible', true)
                ->whereNull('archived_at')
                ->orderBy('price')
                ->get();
        } else {
            $platformSaasPlans = PlatformSaasPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return view('welcome', [
            'landingIsp' => $landingIsp,
            'landingPlans' => $landingPlans,
            'platformSaasPlans' => $platformSaasPlans,
            'defaultTrialDays' => max(0, PlatformSetting::getInt('saas_default_trial_days', 14)),
        ]);
    }
}
