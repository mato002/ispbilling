<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\Payment;
use App\Services\SaasOnboardingWizardService;
use App\Support\SaasOnboardingContext;
use Illuminate\Http\JsonResponse;

class OnboardingPaymentStatusController extends Controller
{
    public function __construct(
        private readonly SaasOnboardingWizardService $wizard
    ) {
    }

    public function show(Payment $payment): JsonResponse
    {
        $user = auth()->user();
        abort_unless(SaasOnboardingContext::canAccess($user), 403);

        $isp = SaasOnboardingContext::resolveIsp($user);
        abort_unless($isp instanceof ISP, 403);
        abort_unless((int) $payment->isp_id === (int) $isp->id, 403);
        abort_unless($payment->isSaasOnboardingVerification(), 404);

        $this->wizard->syncSaasPaymentVerifiedAtIfOnboardingPaymentCompleted($isp);
        $this->wizard->reconcileOnboardingVerificationPayment($payment);
        $payment->refresh();
        $isp->refresh();

        $status = match ($payment->status) {
            'completed' => 'paid',
            'cancelled' => 'cancelled',
            'failed' => 'failed',
            default => 'pending',
        };

        if ($status === 'paid') {
            $isp->update([
                'saas_payment_verified_at' => $isp->saas_payment_verified_at ?? now(),
                'saas_onboarding_step' => 3,
            ]);
        }

        return response()->json([
            'status' => $status,
            'payment_status' => $payment->status,
            'redirect' => SaasOnboardingContext::showUrl($isp),
        ]);
    }
}
