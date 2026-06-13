<?php

namespace App\Support;

use App\Models\IspPaymentMethod;
use App\Models\ISP;

/**
 * Canonical Daraja / M-Pesa credential shape for isp_payment_methods and ISP.settings.payment_gateways.
 */
class MpesaGatewayConfig
{
    /** Canonical provider slug stored on isp_payment_methods rows. */
    public const STORAGE_PROVIDER = 'daraja';

    /** Provider slug exposed to checkout / payment UI. */
    public const CHECKOUT_PROVIDER = 'mpesa';

    /** Canonical settings JSON key under ISP.settings.payment_gateways. */
    public const SETTINGS_KEY = 'mpesa';

    /**
     * @return list<string>
     */
    public static function mpesaProviderSlugs(): array
    {
        return [self::STORAGE_PROVIDER, self::CHECKOUT_PROVIDER];
    }

    public static function isMpesaProvider(?string $provider): bool
    {
        return in_array(strtolower((string) $provider), self::mpesaProviderSlugs(), true);
    }

    public static function normalizeProviderForStorage(?string $provider): string
    {
        return self::isMpesaProvider($provider) ? self::STORAGE_PROVIDER : (string) $provider;
    }

    public static function normalizeProviderForCheckout(?string $provider): string
    {
        return self::isMpesaProvider($provider) ? self::CHECKOUT_PROVIDER : (string) $provider;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function normalizeCredentials(array $raw): array
    {
        $pick = function (array $keys) use ($raw) {
            foreach ($keys as $key) {
                if (! array_key_exists($key, $raw)) {
                    continue;
                }
                $value = $raw[$key];
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            return null;
        };

        $normalized = [
            'consumer_key' => $pick(['consumer_key', 'mpesa_consumer_key', 'daraja_consumer_key', 'ConsumerKey']),
            'consumer_secret' => $pick(['consumer_secret', 'mpesa_consumer_secret', 'daraja_consumer_secret', 'ConsumerSecret']),
            'passkey' => $pick(['passkey', 'mpesa_passkey', 'daraja_passkey', 'Passkey', 'lnm_passkey']),
            'shortcode' => $pick(['shortcode', 'mpesa_shortcode', 'daraja_shortcode', 'Shortcode', 'business_shortcode']),
            'environment' => $pick(['environment', 'mpesa_environment', 'env'])
                ?? config('services.mpesa.environment', 'sandbox'),
            'transaction_type' => $pick(['transaction_type', 'TransactionType'])
                ?? config('services.mpesa.transaction_type', 'CustomerPayBillOnline'),
            'callback_url' => $pick(['callback_url', 'mpesa_callback_url']),
            'timeout_url' => $pick(['timeout_url', 'mpesa_timeout_url']),
        ];

        return array_filter(
            $normalized,
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public static function hasTenantCredentials(array $credentials): bool
    {
        return filled($credentials['consumer_key'] ?? null)
            && filled($credentials['consumer_secret'] ?? null)
            && filled($credentials['passkey'] ?? null)
            && filled($credentials['shortcode'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    public static function formatForSettings(array $credentials, bool $enabled = true): array
    {
        return array_merge(
            ['enabled' => $enabled],
            self::normalizeCredentials($credentials)
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function credentialsFromSettings(array $settings): array
    {
        $gateways = $settings['payment_gateways'] ?? [];

        return self::normalizeCredentials(array_merge(
            $gateways['daraja'] ?? [],
            $gateways[self::SETTINGS_KEY] ?? []
        ));
    }

    /**
     * Resolve M-Pesa/Daraja credentials for an ISP (payment method row preferred, then settings JSON, then config).
     *
     * @return array<string, mixed>
     */
    public static function resolveForIsp(?ISP $isp, bool $activeMethodOnly = false): array
    {
        if ($isp) {
            $methodQuery = IspPaymentMethod::query()
                ->forIsp($isp->id)
                ->mpesaProvider();

            if ($activeMethodOnly) {
                $methodQuery->where('active', true);
            }

            /** @var IspPaymentMethod|null $method */
            $method = $methodQuery->first();

            if ($method) {
                $credentials = self::normalizeCredentials($method->credentials);

                if (self::hasTenantCredentials($credentials)) {
                    return array_merge($credentials, [
                        'callback_url' => $method->callback_url ?: ($credentials['callback_url'] ?? null),
                        'timeout_url' => $credentials['timeout_url'] ?? config('services.mpesa.timeout_url'),
                        'source' => 'isp_payment_method',
                        'provider' => self::STORAGE_PROVIDER,
                        'active' => (bool) $method->active,
                    ]);
                }
            }

            $settingsCredentials = self::credentialsFromSettings($isp->settings ?? []);
            if (self::hasTenantCredentials($settingsCredentials)) {
                $gateways = $isp->settings['payment_gateways'] ?? [];
                $enabled = ($gateways[self::SETTINGS_KEY]['enabled'] ?? $gateways['daraja']['enabled'] ?? true) === true;

                return array_merge($settingsCredentials, [
                    'source' => 'settings',
                    'enabled' => $enabled,
                    'provider' => self::CHECKOUT_PROVIDER,
                ]);
            }
        }

        return self::unconfigured();
    }

    /**
     * ISP tenant has not configured M-Pesa — never fall back to platform .env (PrimeBilling collections).
     *
     * @return array<string, mixed>
     */
    public static function unconfigured(): array
    {
        return [
            'consumer_key' => null,
            'consumer_secret' => null,
            'passkey' => null,
            'shortcode' => null,
            'environment' => config('services.mpesa.environment', 'sandbox'),
            'transaction_type' => config('services.mpesa.transaction_type', 'CustomerPayBillOnline'),
            'source' => 'unconfigured',
            'provider' => self::CHECKOUT_PROVIDER,
            'active' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromConfigDefaults(): array
    {
        return self::normalizeCredentials(config('services.mpesa', [])) + [
            'callback_url' => config('services.mpesa.callback_url'),
            'timeout_url' => config('services.mpesa.timeout_url'),
            'source' => 'config',
            'provider' => self::CHECKOUT_PROVIDER,
            'active' => true,
        ];
    }

    public static function findPaymentMethodForIsp(int $ispId, bool $activeOnly = false): ?IspPaymentMethod
    {
        $query = IspPaymentMethod::query()
            ->forIsp($ispId)
            ->mpesaProvider();

        if ($activeOnly) {
            $query->where('active', true);
        }

        return $query->first();
    }
}
