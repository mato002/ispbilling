<?php

namespace Tests\Unit;

use App\Models\ISP;
use App\Models\Payment;
use App\Services\MpesaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MpesaServiceStkPushTest extends TestCase
{
    use RefreshDatabase;

    private function bindSandboxMpesaConfig(): void
    {
        config([
            'app.url' => 'https://billing.test',
            'services.mpesa.consumer_key' => 'test_consumer_key',
            'services.mpesa.consumer_secret' => 'test_consumer_secret',
            'services.mpesa.passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
            'services.mpesa.shortcode' => '174379',
            'services.mpesa.environment' => 'sandbox',
            'services.mpesa.transaction_type' => 'CustomerPayBillOnline',
            'services.mpesa.callback_url' => 'https://billing.test/api/payments/mpesa/callback',
            'services.mpesa.timeout_url' => 'https://billing.test/api/payments/mpesa/timeout',
        ]);
    }

    public function test_stk_push_calls_oauth_then_processrequest_and_updates_payment_on_success(): void
    {
        $this->bindSandboxMpesaConfig();

        Http::fake([
            '*sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response(['access_token' => 'sandbox-test-token'], 200),
            '*sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest*' => Http::response([
                'MerchantRequestID' => 'mr-1',
                'CheckoutRequestID' => 'ws_CO_UNIT_TEST_123',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);

        $isp = ISP::query()->create([
            'name' => 'Test ISP',
            'company_name' => 'Test ISP Ltd',
            'email' => 'isp-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '254712345678',
        ]);

        $payment = Payment::query()->create([
            'isp_id' => $isp->id,
            'customer_id' => null,
            'invoice_id' => null,
            'payment_reference' => 'REF-'.Str::uuid()->toString(),
            'payment_method' => 'mpesa',
            'amount' => 1,
            'phone_number' => '254712345678',
            'status' => 'pending',
            'metadata' => ['purpose' => 'saas_onboarding_verification'],
        ]);

        $service = new MpesaService(null);
        $result = $service->initiateStkPush($payment);

        $this->assertTrue($result['success']);
        $this->assertSame('ws_CO_UNIT_TEST_123', $result['checkout_request_id'] ?? null);

        $payment->refresh();
        $this->assertSame('ws_CO_UNIT_TEST_123', $payment->transaction_id);
        $this->assertSame('ws_CO_UNIT_TEST_123', $payment->metadata['mpesa_checkout_request_id'] ?? null);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sandbox.safaricom.co.ke/oauth/v1/generate')
                && $request->method() === 'GET';
        });

        Http::assertSent(function ($request) use ($payment) {
            if (! str_contains($request->url(), 'stkpush/v1/processrequest')) {
                return false;
            }
            $data = $request->data();

            return $request->hasHeader('Authorization', 'Bearer sandbox-test-token')
                && (int) ($data['BusinessShortCode'] ?? 0) === 174379
                && ($data['TransactionType'] ?? '') === 'CustomerPayBillOnline'
                && (int) ($data['Amount'] ?? 0) === 1
                && ($data['PartyA'] ?? '') === $payment->phone_number
                && ($data['PhoneNumber'] ?? '') === $payment->phone_number
                && ($data['CallBackURL'] ?? '') === 'https://billing.test/api/payments/mpesa/callback'
                && ($data['QueueTimeOutURL'] ?? '') === 'https://billing.test/api/payments/mpesa/timeout'
                && ($data['TransactionDesc'] ?? '') === 'PrimeBilling verify';
        });
    }

    public function test_stk_push_returns_failure_when_daraja_returns_non_zero_response_code(): void
    {
        $this->bindSandboxMpesaConfig();

        Http::fake([
            '*sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response(['access_token' => 'sandbox-test-token'], 200),
            '*sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '1',
                'CustomerMessage' => 'The service request is invalid.',
            ], 200),
        ]);

        $isp = ISP::query()->create([
            'name' => 'Test ISP 2',
            'company_name' => 'Test ISP 2 Ltd',
            'email' => 'isp2-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '254798765432',
        ]);

        $payment = Payment::query()->create([
            'isp_id' => $isp->id,
            'customer_id' => null,
            'invoice_id' => null,
            'payment_reference' => 'REF-'.Str::uuid()->toString(),
            'payment_method' => 'mpesa',
            'amount' => 1,
            'phone_number' => '254798765432',
            'status' => 'pending',
            'metadata' => [],
        ]);

        $service = new MpesaService(null);
        $result = $service->initiateStkPush($payment);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalid', strtolower((string) ($result['message'] ?? '')));
    }

    public function test_stk_push_uses_production_hosts_when_environment_is_production(): void
    {
        config([
            'app.url' => 'https://billing.test',
            'services.mpesa.consumer_key' => 'ck',
            'services.mpesa.consumer_secret' => 'cs',
            'services.mpesa.passkey' => 'pk',
            'services.mpesa.shortcode' => '123456',
            'services.mpesa.environment' => 'production',
            'services.mpesa.transaction_type' => 'CustomerPayBillOnline',
            'services.mpesa.callback_url' => 'https://billing.test/cb',
            'services.mpesa.timeout_url' => 'https://billing.test/to',
        ]);

        Http::fake([
            '*api.safaricom.co.ke/oauth/v1/generate*' => Http::response(['access_token' => 'prod-token'], 200),
            '*api.safaricom.co.ke/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '0',
                'CheckoutRequestID' => 'ws_CO_PROD',
            ], 200),
        ]);

        $isp = ISP::query()->create([
            'name' => 'Prod ISP',
            'company_name' => 'Prod ISP Ltd',
            'email' => 'isp3-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '254711000003',
        ]);

        $payment = Payment::query()->create([
            'isp_id' => $isp->id,
            'customer_id' => null,
            'invoice_id' => null,
            'payment_reference' => 'REF-'.Str::uuid()->toString(),
            'payment_method' => 'mpesa',
            'amount' => 10,
            'phone_number' => '254711000003',
            'status' => 'pending',
            'metadata' => [],
        ]);

        $service = new MpesaService(null);
        $result = $service->initiateStkPush($payment);

        $this->assertTrue($result['success']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'));
    }
}
