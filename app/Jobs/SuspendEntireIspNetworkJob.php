<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\ISP;
use App\Services\MikroTikService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * SaaS enforcement: when an ISP is deactivated by the platform, push suspend to RouterOS
 * for every provisioned customer and align local service state.
 */
class SuspendEntireIspNetworkJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public int $ispId,
        public bool $markPlatformSuspension = false,
    ) {}

    public function handle(): void
    {
        $isp = ISP::query()->find($this->ispId);
        if (! $isp || $isp->is_active) {
            Log::info('SuspendEntireIspNetworkJob: skipped (ISP missing or active again)', ['isp_id' => $this->ispId]);

            return;
        }

        $disabled = 0;
        $sessions = 0;
        $errors = 0;

        Customer::query()
            ->where('isp_id', $this->ispId)
            ->with(['router'])
            ->chunkById(100, function ($customers) use (&$disabled, &$sessions, &$errors) {
                foreach ($customers as $customer) {
                    $router = $customer->router;
                    if (! $router || ! $router->is_active) {
                        continue;
                    }

                    try {
                        $svc = new MikroTikService($router);
                        if ($customer->mikrotik_user_id || $customer->username) {
                            $svc->disconnectSession($customer);
                            $sessions++;
                        }
                        if ($customer->mikrotik_user_id) {
                            if ($svc->disableUser($customer)) {
                                $disabled++;
                            }
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::warning('SuspendEntireIspNetworkJob: router op failed', [
                            'customer_id' => $customer->id,
                            'router_id' => $customer->router_id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $attrs = $customer->custom_attributes ?? [];
                    if ($this->markPlatformSuspension) {
                        $attrs['platform_service_suspended'] = true;
                    }

                    $customer->update([
                        'status' => 'suspended',
                        'connection_status' => 'suspended',
                        'suspended_at' => now(),
                        'custom_attributes' => $attrs,
                    ]);
                }
            });

        Log::warning('SuspendEntireIspNetworkJob: completed SaaS kill-switch pass', [
            'isp_id' => $this->ispId,
            'disconnect_attempts' => $sessions,
            'disable_success' => $disabled,
            'errors' => $errors,
        ]);
    }
}
