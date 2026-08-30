<?php

namespace App\Jobs;

use App\Models\ISP;
use App\Models\Voucher;
use App\Services\MikroTikService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckExpiredVouchers implements ShouldQueue
{
    use Queueable;

    protected $isp;

    public function __construct(?ISP $isp = null)
    {
        $this->isp = $isp;
    }

    public function handle(): void
    {
        $isps = $this->isp ? collect([$this->isp]) : ISP::where('is_active', true)->get();

        foreach ($isps as $isp) {
            $this->checkExpiredVouchersForIsp($isp);
        }
    }

    protected function checkExpiredVouchersForIsp(ISP $isp): void
    {
        $expiredVouchers = Voucher::where('isp_id', $isp->id)
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(['usedByCustomer.router', 'router'])
            ->get();

        foreach ($expiredVouchers as $voucher) {
            try {
                $customer = $voucher->usedByCustomer;

                if ($customer && $customer->status === 'active' && $customer->router) {
                    try {
                        $mikrotik = new MikroTikService($customer->router);
                        $mikrotik->disconnectSession($customer);
                        $mikrotik->disableUser($customer);
                        $customer->suspend();

                        Log::info("Suspended customer {$customer->id} due to expired voucher {$voucher->id}");
                    } catch (\Exception $e) {
                        Log::error("Failed to suspend customer {$customer->id} for expired voucher {$voucher->id}: ".$e->getMessage());
                    }
                } elseif (! $customer && $voucher->router && isset($voucher->metadata['mikrotik_user_id'])) {
                    try {
                        $mikrotik = new MikroTikService($voucher->router);
                        if ($mikrotik->connect() && $mikrotik->getConnection()) {
                            $mikrotik->getConnection()->comm('/ip/hotspot/user/disable', [
                                '.id' => $voucher->metadata['mikrotik_user_id'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to disable expired voucher user on router: ".$e->getMessage(), [
                            'voucher_id' => $voucher->id,
                        ]);
                    }
                }

                $voucher->markAsExpired();
                Log::info("Marked voucher {$voucher->id} as expired");
            } catch (\Exception $e) {
                Log::error("Failed to process expired voucher {$voucher->id}: ".$e->getMessage());
            }
        }
    }
}
