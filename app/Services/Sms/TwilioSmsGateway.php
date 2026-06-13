<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\DataTransferObjects\SmsGatewayConfig;
use App\Services\Sms\Concerns\NormalizesSmsRecipients;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TwilioSmsGateway implements SmsGateway
{
    use NormalizesSmsRecipients;

    public function send(string $to, string $message, SmsGatewayConfig $config): void
    {
        $accountSid = (string) $config->credential('account_sid', '');
        $authToken = (string) $config->credential('auth_token', '');
        $from = (string) $config->credential('from', '');

        if ($accountSid === '' || $authToken === '' || $from === '') {
            throw new RuntimeException('Twilio credentials are incomplete for ISP '.$config->isp->id);
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->timeout(20)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'To' => '+'.$this->normalizeRecipient($to),
                'From' => $from,
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            Log::error('Twilio SMS request failed', [
                'isp_id' => $config->isp->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Twilio SMS request failed with HTTP '.$response->status());
        }
    }
}
