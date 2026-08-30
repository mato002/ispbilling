<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InventoryAssignment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\RouterMigrationBatch;
use App\Models\RouterMigrationLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class RouterMigrationService
{
    public const ELIGIBILITY_ACTIVE_ONLY = 'active_only';

    public const ELIGIBILITY_ALL_ON_ROUTER = 'all_on_router';

    public function validateBatch(int $ispId, int $oldRouterId, int $newRouterId): void
    {
        if ($oldRouterId === $newRouterId) {
            throw new InvalidArgumentException('Old and new router must be different.');
        }

        $old = Router::forIsp($ispId)->whereKey($oldRouterId)->where('is_active', true)->first();
        $new = Router::forIsp($ispId)->whereKey($newRouterId)->where('is_active', true)->first();

        if (! $old || ! $new) {
            throw new InvalidArgumentException('Both routers must exist, belong to this workspace, and be active.');
        }

        $running = RouterMigrationBatch::query()
            ->where('isp_id', $ispId)
            ->whereIn('status', [RouterMigrationBatch::STATUS_PENDING, RouterMigrationBatch::STATUS_RUNNING])
            ->where(function (Builder $q) use ($oldRouterId, $newRouterId) {
                $q->where('old_router_id', $oldRouterId)
                    ->orWhere('new_router_id', $oldRouterId)
                    ->orWhere('old_router_id', $newRouterId)
                    ->orWhere('new_router_id', $newRouterId);
            })
            ->exists();

        if ($running) {
            throw new InvalidArgumentException('A migration involving one of these routers is already in progress.');
        }
    }

    /**
     * @return Builder<Customer>
     */
    public function affectedCustomersQuery(
        int $ispId,
        int $oldRouterId,
        string $eligibility = self::ELIGIBILITY_ACTIVE_ONLY,
    ): Builder {
        $query = Customer::query()
            ->forIsp($ispId)
            ->where('router_id', $oldRouterId)
            ->whereNotNull('username');

        if ($eligibility === self::ELIGIBILITY_ACTIVE_ONLY) {
            $query->whereHas('subscriptions', function (Builder $q) {
                $q->where('status', 'active')
                    ->where('end_date', '>=', now()->startOfDay());
            })->with([
                'inventoryItem',
                'subscriptions' => fn ($q) => $q->where('status', 'active')
                    ->where('end_date', '>=', now()->startOfDay())
                    ->with('plan'),
            ]);
        } else {
            // All named accounts still assigned to the source router (active, suspended, expired, etc.).
            $query->with([
                'inventoryItem',
                'subscriptions' => fn ($q) => $q->with('plan')->orderByDesc('end_date')->orderByDesc('id'),
            ]);
        }

        return $query;
    }

    public function createBatch(
        int $ispId,
        int $oldRouterId,
        int $newRouterId,
        int $userId,
        string $eligibility = self::ELIGIBILITY_ACTIVE_ONLY,
    ): RouterMigrationBatch {
        $this->validateBatch($ispId, $oldRouterId, $newRouterId);

        if (! in_array($eligibility, [self::ELIGIBILITY_ACTIVE_ONLY, self::ELIGIBILITY_ALL_ON_ROUTER], true)) {
            $eligibility = self::ELIGIBILITY_ACTIVE_ONLY;
        }

        $total = $this->affectedCustomersQuery($ispId, $oldRouterId, $eligibility)->count();

        return RouterMigrationBatch::create([
            'isp_id' => $ispId,
            'old_router_id' => $oldRouterId,
            'new_router_id' => $newRouterId,
            'status' => RouterMigrationBatch::STATUS_PENDING,
            'total_customers' => $total,
            'created_by' => $userId,
            'metadata' => [
                'eligibility' => $eligibility,
            ],
        ]);
    }

    public function eligibilityFor(RouterMigrationBatch $batch): string
    {
        return $batch->metadata['eligibility'] ?? self::ELIGIBILITY_ACTIVE_ONLY;
    }

    /**
     * @return array<int, Customer>
     */
    public function nextCustomerChunk(RouterMigrationBatch $batch, int $limit): array
    {
        $completedIds = RouterMigrationLog::query()
            ->where('batch_id', $batch->id)
            ->where('action', RouterMigrationLog::ACTION_COMPLETED)
            ->where('status', RouterMigrationLog::STATUS_SUCCESS)
            ->pluck('customer_id');

        $skipIds = $completedIds->unique();

        return $this->affectedCustomersQuery(
            $batch->isp_id,
            $batch->old_router_id,
            $this->eligibilityFor($batch),
        )
            ->when($skipIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $skipIds))
            ->with(['inventoryItem'])
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function migrateCustomer(RouterMigrationBatch $batch, Customer $customer): bool
    {
        if ($this->isCustomerMigrated($batch, $customer->id)) {
            return true;
        }

        $oldRouter = $batch->oldRouter;
        $newRouter = $batch->newRouter;
        $plan = $this->resolvePlan($customer);

        if (! $plan) {
            $this->logStep($batch, $customer, RouterMigrationLog::ACTION_FAILED, RouterMigrationLog::STATUS_FAILED,
                'No plan found to reprovision on the target router.', ['permanent' => true]);

            return false;
        }

        $snapshot = [
            'previous_router_id' => $customer->router_id,
            'previous_mikrotik_user_id' => $customer->mikrotik_user_id,
            'connection_type' => $customer->connection_type,
            'customer_status' => $customer->status,
        ];
        $oldMikrotikId = $customer->mikrotik_user_id;
        $connectionType = $customer->connection_type ?? 'pppoe';
        $shouldEnable = $customer->status === 'active';

        try {
            $oldService = new MikroTikService($oldRouter);
            $disconnected = $oldService->disconnectSession($customer);
            $this->logStep($batch, $customer, RouterMigrationLog::ACTION_DISCONNECT,
                $disconnected ? RouterMigrationLog::STATUS_SUCCESS : RouterMigrationLog::STATUS_FAILED,
                $disconnected ? null : 'Could not confirm session disconnect on old router.',
                $snapshot);

            usleep(config('router_migration.api_delay_ms', 300) * 1000);

            $newService = new MikroTikService($newRouter);
            $recreated = $this->recreateUserOnRouter($newService, $customer, $plan);
            $this->logStep($batch, $customer, RouterMigrationLog::ACTION_RECREATE_USER,
                $recreated ? RouterMigrationLog::STATUS_SUCCESS : RouterMigrationLog::STATUS_FAILED,
                $recreated ? null : 'Failed to recreate user on new router.',
                $snapshot);

            if (! $recreated) {
                throw new \RuntimeException('User recreation failed on new router.');
            }

            usleep(config('router_migration.api_delay_ms', 300) * 1000);

            $customer->refresh();

            if ($customer->inventoryItem) {
                $newService->bindInventoryDeviceToCustomer($customer, $customer->inventoryItem);
            }

            $queued = $this->applyQueueOnRouter($newService, $customer, $plan);
            $this->logStep($batch, $customer, RouterMigrationLog::ACTION_APPLY_QUEUE,
                $queued ? RouterMigrationLog::STATUS_SUCCESS : RouterMigrationLog::STATUS_FAILED,
                $queued ? null : 'Queue rebuild skipped or failed (user may still be active).',
                $snapshot);

            DB::transaction(function () use ($customer, $newRouter) {
                $customer->update(['router_id' => $newRouter->id]);

                InventoryAssignment::query()
                    ->where('customer_id', $customer->id)
                    ->where('is_active', true)
                    ->update(['router_id' => $newRouter->id]);
            });

            if ($oldMikrotikId) {
                $oldService->removeUserByDotId((string) $oldMikrotikId, $connectionType);
            }

            $fresh = $customer->fresh();
            if ($shouldEnable) {
                $newService->enableUser($fresh);
            } else {
                $newService->disableUser($fresh);
            }

            $this->logStep($batch, $customer, RouterMigrationLog::ACTION_COMPLETED, RouterMigrationLog::STATUS_SUCCESS,
                null, array_merge($snapshot, [
                    'new_router_id' => $newRouter->id,
                    'enabled_on_target' => $shouldEnable,
                ]));

            return true;
        } catch (\Throwable $e) {
            Log::error('Router migration customer failed', [
                'batch_id' => $batch->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            $this->logStep($batch, $customer, RouterMigrationLog::ACTION_FAILED, RouterMigrationLog::STATUS_FAILED,
                $e->getMessage(), $snapshot);

            return false;
        }
    }

    public function rollbackCustomer(RouterMigrationBatch $batch, Customer $customer, array $snapshot): bool
    {
        $oldRouter = $batch->oldRouter;
        $newRouter = $batch->newRouter;

        try {
            $newService = new MikroTikService($newRouter);
            $newService->disconnectSession($customer);
            if ($customer->mikrotik_user_id) {
                $newService->removeUser($customer);
            }

            $customer->forceFill([
                'router_id' => $oldRouter->id,
                'mikrotik_user_id' => $snapshot['previous_mikrotik_user_id'] ?? null,
            ])->saveQuietly();

            $plan = $this->resolvePlan($customer->fresh());
            if ($plan) {
                $oldService = new MikroTikService($oldRouter);
                $this->recreateUserOnRouter($oldService, $customer, $plan);
                $fresh = $customer->fresh();
                if (($snapshot['customer_status'] ?? $fresh->status) === 'active') {
                    $oldService->enableUser($fresh);
                } else {
                    $oldService->disableUser($fresh);
                }
            }

            $this->logStep($batch, $customer, RouterMigrationLog::ACTION_ROLLBACK, RouterMigrationLog::STATUS_SUCCESS,
                'Rolled back to previous router.', $snapshot);

            return true;
        } catch (\Throwable $e) {
            $this->logStep($batch, $customer, RouterMigrationLog::ACTION_ROLLBACK, RouterMigrationLog::STATUS_FAILED,
                $e->getMessage(), $snapshot);

            return false;
        }
    }

    /**
     * Prefer active subscription plan, otherwise most recent plan on any subscription.
     */
    protected function resolvePlan(Customer $customer): ?Plan
    {
        $subscriptions = $customer->relationLoaded('subscriptions')
            ? $customer->subscriptions
            : $customer->subscriptions()->with('plan')->orderByDesc('end_date')->orderByDesc('id')->get();

        $active = $subscriptions->first(function ($s) {
            return $s->status === 'active'
                && $s->end_date
                && $s->end_date->greaterThanOrEqualTo(now()->startOfDay())
                && $s->plan;
        });

        if ($active?->plan) {
            return $active->plan;
        }

        return $subscriptions->first(fn ($s) => $s->plan)?->plan;
    }

    protected function recreateUserOnRouter(MikroTikService $service, Customer $customer, Plan $plan): bool
    {
        if ($customer->connection_type === 'hotspot') {
            if ($service->activateHotspotUser($customer)) {
                return true;
            }

            return $service->createHotspotUser($customer, $plan);
        }

        if ($service->createPppoeUser($customer, $plan)) {
            return true;
        }

        return $service->enableUser($customer);
    }

    protected function applyQueueOnRouter(MikroTikService $service, Customer $customer, Plan $plan): bool
    {
        $queueName = 'queue_'.$customer->username;
        $service->removeSimpleQueueByName($queueName);

        if (! $plan->download_speed_kbps && ! $plan->upload_speed_kbps) {
            return true;
        }

        $down = max(1, (int) ($plan->download_speed_kbps ?? 0));
        $up = max(1, (int) ($plan->upload_speed_kbps ?? 0));

        return $service->createQueueSimple([
            'name' => $queueName,
            'target' => $customer->username,
            'max-limit' => round($down / 1000, 1).'M/'.round($up / 1000, 1).'M',
            'comment' => "Migrated customer {$customer->id}",
        ]);
    }

    protected function isCustomerMigrated(RouterMigrationBatch $batch, int $customerId): bool
    {
        return RouterMigrationLog::query()
            ->where('batch_id', $batch->id)
            ->where('customer_id', $customerId)
            ->where('action', RouterMigrationLog::ACTION_COMPLETED)
            ->where('status', RouterMigrationLog::STATUS_SUCCESS)
            ->exists();
    }

    protected function logStep(
        RouterMigrationBatch $batch,
        Customer $customer,
        string $action,
        string $status,
        ?string $error = null,
        ?array $metadata = null,
    ): RouterMigrationLog {
        return RouterMigrationLog::create([
            'batch_id' => $batch->id,
            'customer_id' => $customer->id,
            'action' => $action,
            'status' => $status,
            'error_message' => $error,
            'metadata' => $metadata,
        ]);
    }
}
