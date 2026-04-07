<?php

namespace App\Jobs;

use App\Models\ISP;
use App\Models\Voucher;
use App\Models\Customer;
use App\Services\MikroTikService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckExpiredVouchers implements ShouldQueue
{
    use Queueable;

    protected $isp;

    /**
     * Create a new job instance.
     */
    public function __construct(ISP $isp = null)
    {
        $this->isp = $isp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $isps = $this->isp ? collect([$this->isp]) : ISP::where('is_active', true)->get();

        foreach ($isps as $isp) {
            $this->checkExpiredVouchersForIsp($isp);
        }
    }

    /**
     * Check and suspend users with expired vouchers for a specific ISP
     */
    protected function checkExpiredVouchersForIsp(ISP $isp): void
    {
        // Get active vouchers that have expired
        // Vouchers that are:
        // 1. Status is 'active'
        // 2. Have an expires_at date that is in the past
        // 3. Are linked to a customer (used_by is not null)
        $expiredVouchers = Voucher::where('isp_id', $isp->id)
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNotNull('used_by') // Only vouchers that have been used by a customer
            ->with(['usedByCustomer.router'])
            ->get();

        foreach ($expiredVouchers as $voucher) {
            try {
                $customer = $voucher->usedByCustomer;

                if (!$customer) {
                    // Voucher was used but customer doesn't exist anymore
                    $voucher->markAsExpired();
                    Log::warning("Voucher {$voucher->id} expired but customer {$voucher->used_by} not found");
                    continue;
                }

                // Only suspend if customer is still active
                if ($customer->status === 'active' && $customer->router) {
                    try {
                        $mikrotik = new MikroTikService($customer->router);
                        
                        // Disconnect active session
                        $mikrotik->disconnectSession($customer);
                        
                        // Disable user on MikroTik
                        $mikrotik->disableUser($customer);
                        
                        // Suspend customer
                        $customer->suspend();
                        
                        Log::info("Suspended customer {$customer->id} due to expired voucher {$voucher->id}");
                    } catch (\Exception $e) {
                        Log::error("Failed to suspend customer {$customer->id} for expired voucher {$voucher->id}: " . $e->getMessage());
                    }
                } elseif ($customer->status !== 'active') {
                    // Customer already suspended/expired, just mark voucher as expired
                    Log::info("Voucher {$voucher->id} expired but customer {$customer->id} already suspended");
                }

                // Mark voucher as expired
                $voucher->markAsExpired();
                
                Log::info("Marked voucher {$voucher->id} as expired");
            } catch (\Exception $e) {
                Log::error("Failed to process expired voucher {$voucher->id}: " . $e->getMessage());
            }
        }
    }
}

