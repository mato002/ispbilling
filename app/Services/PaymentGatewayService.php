<?php

namespace App\Services;

use App\Models\IspPaymentMethod;
use App\Models\Payment;
use App\Support\MpesaGatewayConfig;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    private $ispId;
    private $paymentMethods;

    public function __construct(int $ispId)
    {
        $this->ispId = $ispId;
        $this->paymentMethods = IspPaymentMethod::forIsp($ispId)->active()->get()->keyBy(function (IspPaymentMethod $method) {
            return MpesaGatewayConfig::normalizeProviderForCheckout($method->provider);
        });
    }

    /**
     * Get available payment methods for ISP
     */
    public function getAvailableMethods(): array
    {
        return $this->paymentMethods->map(function ($method) {
            return [
                'provider' => MpesaGatewayConfig::normalizeProviderForCheckout($method->provider),
                'name' => $this->getProviderName($method->provider),
                'environment' => $method->getCredential('environment', 'production'),
                'callback_url' => $method->callback_url,
            ];
        })->values()->toArray();
    }

    /**
     * Initiate payment with specified provider
     */
    public function initiatePayment(string $provider, array $data): array
    {
        $provider = MpesaGatewayConfig::normalizeProviderForCheckout($provider);

        if (! $this->paymentMethods->has($provider)) {
            return [
                'success' => false,
                'message' => 'Payment method not available',
            ];
        }

        $paymentMethod = $this->paymentMethods->get($provider);

        switch ($provider) {
            case 'mpesa':
                return $this->initiateMpesaPayment($paymentMethod, $data);
            case 'paypal':
                return $this->initiatePaypalPayment($paymentMethod, $data);
            case 'stripe':
                return $this->initiateStripePayment($paymentMethod, $data);
            case 'flutterwave':
                return $this->initiateFlutterwavePayment($paymentMethod, $data);
            case 'selcom':
                return $this->initiateSelcomPayment($paymentMethod, $data);
            default:
                return [
                    'success' => false,
                    'message' => 'Unsupported payment provider'
                ];
        }
    }

    /**
     * Initiate M-Pesa payment using existing service
     */
    private function initiateMpesaPayment(IspPaymentMethod $paymentMethod, array $data): array
    {
        try {
            // Create payment record
            $payment = Payment::create([
                'isp_id' => $this->ispId,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_method' => 'mpesa',
                'amount' => $data['amount'],
                'phone_number' => $data['phone_number'],
                'status' => 'pending',
                'metadata' => $data['metadata'] ?? [],
            ]);

            // Use existing M-Pesa service with ISP settings
            $mpesaService = new \App\Services\MpesaService(\App\Models\ISP::find($this->ispId));
            $result = $mpesaService->initiateStkPush($payment);

            if ($result['success']) {
                $payment->update([
                    'transaction_id' => $result['checkout_request_id'] ?? null,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'stk_push_response' => $result
                    ])
                ]);
            }

            return array_merge($result, [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference
            ]);

        } catch (\Exception $e) {
            Log::error('M-Pesa payment initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initiate PayPal payment
     */
    private function initiatePaypalPayment(IspPaymentMethod $paymentMethod, array $data): array
    {
        try {
            $credentials = $paymentMethod->credentials;
            $environment = $credentials['environment'] ?? 'sandbox';
            
            $baseUrl = $environment === 'production' 
                ? 'https://api-m.paypal.com' 
                : 'https://api-m.sandbox.paypal.com';

            // Get access token
            $token = $this->getPaypalAccessToken($baseUrl, $credentials);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get PayPal access token'];
            }

            // Create order
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => $data['currency'] ?? 'USD',
                        'value' => number_format($data['amount'], 2, '.', '')
                    ],
                    'reference_id' => $data['payment_reference'] ?? null,
                    'description' => $data['description'] ?? 'Internet Service Payment'
                ]]
            ];

            $response = Http::withToken($token)
                ->post($baseUrl . '/v2/checkout/orders', $orderData);

            if ($response->successful()) {
                $orderData = $response->json();
                $approvalUrl = collect($orderData['links'])->firstWhere('rel', 'approve')['href'] ?? null;

                // Create payment record
                $payment = Payment::create([
                    'isp_id' => $this->ispId,
                    'customer_id' => $data['customer_id'] ?? null,
                    'payment_reference' => $data['payment_reference'] ?? null,
                    'payment_method' => 'paypal',
                    'amount' => $data['amount'],
                    'transaction_id' => $orderData['id'] ?? null,
                    'status' => 'pending',
                    'metadata' => array_merge($data['metadata'] ?? [], [
                        'paypal_order_id' => $orderData['id'],
                        'approval_url' => $approvalUrl
                    ])
                ]);

                return [
                    'success' => true,
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'redirect_url' => $approvalUrl,
                    'order_id' => $orderData['id']
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create PayPal order: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('PayPal payment initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initiate Stripe payment
     */
    private function initiateStripePayment(IspPaymentMethod $paymentMethod, array $data): array
    {
        try {
            $credentials = $paymentMethod->credentials;
            $secretKey = $credentials['secret_key'] ?? null;
            if (! $secretKey) {
                return ['success' => false, 'message' => 'Stripe secret key is not configured'];
            }

            $currency = strtolower($data['currency'] ?? (\App\Models\ISP::find($this->ispId)?->currency ?? 'kes'));
            $amount = (int) round((float) $data['amount'] * 100);
            if ($amount < 1 || ! preg_match('/^[a-z]{3}$/', $currency)) {
                return ['success' => false, 'message' => 'A valid amount and ISO currency are required'];
            }

            $payment = Payment::create([
                'isp_id' => $this->ispId,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_method' => 'stripe',
                'amount' => $data['amount'],
                'status' => 'pending',
                'metadata' => $data['metadata'] ?? [],
            ]);

            $successUrl = $data['success_url'] ?? URL::temporarySignedRoute('hotspot.checkout.access', now()->addHours(24), ['payment' => $payment->id]);
            $cancelUrl = $data['cancel_url'] ?? URL::signedRoute('hotspot.checkout.cancel', ['payment' => $payment->id]);
            $successUrl .= (str_contains($successUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}';
            $response = Http::withToken($secretKey)->asForm()->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => (string) $payment->id,
                'metadata[payment_id]' => (string) $payment->id,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => $currency,
                'line_items[0][price_data][unit_amount]' => $amount,
                'line_items[0][price_data][product_data][name]' => $data['description'] ?? 'Internet Service Payment',
            ]);

            if (! $response->successful() || ! $response->json('url')) {
                $payment->markAsFailed();
                return ['success' => false, 'message' => 'Stripe checkout could not be created: '.($response->json('error.message') ?? 'provider error')];
            }

            $session = $response->json();
            $payment->update(['transaction_id' => $session['id'], 'metadata' => array_merge($payment->metadata ?? [], ['stripe_session_id' => $session['id'], 'stripe_checkout_url' => $session['url']])]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'redirect_url' => $session['url'],
                'checkout_session_id' => $session['id'],
                'publishable_key' => $credentials['publishable_key'] ?? null,
                'webhook_url' => route('payments.stripe.webhook'),
            ];

        } catch (\Throwable $e) {
            if (isset($payment) && $payment->status === 'pending') $payment->markAsFailed();
            Log::error('Stripe payment initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initiate Flutterwave payment
     */
    private function initiateFlutterwavePayment(IspPaymentMethod $paymentMethod, array $data): array
    {
        try {
            $credentials = $paymentMethod->credentials;
            $environment = $credentials['environment'] ?? 'sandbox';
            
            $baseUrl = $environment === 'production' 
                ? 'https://api.flutterwave.com/v3' 
                : 'https://ravesandboxapi.flutterwave.com/v3';

            $paymentData = [
                'tx_ref' => $data['payment_reference'] ?? uniqid('FW-'),
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'USD',
                'customer' => [
                    'email' => $data['customer_email'] ?? '',
                    'name' => $data['customer_name'] ?? '',
                    'phone_number' => $data['phone_number'] ?? ''
                ],
                'redirect_url' => $data['redirect_url'] ?? route('payment.callback.flutterwave'),
                'payment_options' => 'card,mpesa,mobilemoney,ussd',
                'customizations' => [
                    'title' => 'Internet Service Payment',
                    'description' => $data['description'] ?? 'Payment for internet service'
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $credentials['secret_key']
            ])->post($baseUrl . '/payments', $paymentData);

            if ($response->successful()) {
                $result = $response->json();
                
                $payment = Payment::create([
                    'isp_id' => $this->ispId,
                    'customer_id' => $data['customer_id'] ?? null,
                    'payment_reference' => $data['payment_reference'] ?? null,
                    'payment_method' => 'flutterwave',
                    'amount' => $data['amount'],
                    'transaction_id' => $result['data']['id'] ?? null,
                    'status' => 'pending',
                    'metadata' => array_merge($data['metadata'] ?? [], [
                        'flutterwave_link' => $result['data']['link']
                    ])
                ]);

                return [
                    'success' => true,
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'redirect_url' => $result['data']['link'],
                    'transaction_id' => $result['data']['id']
                ];
            }

            return [
                'success' => false,
                'message' => 'Flutterwave payment failed: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Flutterwave payment initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initiate Selcom payment
     */
    private function initiateSelcomPayment(IspPaymentMethod $paymentMethod, array $data): array
    {
        $credentials = $paymentMethod->credentials;
        foreach (['api_key', 'api_secret', 'vendor'] as $key) {
            if (empty($credentials[$key])) return ['success' => false, 'message' => 'Selcom API key, secret, and vendor ID are required'];
        }
        $orderId = (string) ($data['payment_reference'] ?? 'PB-'.Str::uuid());
        $callback = route('payments.selcom.callback');
        $payload = [
            'vendor' => $credentials['vendor'],
            'order_id' => $orderId,
            'buyer_email' => $data['customer_email'] ?? 'customer@example.com',
            'buyer_name' => $data['customer_name'] ?? 'Internet customer',
            'buyer_phone' => $data['phone_number'] ?? '',
            'amount' => (string) $data['amount'],
            'currency' => strtoupper($data['currency'] ?? 'TZS'),
                'redirect_url' => base64_encode($data['redirect_url'] ?? URL::temporarySignedRoute('hotspot.checkout.access', now()->addHours(24), ['payment' => $payment->id])),
            'cancel_url' => base64_encode($data['cancel_url'] ?? $callback),
            'webhook' => base64_encode($callback),
            'no_of_items' => 1,
        ];
        $fields = array_keys($payload);
        $timestamp = now()->toIso8601String();
        $signingString = 'timestamp='.$timestamp;
        foreach ($fields as $field) $signingString .= '&'.$field.'='.$payload[$field];
        $baseUrl = ($credentials['environment'] ?? 'sandbox') === 'production'
            ? 'https://apigw.selcommobile.com'
            : 'https://apigwtest.selcommobile.com';
        $payment = Payment::create([
            'isp_id' => $this->ispId,
            'customer_id' => $data['customer_id'] ?? null,
            'payment_reference' => $orderId,
            'payment_method' => 'selcom',
            'amount' => $data['amount'],
            'phone_number' => $data['phone_number'] ?? null,
            'transaction_id' => $orderId,
            'status' => 'pending',
            'metadata' => array_merge($data['metadata'] ?? [], ['selcom_order_id' => $orderId]),
        ]);
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'SELCOM '.base64_encode($credentials['api_key']),
                'Digest-Method' => 'HS256',
                'Digest' => base64_encode(hash_hmac('sha256', $signingString, $credentials['api_secret'], true)),
                'Timestamp' => $timestamp,
                'Signed-Fields' => implode(',', $fields),
            ])->post($baseUrl.'/v1/checkout/create-order', $payload);
            $result = $response->json();
            $gatewayUrl = data_get($result, 'data.0.payment_gateway_url');
            if (! $response->successful() || ($result['resultcode'] ?? null) !== '000' || ! $gatewayUrl) {
                $payment->markAsFailed();
                return ['success' => false, 'message' => $result['message'] ?? 'Selcom could not create the checkout order'];
            }
            $payment->update(['metadata' => array_merge($payment->metadata ?? [], ['selcom_response' => $result])]);
            return ['success' => true, 'payment_id' => $payment->id, 'payment_reference' => $orderId, 'redirect_url' => base64_decode($gatewayUrl, true) ?: $gatewayUrl];
        } catch (\Throwable $e) {
            $payment->markAsFailed();
            Log::error('Selcom payment initiation error: '.$e->getMessage());
            return ['success' => false, 'message' => 'Selcom payment initiation failed'];
        }
    }

    /**
     * Get PayPal access token
     */
    private function getPaypalAccessToken(string $baseUrl, array $credentials): ?string
    {
        try {
            $response = Http::withBasicAuth($credentials['client_id'], $credentials['client_secret'])
                ->asForm()
                ->post($baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            return $response->successful() ? $response->json('access_token') : null;
        } catch (\Exception $e) {
            Log::error('PayPal token error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get provider display name
     */
    private function getProviderName(string $provider): string
    {
        $names = [
            'mpesa' => 'M-Pesa',
            'daraja' => 'M-Pesa',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe (Card)',
            'flutterwave' => 'Flutterwave',
            'selcom' => 'Selcom'
        ];

        return $names[$provider] ?? ucfirst($provider);
    }

    /**
     * Test payment method connection
     */
    public function testConnection(string $provider): array
    {
        $provider = MpesaGatewayConfig::normalizeProviderForCheckout($provider);

        if (! $this->paymentMethods->has($provider)) {
            return [
                'success' => false,
                'message' => 'Payment method not configured'
            ];
        }

        $paymentMethod = $this->paymentMethods->get($provider);

        switch ($provider) {
            case 'mpesa':
                $mpesaService = new \App\Services\MpesaService(\App\Models\ISP::find($this->ispId));
                return $mpesaService->verifyPayment('test');
            
            case 'paypal':
                return $this->testPaypalConnection($paymentMethod);
            case 'stripe':
                return $this->testStripeConnection($paymentMethod);
            case 'flutterwave':
                return $this->testFlutterwaveConnection($paymentMethod);
            case 'selcom':
                return $this->testSelcomConnection($paymentMethod);
            default:
                return [
                    'success' => false,
                    'message' => 'Connection test is not available for ' . $provider
                ];
        }
    }

    private function testStripeConnection(IspPaymentMethod $method): array
    {
        $key = $method->getCredential('secret_key');
        if (! $key) return ['success' => false, 'message' => 'Stripe secret key is not configured'];
        try {
            $response = Http::timeout(10)->withToken($key)->get('https://api.stripe.com/v1/account');
            return $response->successful()
                ? ['success' => true, 'message' => 'Stripe connection successful']
                : ['success' => false, 'message' => 'Stripe connection failed: '.($response->json('error.message') ?? $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Stripe connection failed: '.$e->getMessage()];
        }
    }

    private function testFlutterwaveConnection(IspPaymentMethod $method): array
    {
        $key = $method->getCredential('secret_key');
        if (! $key) return ['success' => false, 'message' => 'Flutterwave secret key is not configured'];
        try {
            $response = Http::timeout(10)->withToken($key)->get('https://api.flutterwave.com/v3/balances/KES');
            return $response->successful()
                ? ['success' => true, 'message' => 'Flutterwave connection successful']
                : ['success' => false, 'message' => 'Flutterwave connection failed: '.($response->json('message') ?? $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Flutterwave connection failed: '.$e->getMessage()];
        }
    }

    private function testSelcomConnection(IspPaymentMethod $method): array
    {
        $credentials = $method->credentials;
        if (empty($credentials['api_key']) || empty($credentials['api_secret']) || empty($credentials['vendor'])) {
            return ['success' => false, 'message' => 'Selcom API key, secret, and vendor ID are required'];
        }
        $environment = $credentials['environment'] ?? 'sandbox';
        $baseUrl = $environment === 'production' ? 'https://apigw.selcommobile.com' : 'https://apigwtest.selcommobile.com';
        $payload = ['order_id' => 'PB-CONNECTION-'.now()->timestamp];
        $fields = array_keys($payload);
        $timestamp = now()->toIso8601String();
        $signingString = 'timestamp='.$timestamp;
        foreach ($fields as $field) $signingString .= '&'.$field.'='.$payload[$field];
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'SELCOM '.base64_encode($credentials['api_key']),
                'Digest-Method' => 'HS256',
                'Digest' => base64_encode(hash_hmac('sha256', $signingString, $credentials['api_secret'], true)),
                'Timestamp' => $timestamp,
                'Signed-Fields' => implode(',', $fields),
            ])->get($baseUrl.'/v1/checkout/order-status', $payload);
            return $response->successful()
                ? ['success' => true, 'message' => 'Selcom connection successful']
                : ['success' => false, 'message' => 'Selcom connection failed: '.($response->json('message') ?? $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Selcom connection failed: '.$e->getMessage()];
        }
    }

    private function testPaypalConnection(IspPaymentMethod $paymentMethod): array
    {
        try {
            $credentials = $paymentMethod->credentials;
            $environment = $credentials['environment'] ?? 'sandbox';
            
            $baseUrl = $environment === 'production' 
                ? 'https://api-m.paypal.com' 
                : 'https://api-m.sandbox.paypal.com';

            $token = $this->getPaypalAccessToken($baseUrl, $credentials);
            
            return $token 
                ? ['success' => true, 'message' => 'PayPal connection successful']
                : ['success' => false, 'message' => 'PayPal connection failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Connection error: ' . $e->getMessage()];
        }
    }
}
