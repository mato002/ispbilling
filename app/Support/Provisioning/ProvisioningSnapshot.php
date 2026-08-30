<?php

namespace App\Support\Provisioning;

final class ProvisioningSnapshot
{
    /**
     * @param  array{mac_verified: bool, mac_mismatch: bool, throttled: bool, orphan_router_profile: bool}  $statusFlags
     */
    public function __construct(
        public int $customerId,
        public string $customerName,
        public string $ispTenantName,
        public ?string $planName,
        public ?string $hardwareMac,
        public ?string $sessionMac,
        public ?string $queueRateLimit,
        public string $queueState,
        public ?int $routerId,
        public ?string $routerName,
        public string $connectionType,
        public string $customerStatus,
        public array $statusFlags,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName,
            'isp_tenant_name' => $this->ispTenantName,
            'plan_name' => $this->planName,
            'hardware_mac' => $this->hardwareMac,
            'session_mac' => $this->sessionMac,
            'queue_rate_limit' => $this->queueRateLimit,
            'queue_state' => $this->queueState,
            'router_id' => $this->routerId,
            'router_name' => $this->routerName,
            'connection_type' => $this->connectionType,
            'customer_status' => $this->customerStatus,
            'status_flags' => $this->statusFlags,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customerId: (int) $data['customer_id'],
            customerName: (string) $data['customer_name'],
            ispTenantName: (string) ($data['isp_tenant_name'] ?? ''),
            planName: $data['plan_name'] ?? null,
            hardwareMac: $data['hardware_mac'] ?? null,
            sessionMac: $data['session_mac'] ?? null,
            queueRateLimit: $data['queue_rate_limit'] ?? null,
            queueState: (string) ($data['queue_state'] ?? 'unknown'),
            routerId: isset($data['router_id']) ? (int) $data['router_id'] : null,
            routerName: $data['router_name'] ?? null,
            connectionType: (string) ($data['connection_type'] ?? 'hotspot'),
            customerStatus: (string) ($data['customer_status'] ?? 'inactive'),
            statusFlags: [
                'mac_verified' => (bool) ($data['status_flags']['mac_verified'] ?? false),
                'mac_mismatch' => (bool) ($data['status_flags']['mac_mismatch'] ?? false),
                'throttled' => (bool) ($data['status_flags']['throttled'] ?? false),
                'orphan_router_profile' => (bool) ($data['status_flags']['orphan_router_profile'] ?? false),
            ],
        );
    }
}
