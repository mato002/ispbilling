<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InventoryAssignment;
use App\Models\InventoryCategoryThreshold;
use App\Models\InventoryItem;
use App\Models\InventoryPurchase;
use App\Models\InventoryTechnicianAssignment;
use App\Models\InventoryTransaction;
use App\Models\Router;
use App\Models\User;
use App\Support\InventoryCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function __construct(
        protected InventoryQrCodeService $qrCodeService,
        protected InventoryInstallationService $installationService,
    ) {}

    public function createItem(int $ispId, array $data, ?User $performedBy = null): InventoryItem
    {
        return DB::transaction(function () use ($ispId, $data, $performedBy) {
            $item = InventoryItem::create([
                'isp_id' => $ispId,
                'inventory_purchase_id' => $data['inventory_purchase_id'] ?? null,
                'name' => $data['name'],
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'serial_number' => $data['serial_number'],
                'mac_address' => $data['mac_address'] ?? null,
                'category' => $data['category'],
                'status' => InventoryItem::STATUS_AVAILABLE,
                'purchase_price' => $data['purchase_price'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? null,
                'warranty_expiry' => $data['warranty_expiry'] ?? null,
                'location' => $data['location'] ?? 'warehouse',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->logTransaction(
                $item,
                InventoryTransaction::ACTION_ADDED,
                $performedBy,
                notes: $data['notes'] ?? null,
            );

            $this->qrCodeService->generate($item);

            return $item->fresh();
        });
    }

    public function updateItem(InventoryItem $item, array $data): InventoryItem
    {
        $previous = [
            'serial_number' => $item->serial_number,
            'mac_address' => $item->mac_address,
        ];

        $item->update([
            'name' => $data['name'],
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'serial_number' => $data['serial_number'],
            'mac_address' => $data['mac_address'] ?? null,
            'category' => $data['category'],
            'purchase_price' => $data['purchase_price'] ?? null,
            'purchase_date' => $data['purchase_date'] ?? null,
            'warranty_expiry' => $data['warranty_expiry'] ?? null,
            'location' => $data['location'] ?? $item->location,
            'notes' => $data['notes'] ?? null,
        ]);

        $item = $item->fresh();
        $this->qrCodeService->regenerateIfIdentityChanged($item, $previous);

        return $item->fresh();
    }

    public function assignItem(
        InventoryItem $item,
        Customer $customer,
        array $data,
        User $performedBy,
    ): InventoryAssignment {
        if (! $item->isAssignable()) {
            throw new InvalidArgumentException('This device cannot be assigned. It may be unavailable, with a technician, or already assigned to a customer.');
        }

        if ($item->activeAssignment()->exists()) {
            throw new InvalidArgumentException('This device already has an active assignment.');
        }

        if (InventoryAssignment::query()
            ->where('customer_id', $customer->id)
            ->where('is_active', true)
            ->exists()) {
            throw new InvalidArgumentException('Customer already has an active inventory device. Return or replace it first.');
        }

        return DB::transaction(function () use ($item, $customer, $data, $performedBy) {
            if (! empty($data['router_id'])) {
                Router::forIsp($item->isp_id)->whereKey($data['router_id'])->firstOrFail();
            }

            $assignment = InventoryAssignment::create([
                'isp_id' => $item->isp_id,
                'inventory_item_id' => $item->id,
                'customer_id' => $customer->id,
                'router_id' => $data['router_id'] ?? null,
                'technician_user_id' => $data['technician_user_id'] ?? null,
                'assigned_by_user_id' => $performedBy->id,
                'installation_date' => $data['installation_date'] ?? null,
                'installation_location' => $data['installation_location'] ?? null,
                'installation_gps' => $data['installation_gps'] ?? null,
                'installation_status' => $data['installation_status'] ?? InventoryAssignment::INSTALLATION_PENDING,
                'assigned_at' => now(),
                'is_active' => true,
                'notes' => $data['notes'] ?? null,
            ]);

            $item->update([
                'status' => InventoryItem::STATUS_ASSIGNED,
                'location' => 'customer',
            ]);

            $this->logTransaction(
                $item,
                InventoryTransaction::ACTION_ASSIGNED,
                $performedBy,
                customerId: $customer->id,
                assignmentId: $assignment->id,
                notes: $data['notes'] ?? null,
            );

            if ($assignment->installation_status === InventoryAssignment::INSTALLATION_COMPLETED) {
                $this->installationService->finalizeInstallation($assignment->fresh(), $performedBy);
            }

            return $assignment->fresh();
        });
    }

    public function assignToTechnician(
        InventoryItem $item,
        User $technician,
        User $performedBy,
        ?string $notes = null,
    ): InventoryTechnicianAssignment {
        if (! $technician->isTechnician() || $technician->isp_id !== $item->isp_id) {
            throw new InvalidArgumentException('Selected user is not a technician in this workspace.');
        }

        if (! $item->isIssuableToTechnician()) {
            throw new InvalidArgumentException('This device cannot be issued to a technician right now.');
        }

        return DB::transaction(function () use ($item, $technician, $performedBy, $notes) {
            $hold = InventoryTechnicianAssignment::create([
                'isp_id' => $item->isp_id,
                'inventory_item_id' => $item->id,
                'technician_user_id' => $technician->id,
                'assigned_by_user_id' => $performedBy->id,
                'assigned_at' => now(),
                'is_active' => true,
                'notes' => $notes,
            ]);

            $item->update(['location' => 'technician']);

            $this->logTransaction(
                $item,
                InventoryTransaction::ACTION_TECHNICIAN_ASSIGNED,
                $performedBy,
                notes: trim('Issued to '.$technician->name.'. '.($notes ?? '')),
            );

            return $hold;
        });
    }

    public function technicianReturnHold(
        InventoryTechnicianAssignment $hold,
        User $performedBy,
        ?string $notes = null,
    ): InventoryTechnicianAssignment {
        if (! $hold->is_active) {
            throw new InvalidArgumentException('This technician assignment is already closed.');
        }

        return DB::transaction(function () use ($hold, $performedBy, $notes) {
            $item = $hold->item;

            $hold->update([
                'is_active' => false,
                'returned_at' => now(),
            ]);

            $item->update(['location' => 'warehouse']);

            $this->logTransaction(
                $item,
                InventoryTransaction::ACTION_TECHNICIAN_RETURNED,
                $performedBy,
                notes: $notes,
            );

            return $hold->fresh();
        });
    }

    public function completeTechnicianInstallation(
        InventoryTechnicianAssignment $hold,
        Customer $customer,
        array $data,
        User $technician,
    ): InventoryAssignment {
        if (! $hold->is_active) {
            throw new InvalidArgumentException('This technician assignment is no longer active.');
        }

        if ($hold->technician_user_id !== $technician->id) {
            throw new InvalidArgumentException('You are not assigned to this device.');
        }

        $item = $hold->item;

        if ($item->status !== InventoryItem::STATUS_AVAILABLE) {
            throw new InvalidArgumentException('Device is no longer available for installation.');
        }

        return DB::transaction(function () use ($hold, $customer, $data, $technician, $item) {
            $hold->update([
                'is_active' => false,
                'returned_at' => now(),
            ]);

            $installData = array_merge($data, [
                'technician_user_id' => $technician->id,
                'installation_status' => InventoryAssignment::INSTALLATION_COMPLETED,
                'installation_date' => $data['installation_date'] ?? now()->toDateString(),
            ]);

            $assignment = $this->assignItem($item, $customer, $installData, $technician);

            $this->logTransaction(
                $item,
                InventoryTransaction::ACTION_INSTALLED,
                $technician,
                customerId: $customer->id,
                assignmentId: $assignment->id,
                notes: $data['notes'] ?? null,
            );

            return $assignment->fresh(['item', 'customer', 'router']);
        });
    }

    public function completeInstallation(InventoryAssignment $assignment, User $performedBy): InventoryAssignment
    {
        if (! $assignment->is_active) {
            throw new InvalidArgumentException('Assignment is already closed.');
        }

        $assignment->update([
            'installation_status' => InventoryAssignment::INSTALLATION_COMPLETED,
            'installation_date' => $assignment->installation_date ?? now()->toDateString(),
        ]);

        return $this->installationService->finalizeInstallation($assignment->fresh(), $performedBy);
    }

    public function markFaultyFromTechnicianHold(
        InventoryTechnicianAssignment $hold,
        User $technician,
        ?string $notes = null,
    ): InventoryItem {
        if (! $hold->is_active) {
            throw new InvalidArgumentException('This technician assignment is already closed.');
        }

        if ($hold->technician_user_id !== $technician->id) {
            throw new InvalidArgumentException('You are not assigned to this device.');
        }

        return DB::transaction(function () use ($hold, $technician, $notes) {
            $hold->update([
                'is_active' => false,
                'returned_at' => now(),
            ]);

            return $this->markFaulty($hold->item, $technician, $notes);
        });
    }

    public function returnItem(InventoryAssignment $assignment, User $performedBy, ?string $notes = null): InventoryAssignment
    {
        if (! $assignment->is_active) {
            throw new InvalidArgumentException('This assignment is already closed.');
        }

        return DB::transaction(function () use ($assignment, $performedBy, $notes) {
            $item = $assignment->item;
            $customer = $assignment->customer;

            $assignment->update([
                'is_active' => false,
                'returned_at' => now(),
            ]);

            $item->update([
                'status' => InventoryItem::STATUS_AVAILABLE,
                'location' => 'warehouse',
            ]);

            if ($customer) {
                $this->installationService->clearCustomerDeviceLink($customer, $item);
            }

            $this->logTransaction(
                $item,
                InventoryTransaction::ACTION_RETURNED,
                $performedBy,
                customerId: $assignment->customer_id,
                assignmentId: $assignment->id,
                notes: $notes,
            );

            return $assignment->fresh();
        });
    }

    public function markFaulty(InventoryItem $item, User $performedBy, ?string $notes = null): InventoryItem
    {
        return DB::transaction(function () use ($item, $performedBy, $notes) {
            $activeAssignment = $item->activeAssignment;
            $activeTechHold = $item->activeTechnicianAssignment;

            if ($activeAssignment) {
                $activeAssignment->update([
                    'is_active' => false,
                    'returned_at' => now(),
                ]);

                if ($activeAssignment->customer && $item) {
                    $this->installationService->clearCustomerDeviceLink($activeAssignment->customer, $item);
                }
            }

            if ($activeTechHold) {
                $activeTechHold->update([
                    'is_active' => false,
                    'returned_at' => now(),
                ]);
            }

            $item->update([
                'status' => InventoryItem::STATUS_FAULTY,
                'location' => 'warehouse',
            ]);

            $this->logTransaction(
                $item,
                InventoryTransaction::ACTION_FAULTY,
                $performedBy,
                customerId: $activeAssignment?->customer_id,
                assignmentId: $activeAssignment?->id,
                notes: $notes,
            );

            return $item->fresh();
        });
    }

    public function retireItem(InventoryItem $item, User $performedBy, ?string $notes = null): InventoryItem
    {
        if ($item->status === InventoryItem::STATUS_ASSIGNED) {
            throw new InvalidArgumentException('Return or mark as faulty before retiring an assigned device.');
        }

        return DB::transaction(function () use ($item, $performedBy, $notes) {
            $item->update([
                'status' => InventoryItem::STATUS_RETIRED,
                'location' => 'warehouse',
            ]);

            $this->logTransaction(
                $item,
                InventoryTransaction::ACTION_RETIRED,
                $performedBy,
                notes: $notes,
            );

            return $item->fresh();
        });
    }

    public function replaceItem(
        InventoryAssignment $oldAssignment,
        InventoryItem $replacementItem,
        User $performedBy,
        array $data = [],
    ): InventoryAssignment {
        if (! $oldAssignment->is_active) {
            throw new InvalidArgumentException('Cannot replace a closed assignment.');
        }

        if (! $replacementItem->isAssignable()) {
            throw new InvalidArgumentException('Replacement device must be available.');
        }

        return DB::transaction(function () use ($oldAssignment, $replacementItem, $performedBy, $data) {
            $oldItem = $oldAssignment->item;

            $oldAssignment->update([
                'is_active' => false,
                'returned_at' => now(),
            ]);

            $oldItem->update([
                'status' => InventoryItem::STATUS_FAULTY,
                'location' => 'warehouse',
            ]);

            $this->logTransaction(
                $oldItem,
                InventoryTransaction::ACTION_FAULTY,
                $performedBy,
                customerId: $oldAssignment->customer_id,
                assignmentId: $oldAssignment->id,
                relatedItemId: $replacementItem->id,
                notes: $data['notes'] ?? 'Replaced with '.$replacementItem->serial_number,
            );

            $newAssignment = InventoryAssignment::create([
                'isp_id' => $oldAssignment->isp_id,
                'inventory_item_id' => $replacementItem->id,
                'customer_id' => $oldAssignment->customer_id,
                'router_id' => $data['router_id'] ?? $oldAssignment->router_id,
                'technician_user_id' => $data['technician_user_id'] ?? $oldAssignment->technician_user_id,
                'assigned_by_user_id' => $performedBy->id,
                'installation_date' => $data['installation_date'] ?? now()->toDateString(),
                'installation_location' => $data['installation_location'] ?? $oldAssignment->installation_location,
                'installation_gps' => $data['installation_gps'] ?? $oldAssignment->installation_gps,
                'installation_status' => $data['installation_status'] ?? InventoryAssignment::INSTALLATION_COMPLETED,
                'assigned_at' => now(),
                'is_active' => true,
                'notes' => $data['notes'] ?? 'Replacement for '.$oldItem->serial_number,
            ]);

            $oldAssignment->update(['replaced_by_assignment_id' => $newAssignment->id]);

            $replacementItem->update([
                'status' => InventoryItem::STATUS_ASSIGNED,
                'location' => 'customer',
            ]);

            $this->logTransaction(
                $replacementItem,
                InventoryTransaction::ACTION_REPLACED,
                $performedBy,
                customerId: $oldAssignment->customer_id,
                assignmentId: $newAssignment->id,
                relatedItemId: $oldItem->id,
                notes: $data['notes'] ?? 'Replaced '.$oldItem->serial_number,
            );

            if ($customer = $oldAssignment->customer) {
                $this->installationService->clearCustomerDeviceLink($customer, $oldItem);
            }

            if ($newAssignment->installation_status === InventoryAssignment::INSTALLATION_COMPLETED) {
                $this->installationService->finalizeInstallation($newAssignment->fresh(), $performedBy);
            }

            return $newAssignment->fresh(['item', 'customer', 'router']);
        });
    }

    public function createPurchase(int $ispId, array $data, User $performedBy): InventoryPurchase
    {
        return InventoryPurchase::create([
            'isp_id' => $ispId,
            'vendor_name' => $data['vendor_name'],
            'invoice_number' => $data['invoice_number'] ?? null,
            'total_cost' => $data['total_cost'] ?? 0,
            'purchase_date' => $data['purchase_date'],
            'notes' => $data['notes'] ?? null,
            'created_by_user_id' => $performedBy->id,
        ]);
    }

    /**
     * @return array<string, int>
     */
    public function stockCounts(int $ispId): array
    {
        $counts = InventoryItem::forIsp($ispId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'available' => (int) ($counts['available'] ?? 0),
            'assigned' => (int) ($counts['assigned'] ?? 0),
            'faulty' => (int) ($counts['faulty'] ?? 0),
            'retired' => (int) ($counts['retired'] ?? 0),
            'total' => array_sum($counts),
        ];
    }

    /**
     * @return Collection<int, array{category: string, label: string, available: int, threshold: int, is_low: bool}>
     */
    public function lowStockAlerts(int $ispId): Collection
    {
        $thresholds = InventoryCategoryThreshold::forIsp($ispId)
            ->get()
            ->keyBy('category');

        $availableByCategory = InventoryItem::forIsp($ispId)
            ->where('status', InventoryItem::STATUS_AVAILABLE)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return collect(InventoryCatalog::CATEGORIES)->map(function ($label, $category) use ($thresholds, $availableByCategory) {
            $threshold = (int) ($thresholds->get($category)?->low_stock_threshold ?? 5);
            $available = (int) ($availableByCategory[$category] ?? 0);

            return [
                'category' => $category,
                'label' => $label,
                'available' => $available,
                'threshold' => $threshold,
                'is_low' => $available <= $threshold,
            ];
        })->filter(fn ($row) => $row['is_low']);
    }

    public function syncCategoryThresholds(int $ispId, array $thresholds): void
    {
        foreach (InventoryCatalog::CATEGORIES as $category => $label) {
            if (! array_key_exists($category, $thresholds)) {
                continue;
            }

            InventoryCategoryThreshold::updateOrCreate(
                ['isp_id' => $ispId, 'category' => $category],
                ['low_stock_threshold' => max(0, (int) $thresholds[$category])],
            );
        }
    }

    protected function logTransaction(
        InventoryItem $item,
        string $action,
        ?User $performedBy = null,
        ?int $customerId = null,
        ?int $assignmentId = null,
        ?int $relatedItemId = null,
        ?string $notes = null,
    ): InventoryTransaction {
        return InventoryTransaction::create([
            'isp_id' => $item->isp_id,
            'inventory_item_id' => $item->id,
            'action' => $action,
            'performed_by_user_id' => $performedBy?->id,
            'customer_id' => $customerId,
            'inventory_assignment_id' => $assignmentId,
            'related_item_id' => $relatedItemId,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
