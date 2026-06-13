<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\DataTransferObjects\SmsGatewayConfig;
use App\Services\Sms\Concerns\NormalizesSmsRecipients;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CustomSmsGateway implements SmsGateway
{
    use NormalizesSmsRecipients;

    public function send(string $to, string $message, SmsGatewayConfig $config): void
    {
        $webhookUrl = (string) $config->credential('custom_webhook_url', '');

        if ($webhookUrl === '') {
            throw new RuntimeException('Custom SMS webhook URL is not configured for ISP '.$config->isp->id);
        }

        $payload = [
            'to' => $this->normalizeRecipient($to),
            'message' => $message,
            'sender_id' => $config->credential('from'),
            'isp_id' => $config->isp->id,
        ];

        $request = Http::timeout(20)->acceptJson();

        $apiKey = (string) $config->credential('api_key', '');
        if ($apiKey !== '') {
            $request = $request->withToken($apiKey);
        }

        $response = $request->post($webhookUrl, $payload);

        if (! $response->successful()) {
            Log::error('Custom SMS webhook request failed', [
                'isp_id' => $config->isp->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Custom SMS webhook failed with HTTP '.$response->status());
        }
    }
}
