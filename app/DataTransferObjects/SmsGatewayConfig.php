<?php

namespace App\DataTransferObjects;

use App\Models\ISP;

final class SmsGatewayConfig
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function __construct(
        public readonly ISP $isp,
        public readonly string $provider,
        public readonly bool $enabled,
        public readonly array $credentials = [],
    ) {}

    public function isDeliverable(): bool
    {
        return $this->enabled && $this->provider !== '' && $this->provider !== 'none';
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return $this->credentials[$key] ?? $default;
    }

    public function hasCredential(string $key): bool
    {
        $value = $this->credential($key);

        return is_string($value) ? trim($value) !== '' : ! empty($value);
    }
}
