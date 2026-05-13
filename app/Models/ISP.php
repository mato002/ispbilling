<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'saas_setup_fee_paid_at',
        'saas_white_label_opt_in',
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
        'saas_setup_fee_paid_at' => 'datetime',
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

    public function needsSaasOnboarding(): bool
    {
        return $this->saas_onboarding_completed_at === null;
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
