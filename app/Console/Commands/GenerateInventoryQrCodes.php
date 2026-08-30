<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Services\InventoryQrCodeService;
use Illuminate\Console\Command;

class GenerateInventoryQrCodes extends Command
{
    protected $signature = 'inventory:generate-qrcodes {--isp= : Limit to a specific ISP id}';

    protected $description = 'Generate QR code images for inventory items missing a code';

    public function handle(InventoryQrCodeService $qrCodeService): int
    {
        $query = InventoryItem::withoutIspTenant()->orderBy('id');

        if ($ispId = $this->option('isp')) {
            $query->where('isp_id', (int) $ispId);
        }

        $generated = 0;

        $query->chunkById(100, function ($items) use ($qrCodeService, &$generated) {
            foreach ($items as $item) {
                if ($item->qr_code_path && file_exists(public_path($item->qr_code_path))) {
                    continue;
                }

                $qrCodeService->generate($item);
                $generated++;
            }
        });

        $this->info("Generated {$generated} QR code(s).");

        return self::SUCCESS;
    }
}
