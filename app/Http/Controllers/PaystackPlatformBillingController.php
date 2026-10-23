<?php

namespace App\Http\Controllers;

use App\Models\PlatformSaasPaymentAttempt;
use App\Models\PlatformIntegration;
use App\Services\PlatformSaasBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaystackPlatformBillingController extends Controller
{
    public function callback(Request $request, PlatformSaasBillingService $billing): RedirectResponse
    {
        $reference = (string) $request->query('reference', '');
        $attempt = PlatformSaasPaymentAttempt::query()->where('reference', $reference)->first();
        $paid = $attempt && $billing->verifyAndApplyPaystackPayment($reference);

        if (auth()->check() && (int) auth()->user()->isp_id === (int) ($attempt?->isp_id)) {
            return redirect()->route('isp.platform-subscription.index')->with(
                $paid ? 'success' : 'error',
                $paid ? 'Payment confirmed. Your platform access is active.' : 'Payment is not confirmed yet. Refresh your subscription page shortly.'
            );
        }

        return redirect()->route('login')->with(
            $paid ? 'status' : 'error',
            $paid ? 'Payment confirmed. Sign in to continue.' : 'Payment is still being confirmed. Sign in and check your subscription status shortly.'
        );
    }

    public function webhook(Request $request, PlatformSaasBillingService $billing): Response
    {
        $vault = PlatformIntegration::current()->credentials_vault ?? [];
        $secret = (string) ($vault['paystack_secret_key'] ?? config('services.paystack.secret_key', ''));
        $signature = (string) $request->header('x-paystack-signature', '');
        $expected = $secret !== '' ? hash_hmac('sha512', $request->getContent(), $secret) : '';
        if ($expected === '' || $signature === '' || ! hash_equals($expected, $signature)) {
            return response('Invalid signature', 401);
        }

        if ($request->input('event') !== 'charge.success') {
            return response()->noContent();
        }

        $reference = (string) $request->input('data.reference', '');
        $attempt = PlatformSaasPaymentAttempt::query()->where('reference', $reference)->first();
        if (! $attempt || $attempt->status === 'paid') {
            return response()->noContent();
        }

        return $billing->verifyAndApplyPaystackPayment($reference)
            ? response()->noContent()
            : response('Payment verification pending', 503);
    }
}
