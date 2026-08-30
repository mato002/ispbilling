<?php

namespace App\Jobs;

use App\Models\ISP;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshAllProvisioningSnapshotsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        ISP::query()
            ->where('is_active', true)
            ->select('id')
            ->chunkById(50, function ($isps) {
                foreach ($isps as $isp) {
                    RefreshProvisioningSnapshotJob::dispatch($isp->id);
                }
            });
    }
}
