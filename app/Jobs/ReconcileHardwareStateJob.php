<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\MikroTikService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Aligns RouterOS hotspot/PPP secret disabled flag with {@see Customer::$status}
 * when the router was offline during suspend/activate jobs.
 */
class ReconcileHardwareStateJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $fixedEnable = 0;
        $fixedDisable = 0;
        $skipped = 0;

        Customer::query()
            ->whereNotNull('router_id')
            ->whereNotNull('mikrotik_user_id')
            ->with(['router'])
            ->chunkById(75, function ($customers) use (&$fixedEnable, &$fixedDisable, &$skipped) {
                foreach ($customers as $customer) {
                    $router = $customer->router;
                    if (! $router || ! $router->is_active) {
                        $skipped++;

                        continue;
                    }

                    if ($router->reachability_status === 'broken') {
                        $skipped++;

                        continue;
                    }

                    try {
                        $svc = new MikroTikService($router);
                        $disabled = $svc->getUserDisabledState($customer);
                        if ($disabled === null) {
                            $skipped++;

                            continue;
                        }

                        if ($customer->status === 'active' && $disabled === true) {
                            if ($svc->enableUser($customer)) {
                                $fixedEnable++;
                                Log::info('Hardware reconcile: enabled user matching active DB status', [
                                    'customer_id' => $customer->id,
                                    'router_id' => $router->id,
                                ]);
                            }

                            continue;
                        }

                        if ($customer->status === 'suspended' && $disabled === false) {
                            if ($svc->disableUser($customer)) {
                                $fixedDisable++;
                                Log::info('Hardware reconcile: disabled user matching suspended DB status', [
                                    'customer_id' => $customer->id,
                                    'router_id' => $router->id,
                                ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        $skipped++;
                        Log::warning('Hardware reconcile: customer skipped', [
                            'customer_id' => $customer->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('ReconcileHardwareStateJob finished', [
            'fixed_enable' => $fixedEnable,
            'fixed_disable' => $fixedDisable,
            'skipped' => $skipped,
        ]);
    }
}
