<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\Router;
use App\Models\Plan;
use App\Models\ISP;
use App\Services\MikroTikService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class VoucherService
{
    /**
     * Generate a single voucher
     */
    public function generate(
        ISP $isp,
        Router $router,
        array $data,
        ?Plan $plan = null,
        ?int $createdBy = null
    ): Voucher {
        $voucher = Voucher::create([
            'isp_id' => $isp->id,
            'router_id' => $router->id,
            'plan_id' => $plan?->id,
            'created_by' => $createdBy ?? auth()->id(),
            'voucher_code' => $data['voucher_code'] ?? Voucher::generateCode(),
            'voucher_type' => $data['voucher_type'] ?? 'time',
            'time_limit_hours' => $data['time_limit_hours'] ?? null,
            'data_limit_mb' => $data['data_limit_mb'] ?? null,
            'speed_profile' => $data['speed_profile'] ?? $plan?->speed_profile ?? 'default',
            'price' => $data['price'] ?? $plan?->price ?? null,
            'expires_at' => isset($data['expires_at']) ? $data['expires_at'] : null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

            // Create voucher user on MikroTik router
            $createdOnRouter = $this->createVoucherOnRouter($voucher, $router);
            if (! $createdOnRouter) {
                Log::warning('Voucher saved but MikroTik user was not created', [
                    'voucher_id' => $voucher->id,
                    'router_id' => $router->id,
                    'voucher_code' => $voucher->voucher_code,
                ]);
            }

            return $voucher;
    }

    /**
     * Generate multiple vouchers in bulk
     */
    public function generateBulk(
        ISP $isp,
        Router $router,
        int $quantity,
        array $data,
        ?Plan $plan = null,
        ?int $createdBy = null
    ): array {
        $vouchers = [];
        $errors = [];

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $quantity; $i++) {
                try {
                    $voucher = $this->generate($isp, $router, $data, $plan, $createdBy);
                    $vouchers[] = $voucher;
                } catch (Exception $e) {
                    $errors[] = [
                        'index' => $i + 1,
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Failed to generate voucher #{$i}: " . $e->getMessage());
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'vouchers' => $vouchers,
            'errors' => $errors,
            'success_count' => count($vouchers),
            'error_count' => count($errors),
        ];
    }

    /**
     * Create voucher user on MikroTik router
     */
    protected function createVoucherOnRouter(Voucher $voucher, Router $router): bool
    {
        try {
            $mikrotik = new MikroTikService($router);

            if (!$mikrotik->connect()) {
                Log::error("Failed to connect to router {$router->id} for voucher {$voucher->id}");
                return false;
            }

            $params = [
                'name' => $voucher->voucher_code,
                'password' => $voucher->voucher_code, // Use voucher code as password
                'profile' => $voucher->speed_profile ?? 'default',
            ];

            // Add time limit if specified
            if ($voucher->voucher_type === 'time' && $voucher->time_limit_hours) {
                $params['limit-uptime'] = $voucher->time_limit_hours . 'h';
            }

            // Add data limit if specified
            if ($voucher->voucher_type === 'data' && $voucher->data_limit_mb) {
                $params['limit-bytes-total'] = $voucher->data_limit_mb . 'M';
            }

            // Add expiry if specified
            if ($voucher->expires_at) {
                $params['limit-uptime'] = ($voucher->expires_at->diffInHours(now()) . 'h');
            }

            $connection = $mikrotik->getConnection();
            if (!$connection) {
                return false;
            }
            $response = $connection->comm('/ip/hotspot/user/add', $params);

            if (isset($response[0]['.id'])) {
                $voucher->update([
                    'metadata' => array_merge($voucher->metadata ?? [], [
                        'mikrotik_user_id' => $response[0]['.id'],
                        'router_response' => $response[0] ?? null,
                    ]),
                ]);
                return true;
            }

            Log::error("Failed to create voucher user on router: Invalid response", [
                'voucher_id' => $voucher->id,
                'response' => $response,
            ]);

            return false;
        } catch (Exception $e) {
            Log::error("Failed to create voucher on router: " . $e->getMessage(), [
                'voucher_id' => $voucher->id,
                'router_id' => $router->id,
            ]);
            return false;
        }
    }

    /**
     * Activate/use a voucher
     */
    public function activate(Voucher $voucher, ?int $customerId = null): bool
    {
        if (!$voucher->isValid()) {
            return false;
        }

        // Use assigned customer if no customer ID provided
        $finalCustomerId = $customerId ?? $voucher->assigned_to;
        $voucher->markAsUsed($finalCustomerId);

        // Create payment record for voucher usage (even if price is 0, for tracking)
        $payment = \App\Models\Payment::create([
            'isp_id' => $voucher->isp_id,
            'customer_id' => $finalCustomerId, // Can be null for anonymous vouchers
            'voucher_id' => $voucher->id,
            'payment_method' => 'voucher',
            'amount' => $voucher->price ?? 0,
            'status' => 'pending',
            'notes' => "Voucher redemption: {$voucher->voucher_code}",
            'metadata' => [
                'voucher_code' => $voucher->voucher_code,
                'voucher_type' => $voucher->voucher_type,
                'time_limit_hours' => $voucher->time_limit_hours,
                'data_limit_mb' => $voucher->data_limit_mb,
                'assigned_to' => $voucher->assigned_to,
            ],
        ]);

        $payment->markAsCompleted();

        // Immediately enable the user on router
        if ($voucher->router && isset($voucher->metadata['mikrotik_user_id'])) {
            try {
                $mikrotik = new MikroTikService($voucher->router);
                if ($mikrotik->connect()) {
                    $connection = $mikrotik->getConnection();
                    if ($connection) {
                        $connection->comm('/ip/hotspot/user/enable', [
                            '.id' => $voucher->metadata['mikrotik_user_id'],
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::error("Failed to enable voucher user on router: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Cancel/deactivate an unused active voucher and remove RouterOS user when present.
     */
    public function deactivate(Voucher $voucher): bool
    {
        if (! in_array($voucher->status, ['active'], true)) {
            return false;
        }

        $this->deleteFromRouter($voucher);
        $voucher->markAsCancelled();

        return true;
    }

    /**
     * Delete voucher from router
     */
    public function deleteFromRouter(Voucher $voucher): bool
    {
        if (!$voucher->router || !isset($voucher->metadata['mikrotik_user_id'])) {
            return false;
        }

        try {
            $mikrotik = new MikroTikService($voucher->router);
            if ($mikrotik->connect()) {
                $connection = $mikrotik->getConnection();
                if ($connection) {
                    $connection->comm('/ip/hotspot/user/remove', [
                        '.id' => $voucher->metadata['mikrotik_user_id'],
                    ]);
                    return true;
                }
            }
        } catch (Exception $e) {
            Log::error("Failed to delete voucher from router: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Expire old vouchers
     */
    public function expireOldVouchers(): int
    {
        $expired = Voucher::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expired as $voucher) {
            $voucher->markAsExpired();
            $count++;
        }

        return $count;
    }
}

