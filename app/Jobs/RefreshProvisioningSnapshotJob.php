<?php

namespace App\Jobs;

use App\Services\ProvisioningSnapshotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RefreshProvisioningSnapshotJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public int $ispId) {}

    public function handle(ProvisioningSnapshotService $service): void
    {
        try {
            $service->refresh($this->ispId);
        } catch (\Throwable $e) {
            Log::error('RefreshProvisioningSnapshotJob failed', [
                'isp_id' => $this->ispId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
