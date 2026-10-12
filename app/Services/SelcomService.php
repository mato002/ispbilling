<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ISP;
use App\Models\IspPaymentMethod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
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

        $method = IspPaymentMethod::forIsp($isp->id)->where('provider', 'selcom')->first();
        if ($method) {
            $credentials = $method->credentials;
            $this->apiKey = $credentials['api_key'] ?? $this->apiKey;
            $this->apiSecret = $credentials['api_secret'] ?? $this->apiSecret;
            $this->vendorId = $credentials['vendor'] ?? $this->vendorId;
            $this->environment = $credentials['environment'] ?? $this->environment;
        }
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

    protected function baseUrl(): string
    {
        return $this->environment === 'production'
            ? 'https://apigw.selcommobile.com'
            : 'https://apigwtest.selcommobile.com';
    }

    protected function authHeaders(array $data, array $fields): array
    {
        $timestamp = now()->toIso8601String();
        $signing = 'timestamp='.$timestamp;
        foreach ($fields as $field) $signing .= '&'.$field.'='.($data[$field] ?? '');
        return [
            'Authorization' => 'SELCOM '.base64_encode($this->apiKey),
            'Digest-Method' => 'HS256',
            'Digest' => base64_encode(hash_hmac('sha256', $signing, $this->apiSecret, true)),
            'Timestamp' => $timestamp,
            'Signed-Fields' => implode(',', $fields),
            'Accept' => 'application/json',
        ];
    }

    /**
     * Initiate payment
     */
    public function initiatePayment(Payment $payment): array
    {
        try {
            $callbackUrl = route('payments.selcom.callback');
            $payload = [
                'vendor' => $this->vendorId,
                'order_id' => $payment->payment_reference,
                'buyer_email' => $payment->customer?->email ?: (($payment->phone_number ?: 'customer').'@selcom.com'),
                'buyer_name' => $payment->customer?->full_name ?: 'Internet customer',
                'buyer_phone' => $payment->phone_number ?: ($payment->customer?->phone_number ?? ''),
                'amount' => (string) $payment->amount,
                'currency' => $this->isp->currency ?? 'TZS',
                'redirect_url' => base64_encode(\Illuminate\Support\Facades\URL::temporarySignedRoute('hotspot.checkout.access', now()->addHours(24), ['payment' => $payment->id])),
                'cancel_url' => base64_encode($callbackUrl),
                'webhook' => base64_encode($callbackUrl),
                'no_of_items' => 1,
            ];
            $fields = array_keys($payload);
            $response = Http::withHeaders($this->authHeaders($payload, $fields))
                ->post($this->baseUrl().'/v1/checkout/create-order-minimal', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                $gatewayUrl = data_get($data, 'data.0.payment_gateway_url');
                if (($data['resultcode'] ?? null) === '000' && $gatewayUrl) {
                    $payment->update([
                        'transaction_id' => $payment->payment_reference,
                        'metadata' => array_merge($payment->metadata ?? [], ['selcom_response' => $data]),
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Payment initiated successfully',
                        'payment_url' => base64_decode($gatewayUrl, true) ?: $gatewayUrl,
                        'order_id' => $payment->payment_reference,
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
            $payload = ['order_id' => $orderId];
            $response = Http::withHeaders($this->authHeaders($payload, ['order_id']))
                ->get($this->baseUrl().'/v1/checkout/order-status', $payload);

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
    public function handleWebhook(array $data, ?Request $request = null): bool
    {
        try {
            $signedFields = $request?->header('Signed-Fields');
            $digest = $request?->header('Digest');
            $timestamp = $request?->header('Timestamp');
            if (! $signedFields || ! $digest || ! $timestamp || ! $this->apiSecret) return false;
            if (abs(now()->diffInSeconds(\Illuminate\Support\Carbon::parse($timestamp))) > 300) return false;
            $fields = array_filter(explode(',', $signedFields));
            $signing = 'timestamp='.$timestamp;
            foreach ($fields as $field) {
                if (! array_key_exists($field, $data)) return false;
                $signing .= '&'.$field.'='.$data[$field];
            }
            $expected = base64_encode(hash_hmac('sha256', $signing, $this->apiSecret, true));
            if (! hash_equals($expected, $digest)) {
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

