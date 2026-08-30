<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Outage;
use App\Models\OutageCompensationLog;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OutageCompensationService
{
    /**
     * Customers linked to the outage router (hotspot + PPPoE).
     *
     * @return Collection<int, Customer>
     */
    public function affectedCustomers(Outage $outage): Collection
    {
        if (! $outage->router_id) {
            return new Collection;
        }

        return Customer::query()
            ->where('isp_id', $outage->isp_id)
            ->where('router_id', $outage->router_id)
            ->with([
                'subscriptions' => fn ($q) => $q
                    ->where('status', 'active')
                    ->where('end_date', '>=', now()->startOfDay()),
            ])
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Manually apply compensation to selected customers. Never auto-runs.
     *
     * @param  array<int>  $customerIds
     * @return array{applied: int, skipped: int, errors: array<string>}
     */
    public function applyCompensation(
        Outage $outage,
        array $customerIds,
        string $compensationType,
        int $durationValue,
        string $durationUnit,
        User $admin,
    ): array {
        $customerIds = array_values(array_unique(array_map('intval', $customerIds)));
        $allowedIds = $this->affectedCustomers($outage)->pluck('id')->all();

        $applied = 0;
        $skipped = 0;
        $errors = [];

        foreach ($customerIds as $customerId) {
            if (! in_array($customerId, $allowedIds, true)) {
                $skipped++;
                $errors[] = "Customer #{$customerId} is not in the affected list for this outage.";

                continue;
            }

            $customer = Customer::where('isp_id', $outage->isp_id)->find($customerId);
            if (! $customer) {
                $skipped++;
                $errors[] = "Customer #{$customerId} not found.";

                continue;
            }

            try {
                $result = match ($compensationType) {
                    OutageCompensationLog::TYPE_SUBSCRIPTION => $this->extendSubscription(
                        $outage,
                        $customer,
                        $durationValue,
                        $durationUnit,
                        $admin,
                    ),
                    OutageCompensationLog::TYPE_VOUCHER => $this->extendVouchers(
                        $outage,
                        $customer,
                        $durationValue,
                        $durationUnit,
                        $admin,
                    ),
                    default => ['ok' => false, 'message' => 'Unknown compensation type.'],
                };

                if ($result['ok']) {
                    $applied += $result['count'] ?? 1;
                } else {
                    $skipped++;
                    $errors[] = "{$customer->full_name}: {$result['message']}";
                }
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "{$customer->full_name}: {$e->getMessage()}";
            }
        }

        return compact('applied', 'skipped', 'errors');
    }

    /**
     * @return array{ok: bool, message: string, count?: int}
     */
    protected function extendSubscription(
        Outage $outage,
        Customer $customer,
        int $durationValue,
        string $durationUnit,
        User $admin,
    ): array {
        $subscription = $customer->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now()->startOfDay())
            ->orderByDesc('end_date')
            ->first();

        if (! $subscription) {
            return ['ok' => false, 'message' => 'No active subscription to extend.'];
        }

        return DB::transaction(function () use ($outage, $customer, $subscription, $durationValue, $durationUnit, $admin) {
            $previousEnd = $subscription->end_date->format('Y-m-d');
            $newEnd = $this->addDuration($subscription->end_date->copy()->endOfDay(), $durationValue, $durationUnit);

            $subscription->update([
                'end_date' => $newEnd->toDateString(),
                'renewal_date' => $newEnd->toDateString(),
            ]);

            OutageCompensationLog::create([
                'outage_id' => $outage->id,
                'isp_id' => $outage->isp_id,
                'admin_user_id' => $admin->id,
                'customer_id' => $customer->id,
                'compensation_type' => OutageCompensationLog::TYPE_SUBSCRIPTION,
                'duration_value' => $durationValue,
                'duration_unit' => $durationUnit,
                'subscription_id' => $subscription->id,
                'previous_expires_at' => $previousEnd,
                'new_expires_at' => $newEnd->toDateString(),
                'notes' => "Manual outage compensation for outage #{$outage->id}",
            ]);

            return ['ok' => true, 'message' => 'Subscription extended.', 'count' => 1];
        });
    }

    /**
     * @return array{ok: bool, message: string, count?: int}
     */
    protected function extendVouchers(
        Outage $outage,
        Customer $customer,
        int $durationValue,
        string $durationUnit,
        User $admin,
    ): array {
        $vouchers = $this->activeVouchersForCustomer($outage->isp_id, $customer->id);

        if ($vouchers->isEmpty()) {
            return ['ok' => false, 'message' => 'No active vouchers to extend.'];
        }

        $count = 0;

        DB::transaction(function () use ($outage, $customer, $vouchers, $durationValue, $durationUnit, $admin, &$count) {
            foreach ($vouchers as $voucher) {
                $previous = $voucher->expires_at?->toDateTimeString() ?? 'none';
                $base = $voucher->expires_at && $voucher->expires_at->isFuture()
                    ? $voucher->expires_at->copy()
                    : now();
                $newExpires = $this->addDuration($base, $durationValue, $durationUnit);

                $voucher->update(['expires_at' => $newExpires]);

                OutageCompensationLog::create([
                    'outage_id' => $outage->id,
                    'isp_id' => $outage->isp_id,
                    'admin_user_id' => $admin->id,
                    'customer_id' => $customer->id,
                    'compensation_type' => OutageCompensationLog::TYPE_VOUCHER,
                    'duration_value' => $durationValue,
                    'duration_unit' => $durationUnit,
                    'voucher_id' => $voucher->id,
                    'previous_expires_at' => $previous,
                    'new_expires_at' => $newExpires->toDateTimeString(),
                    'notes' => "Manual outage compensation for voucher {$voucher->code} (outage #{$outage->id})",
                ]);

                $count++;
            }
        });

        return [
            'ok' => true,
            'message' => "Extended {$count} voucher(s).",
            'count' => $count,
        ];
    }

    /**
     * @return Collection<int, Voucher>
     */
    protected function activeVouchersForCustomer(int $ispId, int $customerId): Collection
    {
        $query = Voucher::query()
            ->where('isp_id', $ispId)
            ->where('status', 'active')
            ->where(function ($q) use ($customerId) {
                $q->where('used_by_customer_id', $customerId);

                if (Schema::hasColumn('vouchers', 'assigned_to')) {
                    $q->orWhere('assigned_to', $customerId);
                }
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        return $query->get();
    }

    protected function addDuration(Carbon $base, int $value, string $unit): Carbon
    {
        return match ($unit) {
            'hours' => $base->copy()->addHours($value),
            'days' => $base->copy()->addDays($value),
            default => $base->copy()->addDays($value),
        };
    }
}
