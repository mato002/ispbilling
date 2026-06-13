<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\DataTransferObjects\SmsGatewayConfig;
use App\Services\Sms\Concerns\NormalizesSmsRecipients;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VonageSmsGateway implements SmsGateway
{
    use NormalizesSmsRecipients;

    public function send(string $to, string $message, SmsGatewayConfig $config): void
    {
        $apiKey = (string) $config->credential('api_key', '');
        $apiSecret = (string) $config->credential('api_secret', '');

        if ($apiKey === '' || $apiSecret === '') {
            throw new RuntimeException('Vonage credentials are incomplete for ISP '.$config->isp->id);
        }

        $payload = [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'to' => $this->normalizeRecipient($to),
            'text' => $message,
        ];

        $from = (string) $config->credential('from', '');
        if ($from !== '') {
            $payload['from'] = $from;
        }

        $response = Http::asForm()
            ->timeout(20)
            ->post('https://rest.nexmo.com/sms/json', $payload);

        if (! $response->successful()) {
            Log::error('Vonage SMS request failed', [
                'isp_id' => $config->isp->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Vonage SMS request failed with HTTP '.$response->status());
        }

        $messages = $response->json('messages', []);
        $status = (string) ($messages[0]['status'] ?? '');

        if ($status !== '' && $status !== '0') {
            $error = (string) ($messages[0]['error-text'] ?? 'Unknown Vonage error');

            throw new RuntimeException('Vonage rejected SMS: '.$error);
        }
    }
}
