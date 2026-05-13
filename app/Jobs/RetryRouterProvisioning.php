<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\MikroTikService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Retries {@see MikroTikService::enableUser()} after payment when the router was unreachable.
 *
 * Chains up to {@see self::MAX_ATTEMPTS} attempts spaced five minutes apart (~1 hour window).
 */
class RetryRouterProvisioning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const MAX_ATTEMPTS = 12;

    public int $timeout = 90;

    public function __construct(
        public int $customerId,
        public ?int $paymentId = null,
        public int $attempt = 1,
    ) {}

    public function handle(): void
    {
        $customer = Customer::query()->with('router')->find($this->customerId);

        if (! $customer || ! $customer->router || ! $customer->router->is_active) {
            Log::info('RetryRouterProvisioning: skipped (missing customer, router, or inactive router)', [
                'customer_id' => $this->customerId,
                'attempt' => $this->attempt,
            ]);

            return;
        }

        if (! $customer->username && ! $customer->mikrotik_user_id) {
            Log::info('RetryRouterProvisioning: skipped (no username or RouterOS id to target)', [
                'customer_id' => $this->customerId,
            ]);

            return;
        }

        if ($customer->router->reachability_status === 'broken') {
            Log::info('RetryRouterProvisioning: skipped (router circuit open / broken)', [
                'customer_id' => $this->customerId,
                'router_id' => $customer->router_id,
            ]);

            return;
        }

        $mikrotik = new MikroTikService($customer->router);

        if ($mikrotik->enableUser($customer)) {
            Log::info('RetryRouterProvisioning: enableUser succeeded after prior failure', [
                'customer_id' => $customer->id,
                'attempt' => $this->attempt,
                'payment_id' => $this->paymentId,
                'router_id' => $customer->router_id,
            ]);

            return;
        }

        Log::warning('RetryRouterProvisioning: enableUser still failing', [
            'customer_id' => $customer->id,
            'attempt' => $this->attempt,
            'payment_id' => $this->paymentId,
            'router_id' => $customer->router_id,
        ]);

        if ($this->attempt < self::MAX_ATTEMPTS) {
            self::dispatch($this->customerId, $this->paymentId, $this->attempt + 1)
                ->delay(now()->addMinutes(5));

            return;
        }

        Log::error('RetryRouterProvisioning: exhausted retries; workspace notification created', [
            'customer_id' => $customer->id,
            'payment_id' => $this->paymentId,
            'router_id' => $customer->router_id,
        ]);

        app(NotificationService::class)->provisioningRetriesExhausted($customer, $this->paymentId);
    }
}
