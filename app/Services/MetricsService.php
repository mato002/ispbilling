<?php

namespace App\Services;

class MetricsService
{
    public function __construct(
        protected ProvisioningSnapshotService $snapshotService,
    ) {}

    /**
     * @return array{
     *     active_bindings_count: int,
     *     mismatched_mac_count: int,
     *     saturated_queue_count: int,
     *     orphan_router_profiles_count: int,
     * }
     */
    public function forIsp(int $ispId): array
    {
        $payload = $this->snapshotService->getPayload($ispId);

        return $this->fromPayload($payload);
    }

    /**
     * @param  array{rows?: array<int, array<string, mixed>>, orphan_router_usernames?: array<int, string>}  $payload
     * @return array{
     *     active_bindings_count: int,
     *     mismatched_mac_count: int,
     *     saturated_queue_count: int,
     *     orphan_router_profiles_count: int,
     * }
     */
    public function fromPayload(array $payload): array
    {
        $rows = $payload['rows'] ?? [];

        $activeBindings = 0;
        $mismatched = 0;
        $saturated = 0;
        $orphanRows = 0;

        foreach ($rows as $row) {
            $flags = $row['status_flags'] ?? [];

            if (($flags['mac_verified'] ?? false) === true) {
                $activeBindings++;
            }

            if (($flags['mac_mismatch'] ?? false) === true) {
                $mismatched++;
            }

            if (($flags['throttled'] ?? false) === true || ($row['queue_state'] ?? '') === 'near_limit') {
                $saturated++;
            }

            if (($flags['orphan_router_profile'] ?? false) === true) {
                $orphanRows++;
            }
        }

        $orphanRouterProfiles = $orphanRows + count($payload['orphan_router_usernames'] ?? []);

        return [
            'active_bindings_count' => $activeBindings,
            'mismatched_mac_count' => $mismatched,
            'saturated_queue_count' => $saturated,
            'orphan_router_profiles_count' => $orphanRouterProfiles,
        ];
    }
}
