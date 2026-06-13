<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\DataTransferObjects\SmsGatewayConfig;
use InvalidArgumentException;

class SmsGatewayManager
{
    /**
     * @param  array<string, class-string<SmsGateway>>  $drivers
     */
    public function __construct(
        private array $drivers = [
            'vonage' => VonageSmsGateway::class,
            'twilio' => TwilioSmsGateway::class,
            'africas_talking' => AfricasTalkingSmsGateway::class,
            'custom' => CustomSmsGateway::class,
        ],
    ) {}

    public function driver(SmsGatewayConfig $config): SmsGateway
    {
        $provider = $config->provider;

        if ($provider === '' || $provider === 'none') {
            throw new InvalidArgumentException('No SMS provider configured for ISP '.$config->isp->id);
        }

        $class = $this->drivers[$provider] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Unsupported SMS provider [{$provider}] for ISP ".$config->isp->id);
        }

        return app($class);
    }
}
