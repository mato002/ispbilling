<?php

namespace App\Support;

use App\DataTransferObjects\SmsGatewayConfig;
use App\Models\ISP;
use App\Models\PlatformIntegration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves tenant SMS provider choice and runtime credentials for outbound messaging.
 */
class IspSmsConfiguration
{
    public static function forIsp(?ISP $isp): SmsGatewayConfig
    {
        if (! $isp) {
            return new SmsGatewayConfig(
                isp: new ISP,
                provider: '',
                enabled: false,
            );
        }

        $notifications = $isp->settings['notifications'] ?? [];
        $enabled = ! empty($notifications['sms_notifications']);
        $provider = self::normalizeProvider((string) ($notifications['sms_provider'] ?? ''));

        return new SmsGatewayConfig(
            isp: $isp,
            provider: $provider,
            enabled: $enabled,
            credentials: self::resolveCredentials($isp, $provider, $notifications),
        );
    }

    public static function normalizeProvider(string $provider): string
    {
        return match (strtolower(trim($provider))) {
            'africas_talking', 'africastalking', 'africa\'s talking' => 'africas_talking',
            'twilio' => 'twilio',
            'vonage', 'nexmo' => 'vonage',
            'custom' => 'custom',
            'none', '' => '',
            default => strtolower(trim($provider)),
        };
    }

    /**
     * @param  array<string, mixed>  $notifications
     * @return array<string, mixed>
     */
    private static function resolveCredentials(ISP $isp, string $provider, array $notifications): array
    {
        $tenant = self::tenantCredentialLayer($isp, $notifications);
        $platform = self::platformCredentialLayer($provider);
        $env = self::environmentCredentialLayer($provider);

        return array_merge($env, $platform, $tenant);
    }

    /**
     * @param  array<string, mixed>  $notifications
     * @return array<string, mixed>
     */
    private static function tenantCredentialLayer(ISP $isp, array $notifications): array
    {
        $paymentExperience = array_merge(
            IspPaymentExperience::defaults(),
            $isp->settings['payment_experience'] ?? []
        );

        $from = (string) ($notifications['sms_from']
            ?? $paymentExperience['sms_sender_id']
            ?? '');

        $apiKey = self::decryptIfNeeded($notifications['sms_api_key_enc'] ?? null)
            ?? self::decryptIfNeeded($paymentExperience['sms_api_key_enc'] ?? null);

        $apiSecret = self::decryptIfNeeded($notifications['sms_api_secret_enc'] ?? null)
            ?? self::decryptIfNeeded($paymentExperience['sms_api_secret_enc'] ?? null);

        $notificationCredentials = is_array($notifications['sms_credentials'] ?? null)
            ? $notifications['sms_credentials']
            : [];

        return array_filter([
            'from' => $from !== '' ? $from : null,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'account_sid' => $notificationCredentials['account_sid'] ?? $apiKey,
            'auth_token' => $notificationCredentials['auth_token'] ?? $apiSecret,
            'username' => $notificationCredentials['username'] ?? null,
            'custom_webhook_url' => $notifications['custom_webhook_url'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    private static function platformCredentialLayer(string $provider): array
    {
        if ($provider === '' || ! Schema::hasTable('platform_integrations')) {
            return [];
        }

        try {
            $messaging = PlatformIntegration::current()->messaging_channels ?? [];
        } catch (\Throwable) {
            return [];
        }

        if (($messaging['sms_provider'] ?? 'none') === 'none') {
            return [];
        }

        $platformProvider = self::normalizeProvider((string) ($messaging['sms_provider'] ?? ''));

        if ($platformProvider !== $provider && $provider !== '') {
            return [];
        }

        return array_filter([
            'from' => $messaging['twilio_from'] ?? $messaging['africastalking_from'] ?? null,
            'account_sid' => $messaging['twilio_account_sid'] ?? null,
            'auth_token' => $messaging['twilio_auth_token'] ?? null,
            'username' => $messaging['africastalking_username'] ?? null,
            'api_key' => $messaging['africastalking_api_key'] ?? null,
        ], fn ($value) => is_string($value) ? trim($value) !== '' : ! empty($value));
    }

    /**
     * @return array<string, mixed>
     */
    private static function environmentCredentialLayer(string $provider): array
    {
        if ($provider !== 'vonage') {
            return [];
        }

        return array_filter([
            'api_key' => config('services.vonage.key', env('VONAGE_KEY')),
            'api_secret' => config('services.vonage.secret', env('VONAGE_SECRET')),
            'from' => config('services.vonage.sms_from', env('VONAGE_SMS_FROM')),
        ], fn ($value) => is_string($value) ? trim($value) !== '' : ! empty($value));
    }

    private static function decryptIfNeeded(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
