<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\InventoryItem;
use App\Models\NetworkAuditLog;
use App\Models\Router;
use App\Models\User;
use App\Jobs\ForceBindMacJob;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryBindingService
{
    /**
     * Explicit admin MAC authorization — never auto-applied.
     */
    public function forceBindMac(
        int $ispId,
        int $customerId,
        string $macAddress,
        int $routerId,
        User $performedBy,
        ?string $ipAddress = null,
    ): NetworkAuditLog {
        $customer = Customer::query()
            ->forIsp($ispId)
            ->whereKey($customerId)
            ->firstOrFail();

        $router = Router::query()
            ->forIsp($ispId)
            ->whereKey($routerId)
            ->where('is_active', true)
            ->firstOrFail();

        if ($customer->router_id && (int) $customer->router_id !== (int) $routerId) {
            throw new InvalidArgumentException('Selected router does not match the customer router assignment.');
        }

        $normalizedMac = $this->normalizeMac($macAddress);
        if (! $normalizedMac) {
            throw new InvalidArgumentException('Invalid MAC address format.');
        }

        $audit = NetworkAuditLog::create([
            'isp_id' => $ispId,
            'user_id' => $performedBy->id,
            'customer_id' => $customer->id,
            'router_id' => $router->id,
            'action' => NetworkAuditLog::ACTION_FORCE_MAC_BIND,
            'status' => NetworkAuditLog::STATUS_QUEUED,
            'description' => "Queued manual MAC authorization for {$customer->full_name}",
            'metadata' => [
                'mac_address' => $normalizedMac,
            ],
            'ip_address' => $ipAddress,
        ]);

        DB::transaction(function () use ($customer, $normalizedMac, $router) {
            CustomerDevice::where('customer_id', $customer->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            CustomerDevice::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'mac_address' => $normalizedMac,
                ],
                [
                    'device_name' => 'Manual authorization',
                    'device_type' => 'authorized',
                    'is_active' => true,
                    'is_blocked' => false,
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                ],
            );

            if ($customer->inventory_item_id) {
                InventoryItem::query()
                    ->whereKey($customer->inventory_item_id)
                    ->update(['mac_address' => $normalizedMac]);
            }

            if (! $customer->router_id) {
                $customer->update(['router_id' => $router->id]);
            }
        });

        ForceBindMacJob::dispatch(
            $audit->id,
            $customer->id,
            $normalizedMac,
            $router->id,
            $performedBy->id,
        );

        return $audit;
    }

    protected function normalizeMac(string $mac): ?string
    {
        $value = strtolower(preg_replace('/[^a-f0-9]/i', '', $mac) ?? '');

        if (strlen($value) !== 12) {
            return null;
        }

        return implode(':', str_split($value, 2));
    }
}
