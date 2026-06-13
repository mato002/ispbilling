<?php

namespace App\Channels;

use App\Services\Sms\SmsGatewayManager;
use App\Support\IspSmsConfiguration;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DynamicSmsChannel
{
    public function __construct(
        private readonly SmsGatewayManager $gatewayManager,
    ) {}

    /**
     * @param  Customer  $notifiable
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (! is_string($message) || trim($message) === '') {
            return;
        }

        if (empty($notifiable->phone_number)) {
            return;
        }

        $isp = $notifiable->relationLoaded('isp')
            ? $notifiable->isp
            : $notifiable->isp()->first();

        $config = IspSmsConfiguration::forIsp($isp);

        if (! $config->isDeliverable()) {
            Log::info('Skipping SMS delivery because tenant SMS is disabled or incomplete', [
                'isp_id' => $isp?->id,
                'customer_id' => $notifiable->id,
            ]);

            return;
        }

        try {
            $this->gatewayManager
                ->driver($config)
                ->send($notifiable->phone_number, $message, $config);
        } catch (\Throwable $e) {
            Log::error('Dynamic SMS delivery failed', [
                'isp_id' => $isp?->id,
                'customer_id' => $notifiable->id,
                'provider' => $config->provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
