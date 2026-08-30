<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class InventoryInstallationService
{
    /**
     * Link installed inventory device to customer account and enable internet service.
     * Does not modify billing, subscriptions, or payments.
     */
    public function finalizeInstallation(InventoryAssignment $assignment, User $performedBy): InventoryAssignment
    {
        $assignment->load(['item', 'customer', 'router']);

        if ($assignment->installation_status !== InventoryAssignment::INSTALLATION_COMPLETED) {
            throw new InvalidArgumentException('Installation must be marked completed before service activation.');
        }

        $customer = $assignment->customer;
        $item = $assignment->item;

        if (! $customer || ! $item) {
            throw new InvalidArgumentException('Installation record is missing customer or device.');
        }

        $this->assertSingleActiveDevice($customer, $item, $assignment);

        $router = $this->resolveRouter($customer, $assignment);
        $previousStatus = $customer->status;

        $customer->update([
            'inventory_item_id' => $item->id,
            'installation_date' => $assignment->installation_date ?? now(),
            'router_id' => $assignment->router_id ?? $customer->router_id,
            'status' => $this->resolveCustomerStatusAfterInstall($customer),
        ]);

        $this->syncCustomerDeviceRecord($customer, $item);

        $activation = $this->activateInternetService($customer->fresh(), $item, $router);

        $this->logActivation($customer->fresh(), $item, $assignment, $performedBy, $previousStatus, $activation);

        return $assignment->fresh(['item', 'customer', 'router']);
    }

    public function clearCustomerDeviceLink(Customer $customer, InventoryItem $item): void
    {
        if ((int) $customer->inventory_item_id === (int) $item->id) {
            $customer->update(['inventory_item_id' => null]);
        }

        CustomerDevice::where('customer_id', $customer->id)
            ->where('mac_address', $this->normalizeMac($item->mac_address))
            ->update(['is_active' => false]);
    }

    protected function assertSingleActiveDevice(Customer $customer, InventoryItem $item, InventoryAssignment $assignment): void
    {
        $otherAssignment = InventoryAssignment::query()
            ->where('customer_id', $customer->id)
            ->where('is_active', true)
            ->where('id', '!=', $assignment->id)
            ->exists();

        if ($otherAssignment) {
            throw new InvalidArgumentException('Customer already has another active inventory device assigned.');
        }

        if ($customer->inventory_item_id
            && (int) $customer->inventory_item_id !== (int) $item->id
            && InventoryAssignment::query()
                ->where('customer_id', $customer->id)
                ->where('inventory_item_id', $customer->inventory_item_id)
                ->where('is_active', true)
                ->exists()) {
            throw new InvalidArgumentException('Customer already has a linked active device.');
        }
    }

    protected function resolveRouter(Customer $customer, InventoryAssignment $assignment): ?Router
    {
        $routerId = $assignment->router_id ?? $customer->router_id;

        if (! $routerId) {
            return null;
        }

        return Router::forIsp($customer->isp_id)->whereKey($routerId)->first();
    }

    protected function resolveCustomerStatusAfterInstall(Customer $customer): string
    {
        if (in_array($customer->status, ['pending_installation', 'inactive'], true)) {
            return 'active';
        }

        return $customer->status;
    }

    protected function syncCustomerDeviceRecord(Customer $customer, InventoryItem $item): void
    {
        if (! $item->mac_address) {
            return;
        }

        CustomerDevice::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        CustomerDevice::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'mac_address' => $this->normalizeMac($item->mac_address),
            ],
            [
                'device_name' => $item->name,
                'device_type' => $item->category,
                'is_active' => true,
                'is_blocked' => false,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ],
        );
    }

    /**
     * @return array{success: bool, connection_type: string|null, message: string, router_bound: bool}
     */
    protected function activateInternetService(Customer $customer, InventoryItem $item, ?Router $router): array
    {
        if (! $router) {
            return [
                'success' => false,
                'connection_type' => $customer->connection_type,
                'message' => 'No router configured for customer — device linked locally only.',
                'router_bound' => false,
            ];
        }

        try {
            $mikrotik = new MikroTikService($router);

            if ($customer->connection_type === 'hotspot') {
                $ok = $mikrotik->bindInventoryDeviceToCustomer($customer, $item)
                    && $mikrotik->activateHotspotUser($customer);

                return [
                    'success' => $ok,
                    'connection_type' => 'hotspot',
                    'message' => $ok ? 'Hotspot profile activated on router.' : 'Hotspot activation failed — check subscription and router.',
                    'router_bound' => $ok,
                ];
            }

            $bound = $mikrotik->bindInventoryDeviceToCustomer($customer, $item);
            $enabled = $mikrotik->enableUser($customer);

            return [
                'success' => $bound || $enabled,
                'connection_type' => 'pppoe',
                'message' => $enabled
                    ? 'PPPoE user enabled'.($bound ? ' with device MAC/username bound.' : '.')
                    : 'PPPoE enable failed — ensure MikroTik user exists for this customer.',
                'router_bound' => $bound,
            ];
        } catch (\Throwable $e) {
            Log::warning('Inventory service activation failed', [
                'customer_id' => $customer->id,
                'inventory_item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'connection_type' => $customer->connection_type,
                'message' => 'Router activation error: '.$e->getMessage(),
                'router_bound' => false,
            ];
        }
    }

    protected function logActivation(
        Customer $customer,
        InventoryItem $item,
        InventoryAssignment $assignment,
        User $performedBy,
        string $previousStatus,
        array $activation,
    ): void {
        ActivityLog::create([
            'isp_id' => $customer->isp_id,
            'user_id' => $performedBy->id ?? Auth::id(),
            'action' => 'inventory_service_activation',
            'model_type' => Customer::class,
            'model_id' => $customer->id,
            'description' => sprintf(
                'Inventory device %s linked to %s (%s). Status: %s → %s. %s',
                $item->serial_number,
                $customer->full_name,
                $customer->connection_type,
                $previousStatus,
                $customer->status,
                $activation['message']
            ),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'inventory_item_id' => $item->id,
                'inventory_assignment_id' => $assignment->id,
                'serial_number' => $item->serial_number,
                'mac_address' => $item->mac_address,
                'connection_type' => $customer->connection_type,
                'activation_success' => $activation['success'],
                'router_bound' => $activation['router_bound'],
            ],
        ]);
    }

    protected function normalizeMac(?string $mac): ?string
    {
        if (! $mac) {
            return null;
        }

        $value = strtolower(preg_replace('/[^a-f0-9]/i', '', $mac) ?? '');

        if (strlen($value) !== 12) {
            return $mac;
        }

        return implode(':', str_split($value, 2));
    }
}
