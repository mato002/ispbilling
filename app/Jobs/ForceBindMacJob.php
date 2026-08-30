<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\NetworkAuditLog;
use App\Models\Router;
use App\Services\MikroTikService;
use App\Services\ProvisioningSnapshotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ForceBindMacJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(
        public int $auditLogId,
        public int $customerId,
        public string $macAddress,
        public int $routerId,
        public int $performedByUserId,
    ) {}

    public function handle(ProvisioningSnapshotService $snapshots): void
    {
        $audit = NetworkAuditLog::query()->find($this->auditLogId);
        $customer = Customer::query()->with('inventoryItem')->find($this->customerId);
        $router = Router::query()->find($this->routerId);

        if (! $audit || ! $customer || ! $router) {
            return;
        }

        try {
            $item = $customer->inventoryItem ?? new InventoryItem([
                'serial_number' => 'MANUAL-'.$customer->id,
                'mac_address' => $this->macAddress,
            ]);

            if (! $customer->inventoryItem) {
                $item->mac_address = $this->macAddress;
            }

            $service = new MikroTikService($router);
            $bound = $service->bindInventoryDeviceToCustomer($customer, $item);

            $audit->update([
                'status' => $bound ? NetworkAuditLog::STATUS_SUCCESS : NetworkAuditLog::STATUS_FAILED,
                'description' => $bound
                    ? "MAC {$this->macAddress} bound on router {$router->name}"
                    : "MAC bind failed on router {$router->name} — check RouterOS user exists",
                'metadata' => array_merge($audit->metadata ?? [], [
                    'router_bound' => $bound,
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('ForceBindMacJob failed', [
                'audit_id' => $this->auditLogId,
                'error' => $e->getMessage(),
            ]);

            $audit->update([
                'status' => NetworkAuditLog::STATUS_FAILED,
                'description' => 'MAC bind job failed: '.$e->getMessage(),
            ]);
        }

        $snapshots->refresh($customer->isp_id);
    }
}
