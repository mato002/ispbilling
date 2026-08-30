<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\NetworkAuditLog;
use App\Models\Router;
use App\Services\MikroTikService;
use App\Services\ProvisioningSnapshotService;
use App\Services\UserConnectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FlushSessionRebuildQueuesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(
        public int $auditLogId,
        public int $customerId,
        public int $routerId,
    ) {}

    public function handle(
        ProvisioningSnapshotService $snapshots,
        UserConnectionService $connections,
    ): void {
        $audit = NetworkAuditLog::query()->find($this->auditLogId);
        $customer = Customer::query()
            ->with(['router', 'subscriptions' => fn ($q) => $q->where('status', 'active')->with('plan')])
            ->find($this->customerId);
        $router = Router::query()->find($this->routerId);

        if (! $audit || ! $customer || ! $router) {
            return;
        }

        $steps = [];

        try {
            $mikrotik = new MikroTikService($router);

            $disconnected = $mikrotik->disconnectSession($customer);
            $steps['disconnect'] = $disconnected;

            $queueName = 'queue_'.$customer->username;
            $mikrotik->removeSimpleQueueByName($queueName);
            $steps['queue_removed'] = true;

            $subscription = $customer->subscriptions->first();
            $rebuilt = false;

            if ($subscription) {
                $plan = $subscription->plan;
                if ($plan && ($plan->download_speed_kbps || $plan->upload_speed_kbps)) {
                    $down = max(1, (int) ($plan->download_speed_kbps ?? 0));
                    $up = max(1, (int) ($plan->upload_speed_kbps ?? 0));
                    $rebuilt = $mikrotik->createQueueSimple([
                        'name' => $queueName,
                        'target' => $customer->username,
                        'max-limit' => round($down / 1000, 1).'M/'.round($up / 1000, 1).'M',
                        'comment' => "PB rebuild customer {$customer->id}",
                    ]);
                } else {
                    $rebuilt = $connections->applyBandwidthLimits($customer, $subscription);
                }
            }

            $steps['queue_rebuilt'] = $rebuilt;

            $audit->update([
                'status' => NetworkAuditLog::STATUS_SUCCESS,
                'description' => 'Session flushed and queues rebuilt for '.$customer->username,
                'metadata' => array_merge($audit->metadata ?? [], $steps),
            ]);
        } catch (\Throwable $e) {
            Log::error('FlushSessionRebuildQueuesJob failed', [
                'audit_id' => $this->auditLogId,
                'error' => $e->getMessage(),
            ]);

            $audit->update([
                'status' => NetworkAuditLog::STATUS_FAILED,
                'description' => 'Flush/rebuild failed: '.$e->getMessage(),
                'metadata' => array_merge($audit->metadata ?? [], $steps, ['error' => $e->getMessage()]),
            ]);
        }

        $snapshots->refresh($customer->isp_id);
    }
}
