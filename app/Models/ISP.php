<?php

namespace App\Models;

use App\Support\MpesaGatewayConfig;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class ISP extends Model
{
    protected $table = 'i_s_p_s';

    protected $fillable = [
        'name',
        'slug',
        'company_name',
        'email',
        'phone',
        'address',
        'logo',
        'currency',
        'timezone',
        'is_active',
        'platform_access_locked',
        'platform_access_message',
        'saas_tier',
        'platform_saas_plan_id',
        'saas_subscription_status',
        'saas_trial_ends_at',
        'saas_current_period_start',
        'saas_current_period_end',
        'saas_next_billing_at',
        'saas_grace_ends_at',
        'saas_billing_phone',
        'saas_company_pin',
        'saas_billing_method',
        'saas_onboarding_completed_at',
        'saas_onboarding_step',
        'saas_pending_platform_plan_id',
        'support_phone',
        'saas_payment_verified_at',
        'saas_c2b_registered_at',
        'saas_setup_fee_paid_at',
        'saas_white_label_opt_in',
        'sms_balance',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'platform_access_locked' => 'boolean',
        'saas_white_label_opt_in' => 'boolean',
        'settings' => 'array',
        'saas_trial_ends_at' => 'datetime',
        'saas_current_period_start' => 'date',
        'saas_current_period_end' => 'date',
        'saas_next_billing_at' => 'datetime',
        'saas_grace_ends_at' => 'datetime',
        'saas_onboarding_completed_at' => 'datetime',
        'saas_onboarding_step' => 'integer',
        'saas_payment_verified_at' => 'datetime',
        'saas_c2b_registered_at' => 'datetime',
        'saas_setup_fee_paid_at' => 'datetime',
        'sms_balance' => 'decimal:4',
    ];

    public function routers(): HasMany
    {
        return $this->hasMany(Router::class, 'isp_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'isp_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class, 'isp_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'isp_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'isp_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(IspPayout::class, 'isp_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'isp_id');
    }

    public function activeCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'isp_id')->where('status', 'active');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'isp_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(IspPaymentMethod::class, 'isp_id');
    }

    public function saasPlan(): BelongsTo
    {
        return $this->belongsTo(PlatformSaasPlan::class, 'platform_saas_plan_id');
    }

    public function platformSaasInvoices(): HasMany
    {
        return $this->hasMany(PlatformSaasInvoice::class, 'isp_id');
    }

    public function platformSaasSubscription(): HasOne
    {
        return $this->hasOne(PlatformSaasSubscription::class, 'isp_id');
    }

    public function pendingSaasPlan(): BelongsTo
    {
        return $this->belongsTo(PlatformSaasPlan::class, 'saas_pending_platform_plan_id');
    }

    public function pendingPlanDefersOnboardingPayment(?bool $whiteLabelOptIn = null): bool
    {
        $plan = $this->relationLoaded('pendingSaasPlan')
            ? $this->pendingSaasPlan
            : $this->pendingSaasPlan()->first();

        if (! $plan) {
            return false;
        }

        return $plan->defersOnboardingPaymentVerification(
            $whiteLabelOptIn ?? (bool) $this->saas_white_label_opt_in
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function billingPayloadForSaasComplete(): array
    {
        $pb = $this->settings['platform_billing'] ?? [];

        return [
            'company_name' => $this->company_name,
            'saas_company_pin' => $this->saas_company_pin,
            'saas_billing_phone' => $this->saas_billing_phone,
            'saas_billing_method' => $this->saas_billing_method ?? 'mpesa',
            'billing_email' => $pb['billing_email'] ?? null,
            'bank_name' => $pb['bank_name'] ?? null,
            'bank_account_name' => $pb['bank_account_name'] ?? null,
            'bank_account_number' => $pb['bank_account_number'] ?? null,
            'bank_branch' => $pb['bank_branch'] ?? null,
            'cardholder_name' => $pb['cardholder_name'] ?? null,
            'payment_reference_note' => $pb['payment_reference_note'] ?? null,
        ];
    }

    public function needsSaasOnboarding(): bool
    {
        return $this->saas_onboarding_completed_at === null;
    }

    /**
     * Unified Daraja/M-Pesa credentials from isp_payment_methods, settings.payment_gateways, or config.
     *
     * @return array<string, mixed>
     */
    protected function mpesaConfig(): Attribute
    {
        return Attribute::get(fn (): array => MpesaGatewayConfig::resolveForIsp($this));
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedMpesaConfig(bool $activeMethodOnly = false): array
    {
        return MpesaGatewayConfig::resolveForIsp($this, $activeMethodOnly);
    }

    public function saasHasFeature(string $key): bool
    {
        $plan = $this->saasPlan;
        if (! $plan) {
            return true;
        }

        return $plan->hasFeature($key);
    }

    public function saasCustomerLimit(): ?int
    {
        return $this->saasPlan?->max_customers;
    }

    public function logoPublicUrl(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        return Storage::disk('public')->url($this->logo);
    }

    /**
     * Optional hex from settings JSON: settings.brand.primary (#RRGGBB).
     */
    public function brandPrimaryHex(): ?string
    {
        $hex = data_get($this->settings, 'brand.primary');

        return is_string($hex) && preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? $hex : null;
    }
}
