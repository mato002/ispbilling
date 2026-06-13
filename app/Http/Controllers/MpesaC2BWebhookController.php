<?php

namespace App\Http\Controllers;

use App\Models\IspPaymentMethod;
use App\Support\MpesaGatewayConfig;
use App\Services\MpesaC2BReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Safaricom C2B validation / confirmation callbacks (registered per ISP shortcode).
 */
class MpesaC2BWebhookController extends Controller
{
    public function __construct(
        protected MpesaC2BReconciliationService $c2bReconciliationService
    ) {
    }

    public function validation(Request $request, string $webhookToken): JsonResponse
    {
        $this->logC2B('validation', $webhookToken, $request->all());

        if (! $this->paymentMethodForToken($webhookToken)) {
            return response()->json([
                'ResultCode' => 'C2B00011',
                'ResultDesc' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'ResultCode' => '0',
            'ResultDesc' => 'Accepted',
        ]);
    }

    public function confirmation(Request $request, string $webhookToken): JsonResponse
    {
        $payload = $request->all();
        $this->logC2B('confirmation', $webhookToken, $payload);

        $paymentMethod = $this->paymentMethodForToken($webhookToken);

        if (! $paymentMethod) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Unauthorized'], 403);
        }

        try {
            $this->c2bReconciliationService->processConfirmation(
                $paymentMethod,
                $payload,
                $request->ip()
            );
        } catch (\Throwable $e) {
            Log::error('M-Pesa C2B confirmation processing failed: '.$e->getMessage(), [
                'isp_id' => $paymentMethod->isp_id,
                'trans_id' => $payload['TransID'] ?? null,
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    protected function paymentMethodForToken(string $webhookToken): ?IspPaymentMethod
    {
        return IspPaymentMethod::query()
            ->mpesaProvider()
            ->where('webhook_token', $webhookToken)
            ->where('active', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function logC2B(string $kind, string $webhookToken, array $payload): void
    {
        Log::info('M-Pesa C2B '.$kind, [
            'webhook_token_prefix' => substr($webhookToken, 0, 8),
            'payload' => $payload,
        ]);
    }
}
