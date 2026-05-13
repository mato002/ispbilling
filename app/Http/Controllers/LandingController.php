<?php

namespace App\Http\Controllers;

use App\Models\ISP;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function show(Request $request): View
    {
        /** @var ISP|null $landingIsp */
        $landingIsp = $request->attributes->get('landing_isp');

        $landingPlans = collect();
        if ($landingIsp) {
            $landingPlans = Plan::query()
                ->where('isp_id', $landingIsp->id)
                ->where('is_active', true)
                ->orderBy('price')
                ->get();
        }

        return view('welcome', [
            'landingIsp' => $landingIsp,
            'landingPlans' => $landingPlans,
        ]);
    }
}
