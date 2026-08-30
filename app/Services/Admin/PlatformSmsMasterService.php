<?php

namespace App\Services\Admin;

use App\Models\ISP;
use App\Models\PlatformIntegration;
use App\Models\SmsMessageQueue;
use App\Models\SmsPlatformLedger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlatformSmsMasterService
{
    /**
     * @return array{
     *     metrics: array{total_volume: int, gross_revenue: float, driver_key: string, driver_label: string},
     *     tenants: Collection<int, array{isp: ISP, subdomain: string, balance: float, low_balance: bool, messages_dispatched: int, sms_enabled: bool}>,
     *     recent_deposits: Collection<int, SmsPlatformLedger>
     * }
     */
    public function dashboard(): array
    {
        $ledgerSent = (int) SmsPlatformLedger::query()
            ->where('type', SmsPlatformLedger::TYPE_DEDUCTION)
            ->sum('sms_count');

        $queueSent = (int) SmsMessageQueue::query()
            ->where('status', SmsMessageQueue::STATUS_SENT)
            ->count();

        $grossRevenue = (float) SmsPlatformLedger::query()
            ->where('type', SmsPlatformLedger::TYPE_TOPUP)
            ->sum('amount');

        $dispatchedByTenant = SmsMessageQueue::query()
            ->select('tenant_id', DB::raw('COUNT(*) as total'))
            ->where('status', SmsMessageQueue::STATUS_SENT)
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $ledgerSmsByTenant = SmsPlatformLedger::query()
            ->select('tenant_id', DB::raw('SUM(sms_count) as total'))
            ->where('type', SmsPlatformLedger::TYPE_DEDUCTION)
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $centralDomain = config('app.central_domain', 'localhost');
        $lowThreshold = (float) config('sms.low_balance_threshold', 50);

        $tenants = ISP::query()
            ->orderBy('name')
            ->get()
            ->map(function (ISP $isp) use ($centralDomain, $lowThreshold, $dispatchedByTenant, $ledgerSmsByTenant) {
                $balance = (float) ($isp->sms_balance ?? 0);
                $dispatched = (int) ($dispatchedByTenant[$isp->id] ?? 0)
                    + (int) ($ledgerSmsByTenant[$isp->id] ?? 0);

                return [
                    'isp' => $isp,
                    'subdomain' => filled($isp->slug)
                        ? $isp->slug.'.'.$centralDomain
                        : '—',
                    'balance' => $balance,
                    'low_balance' => $balance < $lowThreshold,
                    'messages_dispatched' => $dispatched,
                    'sms_enabled' => $this->tenantSmsEnabled($isp),
                ];
            });

        $driver = $this->resolveActiveDriver();

        $recentDeposits = SmsPlatformLedger::query()
            ->with('tenant')
            ->where('type', SmsPlatformLedger::TYPE_TOPUP)
            ->latest()
            ->limit(25)
            ->get();

        return [
            'metrics' => [
                'total_volume' => $ledgerSent + $queueSent,
                'gross_revenue' => $grossRevenue,
                'driver_key' => $driver['key'],
                'driver_label' => $driver['label'],
            ],
            'tenants' => $tenants,
            'recent_deposits' => $recentDeposits,
        ];
    }

    public function tenantSmsEnabled(ISP $isp): bool
    {
        return (bool) data_get($isp->settings, 'sms.service_enabled', true);
    }

    public function setTenantSmsEnabled(ISP $isp, bool $enabled): void
    {
        $settings = $isp->settings ?? [];
        data_set($settings, 'sms.service_enabled', $enabled);
        $isp->update(['settings' => $settings]);
    }

    /**
     * @return array{key: string, label: string}
     */
    public function resolveActiveDriver(): array
    {
        $labels = config('sms.driver_labels', []);
        $override = config('sms.platform_driver');

        if (filled($override)) {
            $key = (string) $override;

            return [
                'key' => $key,
                'label' => $labels[$key] ?? ucfirst($key),
            ];
        }

        $messaging = PlatformIntegration::current()->messaging_channels ?? [];
        $provider = (string) ($messaging['sms_provider'] ?? 'none');

        if ($provider === 'africastalking') {
            return ['key' => 'africastalking', 'label' => $labels['africastalking']];
        }

        if (in_array($provider, ['onfon', 'onfonmedia'], true)) {
            return ['key' => 'onfon', 'label' => $labels['onfon']];
        }

        return [
            'key' => $provider,
            'label' => $labels[$provider] ?? ($provider === 'none' ? 'Not configured' : ucfirst($provider)),
        ];
    }
}
