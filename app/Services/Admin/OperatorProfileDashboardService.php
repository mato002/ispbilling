<?php

namespace App\Services\Admin;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class OperatorProfileDashboardService
{
    /**
     * @return array{
   *     activity_volume_30d: int,
   *     active_subscribers: int|null,
   *     network_scope_label: string,
   *     failed_logins_30d: int,
   *     router_api_failures_30d: int,
     *     security_events_30d: int,
     *     tenant_activity_30d: int|null,
     *     recent_activity: \Illuminate\Support\Collection<int, ActivityLog>
     * }
   */
    public function snapshot(User $user): array
    {
        $since = now()->subDays(30);
        $baseQuery = $this->operatorActivityQuery($user);

        $activityVolume = (clone $baseQuery)
            ->where('created_at', '>=', $since)
            ->count();

        $failedLogins = (clone $baseQuery)
            ->where('action', 'login_failed')
            ->where('created_at', '>=', $since)
            ->count();

        $routerFailures = (clone $baseQuery)
            ->where('action', 'router_api_connection_failed')
            ->where('created_at', '>=', $since)
            ->count();

        $networkScope = $this->networkScope($user);

        $tenantActivity30d = $user->isp_id
            ? ActivityLog::query()
                ->where('isp_id', $user->isp_id)
                ->where('created_at', '>=', $since)
                ->count()
            : null;

        $recentActivity = (clone $baseQuery)
            ->with(['user:id,name', 'isp:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'activity_volume_30d' => $activityVolume,
            'active_subscribers' => $networkScope['count'],
            'network_scope_label' => $networkScope['label'],
            'failed_logins_30d' => $failedLogins,
            'router_api_failures_30d' => $routerFailures,
            'security_events_30d' => $failedLogins + $routerFailures,
            'tenant_activity_30d' => $tenantActivity30d,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * @return array{count: int|null, label: string}
     */
    private function networkScope(User $user): array
    {
        if ($user->isp_id) {
            $count = Customer::query()
                ->where('isp_id', $user->isp_id)
                ->active()
                ->count();

            return [
                'count' => $count,
                'label' => $user->isp?->name ?? 'Tenant workspace',
            ];
        }

        if ($user->isSuperAdmin()) {
            $count = Customer::query()->active()->count();

            return [
                'count' => $count,
                'label' => 'Platform-wide',
            ];
        }

        return [
            'count' => null,
            'label' => 'No tenant assigned',
        ];
    }

    private function operatorActivityQuery(User $user): Builder
    {
        $email = trim((string) $user->email);

        return ActivityLog::query()->where(function (Builder $query) use ($user, $email) {
            $query->where('user_id', $user->id);

            if ($email !== '') {
                $query->orWhere('description', 'like', '%'.$email.'%')
                    ->orWhere('metadata->attempted_email', $email);
            }
        });
    }
}
