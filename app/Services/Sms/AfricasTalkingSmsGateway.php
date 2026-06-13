<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\DataTransferObjects\SmsGatewayConfig;
use App\Services\Sms\Concerns\NormalizesSmsRecipients;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AfricasTalkingSmsGateway implements SmsGateway
{
    use NormalizesSmsRecipients;

    public function send(string $to, string $message, SmsGatewayConfig $config): void
    {
        $username = (string) $config->credential('username', '');
        $apiKey = (string) $config->credential('api_key', '');

        if ($username === '' || $apiKey === '') {
            throw new RuntimeException("Africa's Talking credentials are incomplete for ISP ".$config->isp->id);
        }

        $payload = [
            'username' => $username,
            'to' => $this->normalizeRecipient($to),
            'message' => $message,
        ];

        $from = (string) $config->credential('from', '');
        if ($from !== '') {
            $payload['from'] = $from;
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'apiKey' => $apiKey,
        ])
            ->asForm()
            ->timeout(20)
            ->post('https://api.africastalking.com/version1/messaging', $payload);

        if (! $response->successful()) {
            Log::error("Africa's Talking SMS request failed", [
                'isp_id' => $config->isp->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException("Africa's Talking SMS request failed with HTTP ".$response->status());
        }

        $recipients = $response->json('SMSMessageData.Recipients', []);
        $statusCode = (string) ($recipients[0]['statusCode'] ?? '');

        if ($statusCode !== '' && ! in_array($statusCode, ['101', '102'], true)) {
            $status = (string) ($recipients[0]['status'] ?? 'Unknown delivery error');

            throw new RuntimeException("Africa's Talking rejected SMS: ".$status);
        }
    }
}
