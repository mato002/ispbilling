<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton-style row in `platform_integrations` for payment pipes, global VAT, and encrypted vault secrets.
 */
class PlatformIntegration extends Model
{
    protected $table = 'platform_integrations';

    protected $fillable = [
        'slug',
        'payment_pipes',
        'global_billing',
        'credentials_vault',
        'messaging_channels',
    ];

    protected function casts(): array
    {
        return [
            'payment_pipes' => 'array',
            'global_billing' => 'array',
            'credentials_vault' => 'encrypted:array',
            'messaging_channels' => 'encrypted:array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentPipeLabels(): array
    {
        return [
            'mpesa' => 'M-Pesa (Safaricom Daraja)',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'flutterwave' => 'Flutterwave',
            'selcom' => 'Selcom',
            'pesapal' => 'Pesapal',
        ];
    }

    /**
     * @return list<string>
     */
    public static function vaultSchemaKeys(): array
    {
        return [
            'daraja_consumer_key',
            'daraja_consumer_secret',
            'daraja_passkey',
            'paystack_public_key',
            'paystack_secret_key',
            'flutterwave_public_key',
            'flutterwave_secret_key',
            'flutterwave_encryption_key',
            'pesapal_consumer_key',
            'pesapal_consumer_secret',
        ];
    }

    public static function current(): self
    {
        $defaults = [
            'payment_pipes' => collect(self::paymentPipeLabels())->map(fn () => true)->all(),
            'global_billing' => [
                'global_tax_rate_percent' => 0,
                'global_tax_label' => 'VAT',
                'enforce_global_tax' => false,
            ],
            'credentials_vault' => [],
            'messaging_channels' => [
                'sms_provider' => 'none',
                'twilio_account_sid' => '',
                'twilio_auth_token' => '',
                'twilio_from' => '',
                'africastalking_username' => '',
                'africastalking_api_key' => '',
                'africastalking_from' => '',
                'firebase_web_api_key' => '',
                'firebase_project_id' => '',
                'firebase_messaging_sender_id' => '',
                'firebase_app_id' => '',
                'firebase_vapid_public_key' => '',
            ],
        ];

        return static::firstOrCreate(
            ['slug' => 'default'],
            $defaults
        );
    }

    public static function normalizePipeProvider(string $provider): string
    {
        return $provider === 'daraja' ? 'mpesa' : $provider;
    }

    public function isPaymentPipeEnabled(string $provider): bool
    {
        $key = self::normalizePipeProvider($provider);
        $pipes = $this->payment_pipes ?? [];

        return ($pipes[$key] ?? true) === true;
    }

    /**
     * @return array<string, bool>
     */
    public function vaultKeyPresence(): array
    {
        $vault = $this->credentials_vault ?? [];
        $out = [];
        foreach (self::vaultSchemaKeys() as $key) {
            $v = $vault[$key] ?? null;
            $out[$key] = is_string($v) && $v !== '';
        }

        return $out;
    }
}
