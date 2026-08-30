<?php

namespace App\Services;

use App\Mail\CampaignMessageMail;
use App\Models\ISP;
use App\Models\SmsMessageQueue;
use App\Models\SmsPlatformLedger;
use App\Services\Sms\SmsGatewayManager;
use App\Support\IspSmsConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CommunicationsDeliveryService
{
    public function __construct(
        protected SmsGatewayManager $gatewayManager,
    ) {}

    /**
     * Deliver a single queued message. Returns true on success.
     */
    public function deliver(SmsMessageQueue $message): bool
    {
        if ($message->status !== SmsMessageQueue::STATUS_PENDING) {
            return $message->status === SmsMessageQueue::STATUS_SENT;
        }

        try {
            $response = match ($message->channel) {
                SmsMessageQueue::CHANNEL_SMS => $this->deliverSms($message),
                SmsMessageQueue::CHANNEL_EMAIL => $this->deliverEmail($message),
                default => throw new \RuntimeException('Unsupported channel: '.$message->channel),
            };

            $message->update([
                'status' => SmsMessageQueue::STATUS_SENT,
                'gateway_response' => $response,
                'sent_at' => now(),
            ]);

            Log::info('Communication message delivered', [
                'queue_id' => $message->id,
                'campaign_id' => $message->campaign_id,
                'customer_id' => $message->customer_id,
                'channel' => $message->channel,
            ]);

            return true;
        } catch (\Throwable $e) {
            return $this->handleFailure($message, $e);
        }
    }

    protected function deliverSms(SmsMessageQueue $message): string
    {
        $isp = ISP::query()->findOrFail($message->tenant_id);
        $config = IspSmsConfiguration::forIsp($isp);

        if (! $config->isDeliverable()) {
            throw new \RuntimeException('SMS provider is not configured or disabled for this workspace.');
        }

        $phone = trim((string) $message->recipient_phone);
        if ($phone === '') {
            throw new \RuntimeException('Missing recipient phone number.');
        }

        $unitCost = (float) ($message->cost_charged ?: config('sms.unit_cost', 0.60));

        $this->deductSmsWallet($isp, $unitCost, $message);

        $this->gatewayManager
            ->driver($config)
            ->send($phone, $message->message_body, $config);

        return json_encode([
            'provider' => $config->provider,
            'recipient' => $phone,
            'delivered_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);
    }

    protected function deliverEmail(SmsMessageQueue $message): string
    {
        $email = trim((string) $message->recipient_email);
        if ($email === '') {
            throw new \RuntimeException('Missing recipient email address.');
        }

        $isp = ISP::query()->find($message->tenant_id);
        $message->loadMissing('campaign');
        $subject = $message->campaign?->title ?? 'Message from '.($isp?->name ?? 'your ISP');

        Mail::to($email)->send(new CampaignMessageMail(
            subjectLine: $subject,
            body: $message->message_body,
            ispName: $isp?->name ?? 'ISP',
        ));

        return json_encode([
            'channel' => 'email',
            'recipient' => $email,
            'delivered_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);
    }

    protected function deductSmsWallet(ISP $isp, float $unitCost, SmsMessageQueue $message): void
    {
        DB::transaction(function () use ($isp, $unitCost, $message) {
            $locked = ISP::query()->whereKey($isp->id)->lockForUpdate()->firstOrFail();
            $balance = (float) ($locked->sms_balance ?? 0);

            if ($balance < $unitCost) {
                throw new \RuntimeException('Insufficient SMS wallet balance. Top up credits before sending.');
            }

            $locked->decrement('sms_balance', $unitCost);

            SmsPlatformLedger::create([
                'tenant_id' => $locked->id,
                'amount' => -$unitCost,
                'type' => SmsPlatformLedger::TYPE_DEDUCTION,
                'sms_count' => 1,
                'description' => 'Campaign #'.($message->campaign_id ?? 'manual').' dispatch',
            ]);
        });
    }

    protected function handleFailure(SmsMessageQueue $message, \Throwable $e): bool
    {
        $retryCount = $message->retry_count + 1;
        $canRetry = $retryCount < SmsMessageQueue::MAX_RETRIES;

        $message->update([
            'retry_count' => $retryCount,
            'status' => $canRetry ? SmsMessageQueue::STATUS_PENDING : SmsMessageQueue::STATUS_FAILED,
            'gateway_response' => mb_substr($e->getMessage(), 0, 2000),
        ]);

        Log::error('Communication message delivery failed', [
            'queue_id' => $message->id,
            'campaign_id' => $message->campaign_id,
            'customer_id' => $message->customer_id,
            'channel' => $message->channel,
            'retry_count' => $retryCount,
            'will_retry' => $canRetry,
            'error' => $e->getMessage(),
        ]);

        return false;
    }
}
