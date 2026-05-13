<?php

namespace App\Http\Controllers;

use App\Models\IspPaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Safaricom C2B validation / confirmation callbacks (registered per ISP shortcode).
 */
class MpesaC2BWebhookController extends Controller
{
    public function validation(Request $request, string $webhookToken): JsonResponse
    {
        $this->logC2B('validation', $webhookToken, $request->all());

        if (! $this->tokenValid($webhookToken)) {
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
        $this->logC2B('confirmation', $webhookToken, $request->all());

        if (! $this->tokenValid($webhookToken)) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Unauthorized'], 403);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    protected function tokenValid(string $webhookToken): bool
    {
        return IspPaymentMethod::query()
            ->where('provider', 'daraja')
            ->where('webhook_token', $webhookToken)
            ->exists();
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
