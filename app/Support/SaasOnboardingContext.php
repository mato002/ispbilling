<?php

namespace App\Support;

use App\Models\ISP;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Resolves which ISP tenant is being onboarded (ISP admin vs platform super admin).
 */
class SaasOnboardingContext
{
    public const SESSION_ISP_KEY = 'saas_onboarding_isp_id';

    public static function canAccess(?User $user): bool
    {
        return $user !== null && ($user->isSuperAdmin() || ($user->isIspAdmin() && $user->isp_id));
    }

    public static function resolveIsp(?User $user, ?Request $request = null): ?ISP
    {
        if ($user === null) {
            return null;
        }

        $request ??= request();

        if ($user->isIspAdmin() && $user->isp_id) {
            return ISP::query()->find($user->isp_id);
        }

        if (! $user->isSuperAdmin()) {
            return null;
        }

        $ispId = $request->query('isp') ?? $request->session()->get(self::SESSION_ISP_KEY);

        if (! $ispId) {
            return null;
        }

        $ispId = (int) $ispId;
        $request->session()->put(self::SESSION_ISP_KEY, $ispId);

        return ISP::query()->find($ispId);
    }

    public static function showUrl(ISP $isp): string
    {
        return route('isp.saas-onboarding.show', ['isp' => $isp->id]);
    }

    public static function completionRedirect(ISP $isp): RedirectResponse
    {
        $user = auth()->user();

        if ($user && $user->isSuperAdmin()) {
            return redirect()
                ->route('admin.isps.show', $isp)
                ->with('success', 'PrimeBilling SaaS onboarding completed for '.$isp->name.'.');
        }

        return redirect()
            ->route('isp.dashboard')
            ->with('success', 'Welcome to PrimeBilling — your network, plans, and M-Pesa C2B are configured.');
    }
}
