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
                    $mikrotik = new MikroTikService($customer->router);
                    if (! $mikrotik->disconnectSession($customer) || ! $mikrotik->disableUser($customer)) {
                        Log::warning("Router enforcement failed for expired voucher {$voucher->id}; will retry");
                        continue;
                    }
                        $customer->suspend();

                        Log::info("Suspended customer {$customer->id} due to expired voucher {$voucher->id}");
                } elseif (! $customer && $voucher->router && isset($voucher->metadata['mikrotik_user_id'])) {
                    try {
                        $mikrotik = new MikroTikService($voucher->router);
                        if (! $mikrotik->connect() || ! $mikrotik->getConnection()) {
                            Log::warning("Router unavailable for expired voucher {$voucher->id}; will retry");
                            continue;
                        }
                        $connection = $mikrotik->getConnection();
                        $sessions = $connection->comm('/ip/hotspot/active/print', ['?user' => $voucher->voucher_code]);
                        foreach ($sessions as $session) {
                            if (! empty($session['.id'])) {
                                $connection->comm('/ip/hotspot/active/remove', ['.id' => $session['.id']]);
                            }
                        }
                        $connection->comm('/ip/hotspot/user/disable', ['.id' => $voucher->metadata['mikrotik_user_id']]);
                    } catch (\Exception $e) {
                        Log::warning("Failed to disable expired voucher user on router: ".$e->getMessage(), [
                            'voucher_id' => $voucher->id,
                        ]);
                        continue;
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
