<?php

namespace App\Providers;

use App\Services\Sms\SmsGatewayManager;
use App\View\Composers\GuestLayoutComposer;
use App\View\Composers\IspBrandingComposer;
use App\View\Composers\IspSettingsComposer;
use App\View\Composers\IspSystemAnnouncementsComposer;
use App\View\Composers\SaasOnboardingOverlayComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\PlatformSetting;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsGatewayManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keep indexes within the limits used by older MySQL/MariaDB hosts.
        Schema::defaultStringLength(191);

        try {
            if (Schema::hasTable('platform_settings')) {
                foreach ([
                    'app_name' => 'app.name',
                    'app_url' => 'app.url',
                    'app_timezone' => 'app.timezone',
                    'app_locale' => 'app.locale',
                ] as $key => $configKey) {
                    $value = PlatformSetting::get($key);
                    if ($value !== null && $value !== '') config([$configKey => $value]);
                }
                date_default_timezone_set(config('app.timezone', 'UTC'));
            }
        } catch (\Throwable) {
            // Settings are optional during initial installation and migrations.
        }

        if (! $this->app->runningInConsole()) {
            $raw = (string) ini_get('memory_limit');
            if ($raw !== '' && $raw !== '-1') {
                $bytes = self::parseIniMemoryToBytes($raw);
                if ($bytes < 256 * 1024 * 1024) {
                    @ini_set('memory_limit', '256M');
                }
            }

            static $logsProbeDone = false;
            if (! $logsProbeDone) {
                $logsProbeDone = true;
                $dir = storage_path('logs');
                if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
                    error_log('[ispbilling] Cannot create storage/logs directory at '.$dir);
                } else {
                    $probe = $dir.DIRECTORY_SEPARATOR.'.write-probe-'.getmypid();
                    $ok = @file_put_contents($probe, '1') !== false;
                    if ($ok) {
                        @unlink($probe);
                    } else {
                        error_log('[ispbilling] storage/logs is not writable by the web PHP user — Path: '.$dir);
                    }
                }
            }
        }

        View::composer('isp.settings.*', IspSettingsComposer::class);
        View::composer('layouts.guest', GuestLayoutComposer::class);
        View::composer('layouts.admin', IspBrandingComposer::class);
        View::composer('layouts.admin', IspSystemAnnouncementsComposer::class);
        View::composer('layouts.admin', SaasOnboardingOverlayComposer::class);
    }

    private static function parseIniMemoryToBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1] ?? '');
        $num = (int) $val;

        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => max(0, $num),
        };
    }
}
