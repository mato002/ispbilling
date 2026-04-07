<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ISP;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SelcomService
{
    protected $isp;
    protected $apiKey;
    protected $apiSecret;
    protected $vendorId;
    protected $environment;

    public function __construct(ISP $isp)
    {
        $this->isp = $isp;
        $settings = $isp->settings ?? [];
        
        $this->apiKey = $settings['selcom_api_key'] ?? config('services.selcom.api_key');
        $this->apiSecret = $settings['selcom_api_secret'] ?? config('services.selcom.api_secret');
        $this->vendorId = $settings['selcom_vendor_id'] ?? config('services.selcom.vendor_id');
        $this->environment = $settings['selcom_environment'] ?? config('services.selcom.environment', 'sandbox');
    }

    /**
     * Generate signature for Selcom API
     */
    protected function generateSignature(array $data): string
    {
        ksort($data);
        $string = '';
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string = rtrim($string, '&');
        return hash_hmac('sha256', $string, $this->apiSecret);
    }

    /**
     * Initiate payment
     */
    public function initiatePayment(Payment $payment): array
    {
        try {
            $baseUrl = $this->environment === 'production'
                ? 'https://api.selcommobile.com/v1'
                : 'https://api.selcommobile.com/v1';

            $callbackUrl = route('payments.selcom.callback');

            $payload = [
                'vendor' => $this->vendorId,
                'order_id' => $payment->payment_reference,
                'buyer_email' => $payment->customer->email ?? $payment->phone_number . '@selcom.com',
                'buyer_name' => $payment->customer->full_name,
                'buyer_phone' => $payment->phone_number ?? $payment->customer->phone_number,
                'amount' => (string) $payment->amount,
                'currency' => $this->isp->currency ?? 'TZS',
                'redirect_url' => $callbackUrl,
                'cancel_url' => $callbackUrl,
                'webhook' => $callbackUrl,
            ];

            $payload['signature'] = $this->generateSignature($payload);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            ])->post($baseUrl . '/checkout/create-order', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']['order_id'])) {
                    $payment->update([
                        'transaction_id' => $data['data']['order_id'] ?? null,
                        'metadata' => $data,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Payment initiated successfully',
                        'payment_url' => $data['data']['payment_gateway_url'] ?? null,
                        'order_id' => $data['data']['order_id'] ?? null,
                    ];
                }

                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Payment initiation failed',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $response->body(),
            ];
        } catch (Exception $e) {
            Log::error('Selcom payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $orderId): array
    {
        try {
            $baseUrl = $this->environment === 'production'
                ? 'https://api.selcommobile.com/v1'
                : 'https://api.selcommobile.com/v1';

            $payload = [
                'vendor' => $this->vendorId,
                'order_id' => $orderId,
            ];

            $payload['signature'] = $this->generateSignature($payload);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            ])->post($baseUrl . '/checkout/order-status', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'message' => 'Verification failed'];
        } catch (Exception $e) {
            Log::error('Selcom verification error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Handle webhook callback
     */
    public function handleWebhook(array $data): bool
    {
        try {
            $signature = $data['signature'] ?? null;
            unset($data['signature']);
            
            $expectedSignature = $this->generateSignature($data);
            
            if ($signature !== $expectedSignature) {
                Log::error('Selcom webhook signature mismatch');
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Selcom webhook error: ' . $e->getMessage());
            return false;
        }
    }
}

