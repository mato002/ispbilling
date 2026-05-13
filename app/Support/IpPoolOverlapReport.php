<?php

namespace App\Support;

use App\Models\IpPool;
use Illuminate\Support\Collection;

/**
 * Detects overlapping IPv4 ranges across all IP pools (different ISPs / routers).
 */
final class IpPoolOverlapReport
{
    /**
     * @return array{segments: Collection<int, array{isp_id: int, isp_name: string, pool_id: int, pool_name: string, router_id: ?int, start: int, end: int, label: string}>, overlaps: array<int, array{a: int, b: int}>}
     */
    public static function analyze(): array
    {
        $segments = collect();

        foreach (IpPool::query()->with(['isp:id,name', 'router:id,name'])->get() as $pool) {
            $ranges = $pool->ranges;
            if (! is_array($ranges)) {
                continue;
            }
            foreach ($ranges as $range) {
                $start = null;
                $end = null;
                if (! empty($range['start']) && ! empty($range['end'])) {
                    $start = ip2long($range['start']);
                    $end = ip2long($range['end']);
                } elseif (! empty($range['address'])) {
                    $start = ip2long($range['address']);
                    $end = $start;
                }
                if ($start === false || $end === false || $start === null || $end === null) {
                    continue;
                }
                if ($end < $start) {
                    [$start, $end] = [$end, $start];
                }
                $label = ($range['start'] ?? $range['address'] ?? '?').'–'.($range['end'] ?? $range['address'] ?? '?');
                $segments->push([
                    'isp_id' => (int) $pool->isp_id,
                    'isp_name' => $pool->isp?->name ?? 'ISP #'.$pool->isp_id,
                    'pool_id' => (int) $pool->id,
                    'pool_name' => (string) $pool->name,
                    'router_id' => $pool->router_id ? (int) $pool->router_id : null,
                    'router_name' => $pool->router?->name,
                    'start' => $start,
                    'end' => $end,
                    'label' => $label,
                ]);
            }
        }

        $sorted = $segments->sortBy('start')->values();
        $overlaps = [];

        for ($i = 0; $i < $sorted->count(); $i++) {
            for ($j = $i + 1; $j < $sorted->count(); $j++) {
                $a = $sorted[$i];
                $b = $sorted[$j];
                if ($b['start'] > $a['end']) {
                    break;
                }
                if ($a['end'] < $b['start']) {
                    continue;
                }
                if ($a['start'] <= $b['end'] && $b['start'] <= $a['end']) {
                    $overlaps[] = ['a' => $i, 'b' => $j];
                }
            }
        }

        return [
            'segments' => $sorted,
            'overlaps' => $overlaps,
        ];
    }
}
