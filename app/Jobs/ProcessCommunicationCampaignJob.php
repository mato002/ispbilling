<?php

namespace App\Jobs;

use App\Models\CommunicationCampaign;
use App\Models\SmsMessageQueue;
use App\Services\CommunicationCampaignService;
use App\Services\CommunicationsDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessCommunicationCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(
        public int $campaignId,
    ) {}

    public function handle(
        CommunicationsDeliveryService $delivery,
        CommunicationCampaignService $campaigns,
    ): void {
        $campaign = CommunicationCampaign::query()->find($this->campaignId);

        if (! $campaign || ! $campaign->isProcessing()) {
            return;
        }

        $batchSize = (int) config('sms.campaign_batch_size', 25);
        $rateLimitMs = (int) config('sms.campaign_rate_limit_ms', 100);

        SmsMessageQueue::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', SmsMessageQueue::STATUS_PENDING)
            ->orderBy('id')
            ->chunkById($batchSize, function ($messages) use ($delivery, $rateLimitMs) {
                foreach ($messages as $message) {
                    $delivery->deliver($message);

                    if ($message->channel === SmsMessageQueue::CHANNEL_SMS && $rateLimitMs > 0) {
                        usleep($rateLimitMs * 1000);
                    }
                }
            });

        $campaigns->refreshCampaignStatus($campaign->fresh());

        $pending = SmsMessageQueue::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', SmsMessageQueue::STATUS_PENDING)
            ->count();

        if ($pending > 0) {
            self::dispatch($campaign->id)->delay(now()->addSeconds(30));

            Log::info('Communication campaign batch complete; retries re-queued', [
                'campaign_id' => $campaign->id,
                'pending' => $pending,
            ]);
        }
    }
}
