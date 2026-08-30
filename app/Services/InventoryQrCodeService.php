<?php

namespace App\Services;

use App\Models\InventoryItem;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\File;
use RuntimeException;

class InventoryQrCodeService
{
    public const QR_DIRECTORY = 'qrcodes';

    /**
     * @return array{inventory_item_id: int, serial_number: string, mac_address: string|null}
     */
    public function payload(InventoryItem $item): array
    {
        return [
            'inventory_item_id' => $item->id,
            'serial_number' => $item->serial_number,
            'mac_address' => $item->mac_address,
        ];
    }

    public function payloadJson(InventoryItem $item): string
    {
        return json_encode($this->payload($item), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function relativePath(InventoryItem $item): string
    {
        return self::QR_DIRECTORY.'/'.$item->isp_id.'/'.$item->id.'.png';
    }

    public function absolutePath(InventoryItem $item): string
    {
        return public_path($this->relativePath($item));
    }

    public function publicUrl(InventoryItem $item): ?string
    {
        if (! $item->qr_code_path) {
            return null;
        }

        return asset($item->qr_code_path);
    }

    public function ensureGenerated(InventoryItem $item): InventoryItem
    {
        if ($item->qr_code_path && File::exists(public_path($item->qr_code_path))) {
            return $item;
        }

        return $this->generate($item);
    }

    public function generate(InventoryItem $item): InventoryItem
    {
        $relativePath = $this->relativePath($item);
        $absolutePath = public_path($relativePath);
        $directory = dirname($absolutePath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            throw new RuntimeException('GD or Imagick PHP extension is required to generate QR code images.');
        }

        $result = (new Builder(
            writer: new PngWriter(),
            data: $this->payloadJson($item),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 12,
        ))->build();

        File::put($absolutePath, $result->getString());

        if ($item->qr_code_path !== $relativePath) {
            $item->update(['qr_code_path' => $relativePath]);
        }

        return $item->fresh();
    }

    public function regenerateIfIdentityChanged(InventoryItem $item, array $previous): InventoryItem
    {
        $serialChanged = ($previous['serial_number'] ?? null) !== $item->serial_number;
        $macChanged = ($previous['mac_address'] ?? null) !== $item->mac_address;

        if (! $serialChanged && ! $macChanged) {
            return $item;
        }

        return $this->generate($item);
    }

    /**
     * @return int|null
     */
    public function parseInventoryItemId(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded) && ! empty($decoded['inventory_item_id'])) {
            return (int) $decoded['inventory_item_id'];
        }

        if (preg_match('#/inventory/(\d+)#', $raw, $matches)) {
            return (int) $matches[1];
        }

        if (ctype_digit($raw)) {
            return (int) $raw;
        }

        return null;
    }
}
