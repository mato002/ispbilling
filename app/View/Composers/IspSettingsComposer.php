<?php

namespace App\View\Composers;

use App\Models\ActivityLog;
use App\Models\HotspotServer;
use App\Models\ISP;
use App\Models\IspPaymentMethod;
use App\Models\Notification;
use App\Models\Router;
use Carbon\Carbon;
use Illuminate\View\View;

class IspSettingsComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isp_id) {
            $view->with('ispSettingsShell', null);

            return;
        }

        $isp = ISP::query()
            ->withCount(['activeCustomers', 'users'])
            ->find($user->isp_id);

        if (! $isp) {
            $view->with('ispSettingsShell', null);

            return;
        }

        $daraja = IspPaymentMethod::query()
            ->forIsp($isp->id)
            ->byProvider('daraja')
            ->first();

        $view->with('ispSettingsShell', $this->buildShell($isp, $daraja));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildShell(ISP $isp, ?IspPaymentMethod $daraja): array
    {
        $settings = $isp->settings ?? [];
        $notifications = $settings['notifications'] ?? [];
        $companyProfile = $settings['company_profile'] ?? [];

        $mpesa = $this->mpesaStatus($daraja);
        $smtp = $this->smtpStatus();
        $sms = $this->smsStatus($notifications);

        $routerCount = Router::query()->where('isp_id', $isp->id)->count();
        $activeRouters = Router::query()->where('isp_id', $isp->id)->where('is_active', true)->count();
        $hotspotServerCount = HotspotServer::query()->where('isp_id', $isp->id)->count();

        $pendingNotifications = Notification::query()
            ->where('isp_id', $isp->id)
            ->where('is_read', false)
            ->count();

        $lastLog = ActivityLog::query()
            ->where('isp_id', $isp->id)
            ->latest('id')
            ->first();

        $lastSettingsAt = $settings['meta']['last_settings_change_at'] ?? null;
        if (is_string($lastSettingsAt)) {
            try {
                $lastSettingsAt = Carbon::parse($lastSettingsAt);
            } catch (\Throwable) {
                $lastSettingsAt = null;
            }
        }

        $notificationsEnabled = ! empty($notifications['email_notifications'])
            || ! empty($notifications['sms_notifications']);

        return [
            'isp' => $isp,
            'daraja' => $daraja,
            'mpesa' => $mpesa,
            'smtp' => $smtp,
            'sms' => $sms,
            'notifications_enabled' => $notificationsEnabled,
            'badges' => [
                $isp->is_active
                    ? ['tone' => 'ok', 'label' => 'System Active', 'pulse' => true]
                    : ['tone' => 'bad', 'label' => 'System Paused', 'pulse' => false],
                $mpesa['badge'],
                $notificationsEnabled
                    ? ['tone' => 'ok', 'label' => 'Notifications Enabled', 'pulse' => false]
                    : ['tone' => 'warn', 'label' => 'Notifications Off', 'pulse' => false],
                ['tone' => 'info', 'label' => 'Secure Workspace', 'pulse' => false],
            ],
            'health' => [
                'database' => ['tone' => 'ok', 'label' => 'Database', 'value' => 'Connected'],
                'payment' => ['tone' => $mpesa['tone'], 'label' => 'Payment gateway', 'value' => $mpesa['short']],
                'routers' => [
                    'tone' => $activeRouters > 0 ? 'ok' : 'warn',
                    'label' => 'Router sync',
                    'value' => $routerCount === 0 ? 'None linked' : "{$activeRouters}/{$routerCount} active",
                ],
                'hotspot' => [
                    'tone' => $hotspotServerCount > 0 ? 'ok' : 'warn',
                    'label' => 'Hotspot edge',
                    'value' => $hotspotServerCount === 0 ? 'No servers' : "{$hotspotServerCount} server".($hotspotServerCount === 1 ? '' : 's'),
                ],
                'notifications_queue' => [
                    'tone' => 'info',
                    'label' => 'Notification queue',
                    'value' => $pendingNotifications === 0 ? 'Inbox clear' : (string) $pendingNotifications.' unread',
                ],
                'queue' => [
                    'tone' => 'info',
                    'label' => 'Job transport',
                    'value' => ucfirst((string) config('queue.default')),
                ],
                'runtime' => [
                    'tone' => 'ok',
                    'label' => 'Control plane',
                    'value' => 'Laravel '.app()->version(),
                ],
            ],
            'stats' => [
                'active_customers' => (int) ($isp->active_customers_count ?? 0),
                'admin_users' => (int) ($isp->users_count ?? 0),
                'routers_total' => $routerCount,
                'routers_active' => $activeRouters,
                'hotspot_servers' => $hotspotServerCount,
            ],
            'onboarding_percent' => $this->onboardingPercent($isp, $daraja, $companyProfile),
            'last_activity' => $lastLog,
            'last_settings_at' => $lastSettingsAt,
            'last_settings_human' => $lastSettingsAt instanceof Carbon ? $lastSettingsAt->diffForHumans() : null,
            'last_activity_human' => $lastLog?->created_at?->diffForHumans(),
            'last_activity_summary' => $lastLog?->description ?? $lastLog?->action,
            'company_profile' => $companyProfile,
        ];
    }

    /**
     * @return array{tone: string, label: string, short: string, detail: string, badge: array{tone: string, label: string}}
     */
    private function mpesaStatus(?IspPaymentMethod $daraja): array
    {
        $hasKeys = $daraja
            && $daraja->getCredential('consumer_key')
            && $daraja->getCredential('consumer_secret')
            && $daraja->getCredential('shortcode')
            && $daraja->getCredential('passkey');

        if (! $hasKeys) {
            return [
                'tone' => 'bad',
                'label' => 'API credentials missing',
                'short' => 'Not configured',
                'detail' => 'Add Consumer Key, Secret, Shortcode, and Passkey to enable STK and callbacks.',
                'badge' => ['tone' => 'bad', 'label' => 'API Key Missing', 'pulse' => false],
            ];
        }

        if (! $daraja->active) {
            return [
                'tone' => 'warn',
                'label' => 'Daraja inactive',
                'short' => 'Inactive',
                'detail' => 'Credentials are stored but this method is turned off.',
                'badge' => ['tone' => 'warn', 'label' => 'Daraja Inactive', 'pulse' => false],
            ];
        }

        if (app()->environment('local', 'staging')) {
            return [
                'tone' => 'warn',
                'label' => 'Sandbox mode',
                'short' => 'Sandbox',
                'detail' => 'Non-production environment — use Safaricom sandbox endpoints and test keys.',
                'badge' => ['tone' => 'warn', 'label' => 'Sandbox Mode', 'pulse' => false],
            ];
        }

        return [
            'tone' => 'ok',
            'label' => 'Daraja connected',
            'short' => 'Live',
            'detail' => 'M-Pesa Daraja is active and ready for collections.',
            'badge' => ['tone' => 'ok', 'label' => 'M-Pesa Connected', 'pulse' => false],
        ];
    }

    /**
     * @return array{tone: string, label: string, short: string, detail: string}
     */
    private function smtpStatus(): array
    {
        $default = config('mail.default');
        $host = config('mail.mailers.smtp.host');

        if ($default === 'smtp' && is_string($host) && $host !== '') {
            return [
                'tone' => 'ok',
                'label' => 'SMTP connected',
                'short' => 'SMTP live',
                'detail' => 'Application mailer is set to SMTP ('.$host.').',
            ];
        }

        if (in_array($default, ['log', 'array'], true)) {
            return [
                'tone' => 'warn',
                'label' => 'Mail in dev mode',
                'short' => $default,
                'detail' => 'Outbound mail is not using a live SMTP transport.',
            ];
        }

        return [
            'tone' => 'warn',
            'label' => 'SMTP not primary',
            'short' => (string) $default,
            'detail' => 'Configure MAIL_MAILER=smtp and SMTP credentials in the environment for production email.',
        ];
    }

    /**
     * @param  array<string, mixed>  $notifications
     * @return array{tone: string, label: string, short: string, detail: string}
     */
    private function smsStatus(array $notifications): array
    {
        if (empty($notifications['sms_notifications'])) {
            return [
                'tone' => 'warn',
                'label' => 'SMS off',
                'short' => 'Disabled',
                'detail' => 'Enable SMS in notification preferences when your provider is ready.',
            ];
        }

        $provider = $notifications['sms_provider'] ?? '';

        return match ($provider) {
            'africas_talking' => [
                'tone' => 'ok',
                'label' => "Africa's Talking",
                'short' => 'SMS active',
                'detail' => "SMS channel marked active with Africa's Talking.",
            ],
            'twilio' => [
                'tone' => 'ok',
                'label' => 'Twilio',
                'short' => 'SMS active',
                'detail' => 'SMS channel marked active with Twilio.',
            ],
            'custom' => [
                'tone' => 'warn',
                'label' => 'Custom API',
                'short' => 'Custom',
                'detail' => 'Custom SMS integration — verify API credentials in your deployment.',
            ],
            default => [
                'tone' => 'warn',
                'label' => 'Provider not selected',
                'short' => 'Incomplete',
                'detail' => 'Choose an SMS provider to finish configuration.',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $companyProfile
     */
    private function onboardingPercent(ISP $isp, ?IspPaymentMethod $daraja, array $companyProfile): int
    {
        $weights = 0;
        $score = 0;

        $bump = function (bool $ok, int $w = 1) use (&$weights, &$score): void {
            $weights += $w;
            if ($ok) {
                $score += $w;
            }
        };

        $bump(filled($isp->company_name));
        $bump(filled($isp->email));
        $bump(filled($isp->phone));
        $bump(filled($isp->address));
        $bump(filled($companyProfile['website'] ?? null));
        $bump(filled($companyProfile['tax_pin'] ?? null));
        $bump(filled($companyProfile['registration_number'] ?? null));
        $bump(filled($isp->logo));

        $hasMpesa = $daraja
            && $daraja->getCredential('consumer_key')
            && $daraja->active;
        $bump((bool) $hasMpesa, 2);

        if ($weights === 0) {
            return 0;
        }

        return (int) round(100 * $score / $weights);
    }
}
