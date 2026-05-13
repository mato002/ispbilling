<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Presentation + filter heuristics for ISP activity / audit logs.
 */
final class ActivityLogAudit
{
    public const MODULES = [
        'customers' => 'Customers',
        'billing' => 'Billing',
        'network' => 'Network',
        'security' => 'Security',
        'system' => 'System',
    ];

    public const SEVERITIES = ['info', 'success', 'warning', 'danger', 'critical'];

    public static function severity(ActivityLog $log): string
    {
        $a = strtolower((string) $log->action);
        $d = strtolower((string) $log->description);

        if (str_contains($a, 'breach') || str_contains($d, 'critical alert') || $a === 'system_critical') {
            return 'critical';
        }
        if (
            str_contains($a, 'fail')
            || str_contains($d, 'failed')
            || $a === 'delete'
            || str_contains($d, 'offline')
            || str_contains($d, 'suspended')
            || str_contains($d, 'unauthorized')
        ) {
            return 'danger';
        }
        if (
            str_contains($d, 'warning')
            || str_contains($a, 'disconnect')
            || str_contains($d, 'timeout')
            || str_contains($d, 'degraded')
        ) {
            return 'warning';
        }
        if (
            in_array($a, ['login', 'logout', 'create'], true)
            || str_contains($a, 'paid')
            || str_contains($d, 'successfully')
            || str_contains($d, 'recorded')
            || str_contains($d, 'completed')
        ) {
            return 'success';
        }

        return 'info';
    }

    public static function moduleKey(ActivityLog $log): string
    {
        $a = strtolower((string) $log->action);
        $type = (string) $log->model_type;

        if ($a === 'login' || $a === 'logout' || str_contains($a, 'auth') || str_contains($a, 'password')) {
            return 'security';
        }
        if (str_contains($type, 'Customer')) {
            return 'customers';
        }
        if (
            str_contains($type, 'Payment')
            || str_contains($type, 'Invoice')
            || str_contains($type, 'Subscription')
            || str_contains($type, 'Plan')
            || str_contains($a, 'payment')
            || str_contains($a, 'invoice')
        ) {
            return 'billing';
        }
        if (
            str_contains($type, 'Router')
            || str_contains($type, 'Hotspot')
            || str_contains($type, 'IpPool')
            || str_contains($type, 'Bandwidth')
            || str_contains($d = strtolower((string) $log->description), 'router')
            || str_contains($d, 'pppoe')
            || str_contains($d, 'hotspot')
        ) {
            return 'network';
        }

        return 'system';
    }

    public static function eventCategory(ActivityLog $log): string
    {
        return match (self::moduleKey($log)) {
            'security' => 'AUTH',
            'billing' => 'BILLING',
            'customers' => 'CUSTOMERS',
            'network' => 'NETWORK',
            default => 'SYSTEM',
        };
    }

    public static function eventTitle(ActivityLog $log): string
    {
        $a = strtolower((string) $log->action);
        $base = class_basename((string) $log->model_type) ?: 'Event';

        if ($a === 'login') {
            return 'Staff login';
        }
        if ($a === 'logout') {
            return 'Staff logout';
        }
        if ($a === 'create') {
            return "Created {$base}";
        }
        if ($a === 'update') {
            return "Updated {$base}";
        }
        if ($a === 'delete') {
            return "Deleted {$base}";
        }

        $words = str_replace('_', ' ', (string) $log->action);

        return Str::title($words);
    }

    public static function applySeverityFilter(Builder $query, string $severity): void
    {
        $severity = strtolower($severity);
        if (! in_array($severity, self::SEVERITIES, true)) {
            return;
        }

        if ($severity === 'info') {
            $query->where(function (Builder $q) {
                $q->whereNot(fn (Builder $n) => self::applySeverityPositive($n, 'critical'))
                    ->whereNot(fn (Builder $n) => self::applySeverityPositive($n, 'danger'))
                    ->whereNot(fn (Builder $n) => self::applySeverityPositive($n, 'warning'))
                    ->whereNot(fn (Builder $n) => self::applySeverityPositive($n, 'success'));
            });

            return;
        }

        $query->where(fn (Builder $q) => self::applySeverityPositive($q, $severity));
    }

    /**
     * Rows that match the given non-info severity (used for filtering + info negation).
     */
    private static function applySeverityPositive(Builder $q, string $severity): void
    {
        match ($severity) {
            'critical' => $q->where(function (Builder $w) {
                $w->whereRaw('LOWER(action) LIKE ?', ['%breach%'])
                    ->orWhere('action', 'system_critical')
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%critical alert%']);
            }),
            'danger' => $q->where(function (Builder $w) {
                $w->whereRaw('LOWER(action) LIKE ?', ['%fail%'])
                    ->orWhere('action', 'delete')
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%failed%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%offline%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%suspended%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%unauthorized%']);
            }),
            'warning' => $q->where(function (Builder $w) {
                $w->whereRaw('LOWER(description) LIKE ?', ['%warning%'])
                    ->orWhereRaw('LOWER(action) LIKE ?', ['%disconnect%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%timeout%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%degraded%']);
            }),
            'success' => $q->where(function (Builder $w) {
                $w->whereIn('action', ['login', 'logout', 'create'])
                    ->orWhereRaw('LOWER(action) LIKE ?', ['%paid%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%successfully%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%recorded%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%completed%']);
            }),
            default => $q->whereRaw('1 = 0'),
        };
    }

    public static function applyModuleFilter(Builder $query, string $module): void
    {
        $module = strtolower($module);
        if (! array_key_exists($module, self::MODULES)) {
            return;
        }

        $query->where(function (Builder $q) use ($module) {
            match ($module) {
                'security' => $q->where(function (Builder $w) {
                    $w->whereIn('action', ['login', 'logout'])
                        ->orWhereRaw('LOWER(action) LIKE ?', ['%auth%'])
                        ->orWhereRaw('LOWER(action) LIKE ?', ['%password%']);
                }),
                'customers' => $q->where('model_type', 'like', '%Customer%'),
                'billing' => $q->where(function (Builder $w) {
                    $w->where('model_type', 'like', '%Payment%')
                        ->orWhere('model_type', 'like', '%Invoice%')
                        ->orWhere('model_type', 'like', '%Subscription%')
                        ->orWhere('model_type', 'like', '%Plan%')
                        ->orWhereRaw('LOWER(action) LIKE ?', ['%payment%'])
                        ->orWhereRaw('LOWER(action) LIKE ?', ['%invoice%']);
                }),
                'network' => $q->where(function (Builder $w) {
                    $w->where('model_type', 'like', '%Router%')
                        ->orWhere('model_type', 'like', '%Hotspot%')
                        ->orWhere('model_type', 'like', '%IpPool%')
                        ->orWhere('model_type', 'like', '%Bandwidth%')
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%router%'])
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%pppoe%'])
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%hotspot%']);
                }),
                default => $q->where(function (Builder $w) {
                    $w->where(function (Builder $x) {
                        $x->whereNotIn('action', ['login', 'logout'])
                            ->whereRaw('LOWER(action) NOT LIKE ?', ['%auth%'])
                            ->whereRaw('LOWER(action) NOT LIKE ?', ['%password%']);
                    })
                        ->where(function (Builder $x) {
                            $x->whereNull('model_type')
                                ->orWhere(function (Builder $y) {
                                    $y->where('model_type', 'not like', '%Customer%')
                                        ->where('model_type', 'not like', '%Payment%')
                                        ->where('model_type', 'not like', '%Invoice%')
                                        ->where('model_type', 'not like', '%Subscription%')
                                        ->where('model_type', 'not like', '%Plan%')
                                        ->where('model_type', 'not like', '%Router%')
                                        ->where('model_type', 'not like', '%Hotspot%')
                                        ->where('model_type', 'not like', '%IpPool%')
                                        ->where('model_type', 'not like', '%Bandwidth%');
                                });
                        });
                }),
            };
        });
    }

    public static function applyEventTypeFilter(Builder $query, string $type): void
    {
        $type = strtolower($type);
        $query->where(function (Builder $q) use ($type) {
            match ($type) {
                'authentication' => $q->whereIn('action', ['login', 'logout'])
                    ->orWhereRaw('LOWER(action) LIKE ?', ['%auth%']),
                'crud' => $q->whereIn('action', ['create', 'update', 'delete']),
                'billing' => $q->where(function (Builder $w) {
                    $w->where('model_type', 'like', '%Payment%')
                        ->orWhere('model_type', 'like', '%Invoice%')
                        ->orWhere('model_type', 'like', '%Subscription%')
                        ->orWhereRaw('LOWER(action) LIKE ?', ['%payment%'])
                        ->orWhereRaw('LOWER(action) LIKE ?', ['%invoice%']);
                }),
                'network' => $q->where(function (Builder $w) {
                    $w->where('model_type', 'like', '%Router%')
                        ->orWhere('model_type', 'like', '%Hotspot%')
                        ->orWhere('model_type', 'like', '%IpPool%')
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%router%'])
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%hotspot%']);
                }),
                default => $q->whereRaw('1 = 1'),
            };
        });
    }
}
