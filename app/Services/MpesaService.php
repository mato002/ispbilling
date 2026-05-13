<?php

namespace App\Services;

use App\Models\IspPaymentMethod;
use App\Models\Payment;
use App\Models\ISP;
use App\Support\IspPaymentExperience;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MpesaService
{
    protected ?ISP $isp;

    protected $consumerKey;
    protected $consumerSecret;
    protected $passkey;
    protected $shortcode;
    protected $environment;
    protected $transactionType;
    protected $callbackUrl;
    protected $timeoutUrl;

    /**
     * @param  ISP|null  $isp  When null, Daraja credentials are read only from config/services (platform / SaaS collections).
     */
    public function __construct(?ISP $isp = null)
    {
        $this->isp = $isp;

        if ($isp) {
            $daraja = IspPaymentMethod::query()
                ->forIsp($isp->id)
                ->byProvider('daraja')
                ->where('active', true)
                ->first();

            if ($daraja) {
                $c = $daraja->credentials;
                $this->consumerKey = $c['consumer_key'] ?? config('services.mpesa.consumer_key');
                $this->consumerSecret = $c['consumer_secret'] ?? config('services.mpesa.consumer_secret');
                $this->passkey = $c['passkey'] ?? config('services.mpesa.passkey');
                $this->shortcode = $c['shortcode'] ?? config('services.mpesa.shortcode');
                $this->environment = $c['environment'] ?? config('services.mpesa.environment', 'sandbox');
                $this->transactionType = $c['transaction_type'] ?? config('services.mpesa.transaction_type', 'CustomerPayBillOnline');
                $this->callbackUrl = $daraja->callback_url ?: config('services.mpesa.callback_url');
                $this->timeoutUrl = $this->callbackUrl ?: config('services.mpesa.timeout_url');

                return;
            }
        }

        $settings = $isp?->settings ?? [];
        $paymentGateways = $settings['payment_gateways'] ?? [];
        $mpesaSettings = $paymentGateways['mpesa'] ?? [];

        $this->consumerKey = $mpesaSettings['consumer_key'] ?? config('services.mpesa.consumer_key');
        $this->consumerSecret = $mpesaSettings['consumer_secret'] ?? config('services.mpesa.consumer_secret');
        $this->passkey = $mpesaSettings['passkey'] ?? config('services.mpesa.passkey');
        $this->shortcode = $mpesaSettings['shortcode'] ?? config('services.mpesa.shortcode');
        $this->environment = $mpesaSettings['environment'] ?? config('services.mpesa.environment', 'sandbox');
        $this->transactionType = $mpesaSettings['transaction_type'] ?? config('services.mpesa.transaction_type', 'CustomerPayBillOnline');
        $this->callbackUrl = $mpesaSettings['callback_url'] ?? config('services.mpesa.callback_url');
        $this->timeoutUrl = $mpesaSettings['timeout_url'] ?? config('services.mpesa.timeout_url');
    }

    /**
     * Get access token
     */
    protected function getAccessToken(): ?string
    {
        return self::accessTokenForKeys($this->consumerKey, $this->consumerSecret, $this->environment);
    }

    /**
     * OAuth client-credentials token for arbitrary Daraja keys (e.g. C2B register before saving).
     */
    public static function accessTokenForKeys(?string $consumerKey, ?string $consumerSecret, ?string $environment = null): ?string
    {
        if (empty($consumerKey) || empty($consumerSecret)) {
            Log::error('MPesa credentials not configured. Please set consumer_key and consumer_secret in ISP settings or .env file.');

            return null;
        }

        $env = $environment ?: config('services.mpesa.environment', 'sandbox');

        try {
            $url = $env === 'production'
                ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
                : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

            $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->get($url);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            Log::error('MPesa access token failed: '.$response->body());

            return null;
        } catch (Exception $e) {
            Log::error('MPesa access token error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Register C2B validation and confirmation URLs against a Paybill / Till shortcode.
     *
     * @param  array{consumer_key:string,consumer_secret:string,shortcode:string,environment?:string}  $credentials
     * @return array{success:bool,message:string,response?:array}
     */
    public static function registerC2BUrls(array $credentials, string $validationUrl, string $confirmationUrl): array
    {
        $consumerKey = (string) ($credentials['consumer_key'] ?? '');
        $consumerSecret = (string) ($credentials['consumer_secret'] ?? '');
        $shortcode = (string) ($credentials['shortcode'] ?? '');
        $environment = (string) ($credentials['environment'] ?? config('services.mpesa.environment', 'sandbox'));

        if ($consumerKey === '' || $consumerSecret === '' || $shortcode === '') {
            return ['success' => false, 'message' => 'Consumer key, consumer secret, and shortcode are required for C2B registration.'];
        }

        $token = self::accessTokenForKeys($consumerKey, $consumerSecret, $environment);
        if (! $token) {
            return ['success' => false, 'message' => 'Could not obtain Daraja access token. Check consumer key and secret.'];
        }

        $url = $environment === 'production'
            ? 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl'
            : 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';

        $payload = [
            'ShortCode' => (int) $shortcode,
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl,
        ];

        try {
            $response = Http::withToken($token)->acceptJson()->post($url, $payload);
            $body = $response->json() ?: ['raw' => $response->body()];

            $desc = strtolower((string) ($body['ResponseDescription'] ?? ''));
            $ok = $response->successful()
                && ($desc === 'success'
                    || (string) ($body['ResponseCode'] ?? '') === '0'
                    || isset($body['OriginatorCoopID']));

            if ($ok) {
                return [
                    'success' => true,
                    'message' => (string) ($body['ResponseDescription'] ?? 'C2B URLs registered'),
                    'response' => is_array($body) ? $body : [],
                ];
            }

            return [
                'success' => false,
                'message' => (string) ($body['errorMessage'] ?? $body['ResponseDescription'] ?? 'C2B register failed'),
                'response' => is_array($body) ? $body : [],
            ];
        } catch (Exception $e) {
            Log::warning('M-Pesa C2B registerurl failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Initiate STK Push
     */
    public function initiateStkPush(Payment $payment): array
    {
        // Validate required credentials
        if (empty($this->consumerKey) || empty($this->consumerSecret) || empty($this->passkey) || empty($this->shortcode)) {
            // Check if we're in development/test mode
            $isDevelopment = config('app.env') === 'local' || config('app.debug');
            
            if ($isDevelopment) {
                // In development mode, simulate a successful payment initiation
                // This allows testing without actual MPesa credentials
                Log::warning('MPesa credentials not configured. Running in development mode - simulating payment.');
                
                // Log transaction as simulated
                \App\Models\PaymentTransactionLog::log([
                    'payment_id' => $payment->id,
                    'isp_id' => $payment->isp_id,
                    'transaction_type' => 'initiated',
                    'payment_method' => 'mpesa',
                    'payment_reference' => $payment->payment_reference,
                    'status' => 'pending',
                    'amount' => $payment->amount,
                    'error_message' => 'Development mode: MPesa credentials not configured. Payment simulated.',
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Development mode: Payment simulated. In production, configure MPesa credentials in ISP settings.',
                    'checkout_request_id' => 'DEV-' . time(),
                    'simulated' => true
                ];
            }
            
            // Log transaction
            \App\Models\PaymentTransactionLog::log([
                'payment_id' => $payment->id,
                'isp_id' => $payment->isp_id,
                'transaction_type' => 'initiated',
                'payment_method' => 'mpesa',
                'payment_reference' => $payment->payment_reference,
                'status' => 'failed',
                'amount' => $payment->amount,
                'error_message' => 'MPesa credentials not configured. Please configure MPesa settings in ISP settings.',
            ]);
            
            return ['success' => false, 'message' => 'MPesa credentials not configured. Please configure MPesa settings in ISP settings or contact your administrator.'];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            // Log transaction
            \App\Models\PaymentTransactionLog::log([
                'payment_id' => $payment->id,
                'isp_id' => $payment->isp_id,
                'transaction_type' => 'initiated',
                'payment_method' => 'mpesa',
                'payment_reference' => $payment->payment_reference,
                'status' => 'failed',
                'amount' => $payment->amount,
                'error_message' => 'Failed to get access token',
            ]);
            
            return ['success' => false, 'message' => 'Failed to get access token'];
        }

        try {
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $url = $this->environment === 'production'
                ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

            // Use configured callback URL or fallback to route
            $callbackUrl = $this->callbackUrl ?? route('payments.mpesa.callback');
            $timeoutUrl = $this->timeoutUrl ?? route('payments.mpesa.callback');

            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $this->transactionType,
                'Amount' => (int) $payment->amount,
                'PartyA' => $payment->phone_number,
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $payment->phone_number,
                'CallBackURL' => $callbackUrl,
                'QueueTimeOutURL' => $timeoutUrl,
                'AccountReference' => IspPaymentExperience::stkAccountReference($payment),
                'TransactionDesc' => match ($payment->metadata['purpose'] ?? null) {
                    'platform_saas' => 'PrimeBilling SaaS',
                    'saas_onboarding_verification' => 'PrimeBilling verify',
                    default => 'ISP Billing Payment - '.$payment->payment_reference,
                },
            ];

            // Log request
            \App\Models\PaymentTransactionLog::log([
                'payment_id' => $payment->id,
                'isp_id' => $payment->isp_id,
                'transaction_type' => 'initiated',
                'payment_method' => 'mpesa',
                'payment_reference' => $payment->payment_reference,
                'status' => 'pending',
                'amount' => $payment->amount,
                'request_data' => $payload,
            ]);

            $response = Http::withToken($accessToken)
                ->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful()) {
                if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
                    $checkoutRequestId = $responseData['CheckoutRequestID'] ?? null;
                    
                    $payment->update([
                        'transaction_id' => $checkoutRequestId,
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'stk_push_response' => $responseData,
                            'mpesa_checkout_request_id' => $checkoutRequestId,
                        ]),
                    ]);

                    // Log successful initiation
                    \App\Models\PaymentTransactionLog::log([
                        'payment_id' => $payment->id,
                        'isp_id' => $payment->isp_id,
                        'transaction_type' => 'initiated',
                        'payment_method' => 'mpesa',
                        'payment_reference' => $payment->payment_reference,
                        'transaction_id' => $checkoutRequestId,
                        'status' => 'pending',
                        'amount' => $payment->amount,
                        'request_data' => $payload,
                        'response_data' => $responseData,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'STK Push initiated successfully',
                        'checkout_request_id' => $checkoutRequestId,
                    ];
                }

                // Log failed initiation
                \App\Models\PaymentTransactionLog::log([
                    'payment_id' => $payment->id,
                    'isp_id' => $payment->isp_id,
                    'transaction_type' => 'initiated',
                    'payment_method' => 'mpesa',
                    'payment_reference' => $payment->payment_reference,
                    'status' => 'failed',
                    'amount' => $payment->amount,
                    'request_data' => $payload,
                    'response_data' => $responseData,
                    'error_message' => $responseData['CustomerMessage'] ?? 'STK Push failed',
                ]);

                return [
                    'success' => false,
                    'message' => $responseData['CustomerMessage'] ?? 'STK Push failed',
                ];
            }

            // Log HTTP error
            \App\Models\PaymentTransactionLog::log([
                'payment_id' => $payment->id,
                'isp_id' => $payment->isp_id,
                'transaction_type' => 'initiated',
                'payment_method' => 'mpesa',
                'payment_reference' => $payment->payment_reference,
                'status' => 'failed',
                'amount' => $payment->amount,
                'request_data' => $payload,
                'error_message' => 'HTTP Error: ' . $response->status() . ' - ' . $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate STK Push: ' . $response->body(),
            ];
        } catch (Exception $e) {
            Log::error('MPesa STK Push error: ' . $e->getMessage());
            
            // Log exception
            \App\Models\PaymentTransactionLog::log([
                'payment_id' => $payment->id,
                'isp_id' => $payment->isp_id,
                'transaction_type' => 'initiated',
                'payment_method' => 'mpesa',
                'payment_reference' => $payment->payment_reference,
                'status' => 'failed',
                'amount' => $payment->amount,
                'error_message' => 'Exception: ' . $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $checkoutRequestId): array
    {
        // Validate required credentials
        if (empty($this->consumerKey) || empty($this->consumerSecret) || empty($this->passkey) || empty($this->shortcode)) {
            return ['success' => false, 'message' => 'MPesa credentials not configured'];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to get access token'];
        }

        try {
            $url = $this->environment === 'production'
                ? 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';

            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ];

            $response = Http::withToken($accessToken)
                ->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'message' => 'Verification failed'];
        } catch (Exception $e) {
            Log::error('MPesa verification error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

