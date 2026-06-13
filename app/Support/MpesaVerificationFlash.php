<?php

namespace App\Support;

use App\Models\ISP;
use Illuminate\Http\RedirectResponse;

/**
 * Structured flash payload for M-Pesa verification pending UI (local dev details vs production copy).
 */
class MpesaVerificationFlash
{
    public const MODAL_TYPE = 'mpesa_verification_pending';

    public static function productionMessage(): string
    {
        return 'We haven\'t received the automatic payment confirmation from M-Pesa yet. Please ensure you have entered the correct PIN on your handset. If you\'ve already completed the payment, please wait a moment and try again, or contact support if the issue persists.';
    }

    public static function localUserMessage(): string
    {
        return 'We haven\'t received the M-Pesa callback on this machine yet. Approve the STK on your phone, or use Skip callback (local) on the form if you are developing without a public URL.';
    }

    public static function redirectBackPendingVerification(ISP $isp, bool $hasPendingVerification): RedirectResponse
    {
        $message = app()->environment('local')
            ? self::localUserMessage()
            : self::productionMessage();

        $modal = [
            'type' => self::MODAL_TYPE,
            'title' => 'Payment Verification Pending',
            'message' => $message,
        ];

        if (app()->environment('local')) {
            $amount = max(1, (int) ceil((float) config('services.mpesa.saas_onboarding_verification_amount', 1)));
            $callbackUrl = config('services.mpesa.callback_url') ?: route('payments.mpesa.callback');
            $modal['developer_details'] = self::localDeveloperDetails($isp, $amount, $hasPendingVerification, $callbackUrl);

            if (config('services.mpesa.environment', 'sandbox') === 'sandbox') {
                $modal['developer_details']['steps'] = array_merge(
                    [
                        'Daraja sandbox only delivers STK to the test MSISDN 254708374149 (or your portal test number) — not real phones.',
                        'Enter 254708374149 as M-Pesa billing number and Save & continue to receive a sandbox prompt.',
                    ],
                    $modal['developer_details']['steps'] ?? []
                );
            }
        }

        return back()->with([
            'error' => $message,
            'flash_modal' => $modal,
        ]);
    }

    public static function redirectBackNoPendingPayment(ISP $isp): RedirectResponse
    {
        $message = app()->environment('local')
            ? 'No pending verification payment found. Save this step with M-Pesa first to send the STK, then use Skip callback (local) or the developer steps below.'
            : 'No verification payment was found. Please save your billing details with M-Pesa selected to receive a verification prompt on your phone.';

        $modal = [
            'type' => self::MODAL_TYPE,
            'title' => 'Payment Verification Pending',
            'message' => $message,
        ];

        if (app()->environment('local')) {
            $modal['developer_details'] = [
                'summary' => 'Send a new STK by saving step 2 with M-Pesa billing selected.',
                'steps' => [
                    'Save step 2 with M-Pesa as your billing method (this sends the STK).',
                    'Click Skip callback (local) on the form, or run: php artisan mpesa:simulate-onboarding-verification '.$isp->id.' --force',
                ],
            ];
        }

        return back()->with([
            'error' => $message,
            'flash_modal' => $modal,
        ]);
    }

    /**
     * @return array{summary: string, steps: array<int, string>, callback_url?: string}
     */
    protected static function localDeveloperDetails(
        ISP $isp,
        int $amount,
        bool $hasPendingVerification,
        string $callbackUrl
    ): array {
        $summary = $hasPendingVerification
            ? "A KES {$amount} verification payment is still pending — approving on your phone is not enough until Safaricom can POST to your public callback URL."
            : "No completed callback recorded for the KES {$amount} verification yet.";

        return [
            'summary' => $summary,
            'steps' => [
                'Keep ngrok pointed at your app (e.g. ngrok http 80).',
                'Run .\\scripts\\sync-ngrok-env.ps1 then php artisan config:clear.',
                'Submit step 2 again so a new STK uses the updated callback URL.',
                'Open http://127.0.0.1:4040 and confirm POST /api/payments/mpesa/callback.',
                'Or choose Bank or Card above to skip platform M-Pesa.',
                'Click Skip callback (local) on this form, or run: php artisan mpesa:simulate-onboarding-verification '.$isp->id.' --force',
            ],
            'callback_url' => $callbackUrl,
        ];
    }
}
