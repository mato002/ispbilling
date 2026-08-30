<?php

namespace App\Services;

use App\Jobs\ProcessCommunicationCampaignJob;
use App\Models\CommunicationCampaign;
use App\Models\SmsMessageQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommunicationCampaignService
{
    public function __construct(
        protected CommunicationsAudienceService $audience,
    ) {}

    /**
     * Queue all outbound messages and dispatch the delivery worker.
     * Controllers must call this — never send directly.
     */
    public function launch(CommunicationCampaign $campaign): void
    {
        if (! $campaign->isLaunchable()) {
            throw new \RuntimeException('Campaign cannot be launched in status: '.$campaign->status);
        }

        $audience = $this->audience->resolve(
            $campaign->isp_id,
            $campaign->audience_filters ?? [],
            $campaign->channel,
        );

        if ($audience->isEmpty()) {
            $campaign->update([
                'status' => CommunicationCampaign::STATUS_FAILED,
                'last_error' => 'No recipients matched the audience filters with deliverable contact details.',
                'completed_at' => now(),
            ]);

            Log::warning('Communication campaign launch aborted: empty audience', [
                'campaign_id' => $campaign->id,
                'isp_id' => $campaign->isp_id,
            ]);

            return;
        }

        DB::transaction(function () use ($campaign, $audience) {
            $campaign->update([
                'status' => CommunicationCampaign::STATUS_SENDING,
                'started_at' => now(),
                'last_error' => null,
                'sent_count' => 0,
                'failed_count' => 0,
            ]);

            $queued = $this->enqueueMessages($campaign, $audience);

            $campaign->update(['queued_count' => $queued]);
        });

        ProcessCommunicationCampaignJob::dispatch($campaign->id);

        Log::info('Communication campaign queued for delivery', [
            'campaign_id' => $campaign->id,
            'isp_id' => $campaign->isp_id,
            'queued_count' => $campaign->fresh()->queued_count,
        ]);
    }

    /**
     * Schedule a campaign for future delivery.
     */
    public function schedule(CommunicationCampaign $campaign): void
    {
        if ($campaign->scheduled_at === null || $campaign->scheduled_at->isPast()) {
            $this->launch($campaign);

            return;
        }

        $campaign->update([
            'status' => CommunicationCampaign::STATUS_SCHEDULED,
        ]);

        Log::info('Communication campaign scheduled', [
            'campaign_id' => $campaign->id,
            'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $audience
     */
    protected function enqueueMessages(CommunicationCampaign $campaign, $audience): int
    {
        $rows = [];
        $now = now();
        $unitCost = (float) config('sms.unit_cost', 0.60);

        foreach ($audience as $recipient) {
            if (in_array($campaign->channel, [CommunicationCampaign::CHANNEL_SMS, CommunicationCampaign::CHANNEL_BOTH], true)
                && filled($recipient['phone_number'])) {
                $rows[] = $this->queueRow($campaign, $recipient, SmsMessageQueue::CHANNEL_SMS, $recipient['phone_number'], null, $unitCost, $now);
            }

            if (in_array($campaign->channel, [CommunicationCampaign::CHANNEL_EMAIL, CommunicationCampaign::CHANNEL_BOTH], true)
                && filled($recipient['email'])) {
                $rows[] = $this->queueRow($campaign, $recipient, SmsMessageQueue::CHANNEL_EMAIL, null, $recipient['email'], 0, $now);
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            SmsMessageQueue::query()->insert($chunk);
        }

        return count($rows);
    }

    /**
     * @param  array<string, mixed>  $recipient
     * @return array<string, mixed>
     */
    protected function queueRow(
        CommunicationCampaign $campaign,
        array $recipient,
        string $channel,
        ?string $phone,
        ?string $email,
        float $cost,
        $now,
    ): array {
        return [
            'id' => (string) Str::uuid(),
            'tenant_id' => $campaign->isp_id,
            'campaign_id' => $campaign->id,
            'customer_id' => $recipient['customer_id'],
            'recipient_phone' => $phone,
            'recipient_email' => $email,
            'message_body' => $campaign->message_body,
            'channel' => $channel,
            'priority' => 'medium',
            'status' => SmsMessageQueue::STATUS_PENDING,
            'cost_charged' => $channel === SmsMessageQueue::CHANNEL_SMS ? $cost : 0,
            'retry_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * Refresh campaign counters and terminal status from queue rows.
     */
    public function refreshCampaignStatus(CommunicationCampaign $campaign): void
    {
        $base = SmsMessageQueue::query()->where('campaign_id', $campaign->id);

        $sent = (clone $base)->where('status', SmsMessageQueue::STATUS_SENT)->count();
        $failed = (clone $base)->where('status', SmsMessageQueue::STATUS_FAILED)->count();
        $pending = (clone $base)->where('status', SmsMessageQueue::STATUS_PENDING)->count();

        $updates = [
            'sent_count' => $sent,
            'failed_count' => $failed,
        ];

        if ($pending === 0) {
            $updates['completed_at'] = now();
            $updates['status'] = $failed > 0 && $sent === 0
                ? CommunicationCampaign::STATUS_FAILED
                : CommunicationCampaign::STATUS_SENT;
        }

        $campaign->update($updates);
    }
}
