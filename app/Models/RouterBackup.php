<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RouterBackup extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'router_id',
        'isp_id',
        'created_by_user_id',
        'type',
        'storage_disk',
        'storage_path',
        'filename',
        'size_bytes',
        'checksum_sha256',
        'status',
        'notes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Read the backup contents from configured disk.
     */
    public function contents(): ?string
    {
        $disk = Storage::disk($this->storage_disk);
        if (! $disk->exists($this->storage_path)) {
            return null;
        }

        return $disk->get($this->storage_path);
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return number_format($value, $value >= 100 ? 0 : 1).' '.$units[$i];
    }
}
