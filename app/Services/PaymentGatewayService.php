<?php

namespace App\Services;

use App\Models\IspPaymentMethod;
use App\Models\Payment;
use App\Support\MpesaGatewayConfig;
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
            
            // This would require stripe/stripe-php package
            // For now, return a placeholder response
            $payment = Payment::create([
                'isp_id' => $this->ispId,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_method' => 'stripe',
                'amount' => $data['amount'],
                'status' => 'pending',
                'metadata' => $data['metadata'] ?? [],
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'message' => 'Stripe payment setup - client-side integration required',
                'publishable_key' => $credentials['publishable_key'] ?? null
            ];

        } catch (\Exception $e) {
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
        try {
            $credentials = $paymentMethod->credentials;
            
            // Selcom-specific implementation
            $payment = Payment::create([
                'isp_id' => $this->ispId,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_method' => 'selcom',
                'amount' => $data['amount'],
                'phone_number' => $data['phone_number'] ?? null,
                'status' => 'pending',
                'metadata' => $data['metadata'] ?? [],
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'message' => 'Selcom payment initiated - mobile money integration required'
            ];

        } catch (\Exception $e) {
            Log::error('Selcom payment initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage()
            ];
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
            
            default:
                return [
                    'success' => true,
                    'message' => 'Connection test not implemented for ' . $provider
                ];
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
