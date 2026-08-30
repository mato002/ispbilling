<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key/value rows in `platform_settings` (e.g. platform commission percent).
 */
class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function getDecimal(string $key, float $default = 0.0): float
    {
        $v = static::get($key);
        if ($v === null || $v === '') {
            return $default;
        }

        return (float) $v;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $v = static::get($key);
        if ($v === null || $v === '') {
            return $default;
        }

        return (int) $v;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $v = static::get($key);
        if ($v === null || $v === '') {
            return $default;
        }

        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function put(string $key, string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
