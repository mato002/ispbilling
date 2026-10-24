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
    private const PUBLIC_PAGES = [
        'features' => [
            'title' => 'Features',
            'headline' => 'Tools for every part of ISP operations',
            'description' => 'Manage billing, network access, customer support, field work, and reporting from one workspace.',
        ],
        'workflows' => [
            'title' => 'Workflows',
            'headline' => 'From customer payment to connected service',
            'description' => 'See how billing events, router provisioning, and customer support fit together in everyday operations.',
        ],
        'pricing' => [
            'title' => 'Pricing',
            'headline' => 'Pricing that scales with your network',
            'description' => 'Estimate usage-based costs or review the active plans available for your ISP.',
        ],
        'faq' => [
            'title' => 'Frequently asked questions',
            'headline' => 'Answers for ISP teams',
            'description' => 'Common questions about MikroTik, payments, subscriber provisioning, and getting started.',
        ],
    ];

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

    public function page(Request $request): View
    {
        $page = (string) $request->route('page');
        abort_unless(isset(self::PUBLIC_PAGES[$page]), 404);
        abort_if($request->attributes->get('landing_isp'), 404);

        return view('landing.page', [
            'landingIsp' => null,
            'page' => $page,
            'pageMeta' => self::PUBLIC_PAGES[$page],
            'platformSaasPlans' => PlatformSaasPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'defaultTrialDays' => max(0, PlatformSetting::getInt('saas_default_trial_days', 14)),
        ]);
    }
}
