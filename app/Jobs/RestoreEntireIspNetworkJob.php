<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\ISP;
use App\Services\MikroTikService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Re-enable RouterOS users suspended by platform SaaS enforcement.
 */
class RestoreEntireIspNetworkJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $ispId) {}

    public function handle(): void
    {
        $isp = ISP::query()->find($this->ispId);
        if (! $isp || ! $isp->is_active || $isp->platform_access_locked) {
            Log::info('RestoreEntireIspNetworkJob: skipped (ISP inactive or still locked)', ['isp_id' => $this->ispId]);

            return;
        }

        $enabled = 0;
        $errors = 0;

        Customer::query()
            ->where('isp_id', $this->ispId)
            ->where('custom_attributes->platform_service_suspended', true)
            ->with(['router'])
            ->chunkById(100, function ($customers) use (&$enabled, &$errors) {
                foreach ($customers as $customer) {
                    $router = $customer->router;
                    if ($router && $router->is_active && $customer->mikrotik_user_id) {
                        try {
                            $svc = new MikroTikService($router);
                            if ($svc->enableUser($customer)) {
                                $enabled++;
                            }
                        } catch (\Throwable $e) {
                            $errors++;
                            Log::warning('RestoreEntireIspNetworkJob: router op failed', [
                                'customer_id' => $customer->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $attrs = $customer->custom_attributes ?? [];
                    unset($attrs['platform_service_suspended']);

                    $customer->update([
                        'status' => 'active',
                        'connection_status' => 'connected',
                        'custom_attributes' => $attrs,
                        'suspended_at' => null,
                    ]);
                }
            });

        Log::info('RestoreEntireIspNetworkJob: completed platform restore pass', [
            'isp_id' => $this->ispId,
            'enable_success' => $enabled,
            'errors' => $errors,
        ]);
    }
}
