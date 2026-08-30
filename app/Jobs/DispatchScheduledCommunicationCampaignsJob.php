<?php

namespace App\Jobs;

use App\Models\CommunicationCampaign;
use App\Services\CommunicationCampaignService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DispatchScheduledCommunicationCampaignsJob implements ShouldQueue
{
    use Queueable;

    public function handle(CommunicationCampaignService $campaigns): void
    {
        CommunicationCampaign::query()
            ->where('status', CommunicationCampaign::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->chunkById(50, function ($rows) use ($campaigns) {
                foreach ($rows as $campaign) {
                    try {
                        $campaigns->launch($campaign);
                    } catch (\Throwable $e) {
                        $campaign->update([
                            'status' => CommunicationCampaign::STATUS_FAILED,
                            'last_error' => mb_substr($e->getMessage(), 0, 2000),
                            'completed_at' => now(),
                        ]);

                        Log::error('Scheduled communication campaign failed to launch', [
                            'campaign_id' => $campaign->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
