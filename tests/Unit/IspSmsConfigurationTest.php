<?php

namespace Tests\Unit;

use App\Channels\DynamicSmsChannel;
use App\DataTransferObjects\SmsGatewayConfig;
use App\Models\Customer;
use App\Models\ISP;
use App\Models\Payment;
use App\Notifications\PaymentReceived;
use App\Services\Sms\AfricasTalkingSmsGateway;
use App\Services\Sms\SmsGatewayManager;
use App\Services\Sms\TwilioSmsGateway;
use App\Services\Sms\VonageSmsGateway;
use App\Support\CustomerNotificationChannels;
use App\Support\IspSmsConfiguration;
use Tests\TestCase;

class IspSmsConfigurationTest extends TestCase
{
    public function test_normalizes_africas_talking_provider_aliases(): void
    {
        $this->assertSame('africas_talking', IspSmsConfiguration::normalizeProvider('africastalking'));
        $this->assertSame('africas_talking', IspSmsConfiguration::normalizeProvider('africas_talking'));
        $this->assertSame('vonage', IspSmsConfiguration::normalizeProvider('nexmo'));
    }

    public function test_resolves_tenant_sms_configuration_from_isp_settings(): void
    {
        $isp = new ISP([
            'settings' => [
                'notifications' => [
                    'sms_notifications' => true,
                    'sms_provider' => 'twilio',
                ],
                'payment_experience' => [
                    'sms_sender_id' => 'MYISP',
                ],
            ],
        ]);
        $isp->id = 42;

        $config = IspSmsConfiguration::forIsp($isp);

        $this->assertTrue($config->isDeliverable());
        $this->assertSame('twilio', $config->provider);
        $this->assertSame('MYISP', $config->credential('from'));
    }

    public function test_customer_notification_channels_include_dynamic_sms_when_enabled(): void
    {
        $isp = new ISP([
            'settings' => [
                'notifications' => [
                    'sms_notifications' => true,
                    'sms_provider' => 'vonage',
                ],
            ],
        ]);
        $isp->id = 7;

        $customer = new Customer([
            'isp_id' => 7,
            'email' => 'customer@example.com',
            'phone_number' => '254712345678',
        ]);
        $customer->setRelation('isp', $isp);

        $channels = CustomerNotificationChannels::for($customer)
            ->withMail()
            ->withSms()
            ->resolve();

        $this->assertContains('mail', $channels);
        $this->assertContains(DynamicSmsChannel::class, $channels);
    }

    public function test_customer_notification_channels_skip_sms_when_disabled(): void
    {
        $isp = new ISP([
            'settings' => [
                'notifications' => [
                    'sms_notifications' => false,
                    'sms_provider' => 'twilio',
                ],
            ],
        ]);
        $isp->id = 8;

        $customer = new Customer([
            'isp_id' => 8,
            'phone_number' => '254712345678',
        ]);
        $customer->setRelation('isp', $isp);

        $channels = CustomerNotificationChannels::for($customer)
            ->withSms()
            ->resolve();

        $this->assertNotContains(DynamicSmsChannel::class, $channels);
    }

    public function test_sms_gateway_manager_resolves_provider_drivers(): void
    {
        $manager = new SmsGatewayManager;
        $isp = new ISP;
        $isp->id = 1;

        $this->assertInstanceOf(
            VonageSmsGateway::class,
            $manager->driver(new SmsGatewayConfig($isp, 'vonage', true))
        );

        $this->assertInstanceOf(
            TwilioSmsGateway::class,
            $manager->driver(new SmsGatewayConfig($isp, 'twilio', true))
        );

        $this->assertInstanceOf(
            AfricasTalkingSmsGateway::class,
            $manager->driver(new SmsGatewayConfig($isp, 'africas_talking', true))
        );
    }

    public function test_payment_received_exposes_sms_payload_for_dynamic_channel(): void
    {
        $isp = new ISP(['currency' => 'KES']);
        $isp->id = 3;

        $customer = new Customer(['isp_id' => 3]);

        $payment = new Payment([
            'isp_id' => 3,
            'customer_id' => 1,
            'amount' => 1500,
            'payment_reference' => 'PAY-TEST-001',
        ]);
        $payment->setRelation('isp', $isp);

        $notification = new PaymentReceived($payment);
        $sms = $notification->toSms($customer);

        $this->assertStringContainsString('PAY-TEST-001', $sms);
        $this->assertStringContainsString('KES', $sms);
    }
}
