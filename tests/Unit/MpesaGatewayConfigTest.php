<?php

namespace Tests\Unit;

use App\Models\ISP;
use App\Models\IspPaymentMethod;
use App\Support\MpesaGatewayConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MpesaGatewayConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_credentials_maps_prefixed_form_keys(): void
    {
        $normalized = MpesaGatewayConfig::normalizeCredentials([
            'mpesa_consumer_key' => 'ck-test',
            'mpesa_consumer_secret' => 'cs-test',
            'mpesa_passkey' => 'pk-test',
            'mpesa_shortcode' => '174379',
            'mpesa_environment' => 'sandbox',
        ]);

        $this->assertSame('ck-test', $normalized['consumer_key']);
        $this->assertSame('cs-test', $normalized['consumer_secret']);
        $this->assertSame('pk-test', $normalized['passkey']);
        $this->assertSame('174379', $normalized['shortcode']);
        $this->assertSame('sandbox', $normalized['environment']);
    }

    public function test_isp_payment_method_provider_mpesa_is_stored_as_daraja(): void
    {
        $isp = ISP::query()->create([
            'name' => 'Alias ISP',
            'company_name' => 'Alias ISP Ltd',
            'email' => 'alias-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '254712345678',
        ]);

        $method = IspPaymentMethod::create([
            'isp_id' => $isp->id,
            'provider' => 'mpesa',
            'credentials' => [
                'consumer_key' => 'ck',
                'consumer_secret' => 'cs',
                'passkey' => 'pk',
                'shortcode' => '174379',
                'environment' => 'sandbox',
            ],
            'active' => true,
        ]);

        $this->assertSame(MpesaGatewayConfig::STORAGE_PROVIDER, $method->fresh()->provider);
    }

    public function test_resolve_for_isp_reads_mpesa_provider_row_for_checkout(): void
    {
        $isp = ISP::query()->create([
            'name' => 'Checkout ISP',
            'company_name' => 'Checkout ISP Ltd',
            'email' => 'checkout-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '254712345679',
        ]);

        IspPaymentMethod::create([
            'isp_id' => $isp->id,
            'provider' => 'mpesa',
            'credentials' => [
                'consumer_key' => 'row-ck',
                'consumer_secret' => 'row-cs',
                'passkey' => 'row-pk',
                'shortcode' => '600000',
                'environment' => 'production',
            ],
            'callback_url' => 'https://billing.test/callback/token',
            'active' => true,
        ]);

        $config = $isp->fresh()->resolvedMpesaConfig(activeMethodOnly: true);

        $this->assertSame('row-ck', $config['consumer_key']);
        $this->assertSame('600000', $config['shortcode']);
        $this->assertSame('https://billing.test/callback/token', $config['callback_url']);
        $this->assertSame('isp_payment_method', $config['source']);
    }

    public function test_resolve_for_isp_merges_settings_daraja_and_mpesa_keys(): void
    {
        $isp = ISP::query()->create([
            'name' => 'Settings ISP',
            'company_name' => 'Settings ISP Ltd',
            'email' => 'settings-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '254712345680',
            'settings' => [
                'payment_gateways' => [
                    'daraja' => [
                        'consumer_key' => 'legacy-ck',
                    ],
                    'mpesa' => [
                        'enabled' => true,
                        'consumer_secret' => 'settings-cs',
                        'passkey' => 'settings-pk',
                        'shortcode' => '123456',
                    ],
                ],
            ],
        ]);

        $config = $isp->fresh()->resolvedMpesaConfig();

        $this->assertSame('legacy-ck', $config['consumer_key']);
        $this->assertSame('settings-cs', $config['consumer_secret']);
        $this->assertSame('settings-pk', $config['passkey']);
        $this->assertSame('123456', $config['shortcode']);
        $this->assertSame('settings', $config['source']);
    }

    public function test_resolve_for_isp_without_credentials_does_not_use_platform_env(): void
    {
        $isp = ISP::query()->create([
            'name' => 'No Gateway ISP',
            'company_name' => 'No Gateway ISP Ltd',
            'email' => 'nogw-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '254712345682',
        ]);

        config([
            'services.mpesa.consumer_key' => 'platform-ck',
            'services.mpesa.shortcode' => '480701',
        ]);

        $config = $isp->fresh()->resolvedMpesaConfig();

        $this->assertSame('unconfigured', $config['source']);
        $this->assertNotSame('480701', $config['shortcode']);
    }

    public function test_payment_gateway_service_exposes_daraja_row_as_mpesa_checkout_provider(): void
    {
        $isp = ISP::query()->create([
            'name' => 'Gateway ISP',
            'company_name' => 'Gateway ISP Ltd',
            'email' => 'gateway-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '254712345681',
        ]);

        IspPaymentMethod::create([
            'isp_id' => $isp->id,
            'provider' => 'daraja',
            'credentials' => [
                'consumer_key' => 'ck',
                'consumer_secret' => 'cs',
                'passkey' => 'pk',
                'shortcode' => '174379',
                'environment' => 'sandbox',
            ],
            'active' => true,
        ]);

        $service = new \App\Services\PaymentGatewayService($isp->id);
        $methods = $service->getAvailableMethods();

        $this->assertCount(1, $methods);
        $this->assertSame(MpesaGatewayConfig::CHECKOUT_PROVIDER, $methods[0]['provider']);
    }
}
