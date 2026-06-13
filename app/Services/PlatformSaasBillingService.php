<?php

namespace App\Services;

use App\Models\ISP;
use App\Models\Payment;
use App\Models\PlatformSaasInvoice;
use App\Models\PlatformSaasPlan;
use App\Models\PlatformSaasSubscription;
use App\Models\PlatformSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlatformSaasBillingService
{
    public function graceDays(): int
    {
        return max(0, (int) PlatformSetting::get('saas_payment_grace_days', '3'));
    }

    public static function normalizeKenyanMsisdn(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', trim($raw));

        if ($digits === '') {
            return null;
        }

        if (preg_match('/^0[0-9]{9}$/', $digits)) {
            $digits = '254'.substr($digits, 1);
        } elseif (preg_match('/^[0-9]{9}$/', $digits)) {
            // Local number entered after a +254 UI prefix (e.g. 728883160 or 111296234).
            $digits = '254'.$digits;
        } elseif (preg_match('/^254[0-9]{9}$/', $digits)) {
            // Already international format.
        } else {
            return null;
        }

        return preg_match('/^254[0-9]{9}$/', $digits) ? $digits : null;
    }

    /**
     * ISP admin (or super admin on behalf) completes plan + billing / payment profile.
     *
     * @param  array<string, mixed>  $billing  Keys: company_name, saas_company_pin, saas_billing_method,
     *                                         saas_billing_phone (required for mpesa), billing_email (card),
     *                                         bank_*, cardholder_name, payment_reference_note
     */
    public function completeOnboarding(ISP $isp, PlatformSaasPlan $plan, array $billing, bool $whiteLabelOptIn): void
    {
        $method = $billing['saas_billing_method'] ?? 'mpesa';

        $normalizedPhone = static::normalizeKenyanMsisdn($billing['saas_billing_phone'] ?? $isp->saas_billing_phone);
        if ($method === 'mpesa') {
            if (! $normalizedPhone) {
                throw new \InvalidArgumentException('A valid M-Pesa billing number (254XXXXXXXXX) is required.');
            }
        }

        $trialDays = max(0, (int) $plan->trial_days);
        $trialEnds = now()->addDays($trialDays);

        $settings = $isp->settings ?? [];
        $settings['platform_billing'] = array_filter([
            'method' => $method,
            'billing_email' => $billing['billing_email'] ?? null,
            'bank_name' => $billing['bank_name'] ?? null,
            'bank_account_name' => $billing['bank_account_name'] ?? null,
            'bank_account_number' => $billing['bank_account_number'] ?? null,
            'bank_branch' => $billing['bank_branch'] ?? null,
            'cardholder_name' => $billing['cardholder_name'] ?? null,
            'payment_reference_note' => $billing['payment_reference_note'] ?? null,
            'captured_at' => now()->toIso8601String(),
        ], static fn ($v) => $v !== null && $v !== '');

        $isp->update([
            'platform_saas_plan_id' => $plan->id,
            'saas_pending_platform_plan_id' => null,
            'saas_tier' => $plan->slug,
            'company_name' => $billing['company_name'] ?? $isp->company_name,
            'saas_company_pin' => $billing['saas_company_pin'] ?? null,
            'saas_billing_phone' => $normalizedPhone,
            'saas_billing_method' => $method,
            'saas_white_label_opt_in' => $whiteLabelOptIn,
            'saas_subscription_status' => 'trialing',
            'saas_trial_ends_at' => $trialEnds,
            'saas_next_billing_at' => $trialEnds,
            'saas_onboarding_completed_at' => now(),
            'saas_onboarding_step' => 1,
            'saas_payment_verified_at' => $isp->saas_payment_verified_at ?? now(),
            'saas_grace_ends_at' => null,
            'platform_access_locked' => false,
            'is_active' => true,
            'settings' => $settings,
        ]);

        User::where('isp_id', $isp->id)->update(['is_active' => true]);

        PlatformSaasSubscription::query()->updateOrCreate(
            ['isp_id' => $isp->id],
            [
                'platform_saas_plan_id' => $plan->id,
                'status' => PlatformSaasSubscription::STATUS_TRIALING,
                'trial_ends_at' => $trialEnds,
                'metadata' => array_merge(
                    $isp->platformSaasSubscription?->metadata ?? [],
                    ['completed_at' => now()->toIso8601String()]
                ),
            ]
        );
    }

    public function buildUpcomingInvoiceAmounts(ISP $isp): array
    {
        $plan = $isp->saasPlan;
        if (! $plan) {
            return ['subscription' => 0.0, 'setup' => 0.0];
        }

        $setup = 0.0;
        if ($isp->saas_white_label_opt_in && ! $isp->saas_setup_fee_paid_at && (float) $plan->setup_fee > 0) {
            $setup = (float) $plan->setup_fee;
        }

        return [
            'subscription' => (float) $plan->monthly_price,
            'setup' => $setup,
        ];
    }

    public function createPendingInvoice(ISP $isp, Carbon $billingAnchor): PlatformSaasInvoice
    {
        $plan = $isp->saasPlan;
        if (! $plan) {
            throw new \RuntimeException('ISP has no SaaS plan assigned.');
        }

        $parts = $this->buildUpcomingInvoiceAmounts($isp);
        $periodStart = $billingAnchor->copy()->startOfDay();
        $periodEnd = $billingAnchor->copy()->addMonth()->subDay();

        return PlatformSaasInvoice::create([
            'isp_id' => $isp->id,
            'platform_saas_plan_id' => $plan->id,
            'invoice_number' => PlatformSaasInvoice::generateInvoiceNumber(),
            'subscription_amount' => $parts['subscription'],
            'setup_fee_amount' => $parts['setup'],
            'currency' => $isp->currency ?? 'KES',
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'due_date' => $periodStart->copy()->addDays($this->graceDays())->toDateString(),
            'status' => PlatformSaasInvoice::STATUS_PENDING,
            'metadata' => [
                'billing_anchor' => $billingAnchor->toIso8601String(),
            ],
        ]);
    }

    public function initiateStkForInvoice(PlatformSaasInvoice $invoice): array
    {
        $isp = $invoice->isp;
        $phone = static::normalizeKenyanMsisdn($isp->saas_billing_phone);
        if (! $phone) {
            return ['success' => false, 'message' => 'Billing phone is not set or invalid.'];
        }

        $amount = (int) ceil($invoice->totalAmount());
        if ($amount < 1) {
            return ['success' => false, 'message' => 'Invoice amount is zero.'];
        }

        $payment = Payment::create([
            'isp_id' => $isp->id,
            'customer_id' => null,
            'invoice_id' => null,
            'payment_method' => 'mpesa',
            'amount' => $invoice->totalAmount(),
            'phone_number' => $phone,
            'status' => 'pending',
            'metadata' => [
                'purpose' => 'platform_saas',
                'platform_saas_invoice_id' => $invoice->id,
            ],
        ]);

        $mpesa = new MpesaService(null);

        return $mpesa->initiateStkPush($payment);
    }

    public function onMpesaPaymentSucceeded(Payment $payment): void
    {
        $invoiceId = $payment->metadata['platform_saas_invoice_id'] ?? null;
        if (! $invoiceId) {
            return;
        }

        $invoice = PlatformSaasInvoice::query()->find($invoiceId);
        if (! $invoice || $invoice->status === PlatformSaasInvoice::STATUS_PAID) {
            return;
        }

        $isp = $invoice->isp;

        DB::transaction(function () use ($invoice, $isp, $payment) {
            $invoice->update([
                'status' => PlatformSaasInvoice::STATUS_PAID,
                'paid_at' => now(),
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'platform_payment_id' => $payment->id,
                ]),
            ]);

            $periodStart = now()->startOfDay();
            $periodEnd = $periodStart->copy()->addMonth()->subDay();
            $nextBill = $periodStart->copy()->addMonth();

            $setupPaid = $invoice->setup_fee_amount > 0;

            $isp->update([
                'saas_subscription_status' => 'active',
                'saas_current_period_start' => $periodStart->toDateString(),
                'saas_current_period_end' => $periodEnd->toDateString(),
                'saas_next_billing_at' => $nextBill,
                'saas_grace_ends_at' => null,
                'platform_access_locked' => false,
                'is_active' => true,
                'saas_setup_fee_paid_at' => $setupPaid ? now() : $isp->saas_setup_fee_paid_at,
            ]);

            User::where('isp_id', $isp->id)->update(['is_active' => true]);
        });
    }

    public function suspendIspForOverdueSaas(ISP $isp, ?string $message = null): void
    {
        $isp->update([
            'is_active' => false,
            'platform_access_locked' => true,
            'platform_access_message' => $message ?? 'Your PrimeBilling platform subscription is suspended after the payment grace period. Please pay your invoice to restore access.',
            'saas_subscription_status' => 'suspended',
        ]);

        User::where('isp_id', $isp->id)->update(['is_active' => false]);
    }

    /**
     * Daily scheduler: open billing cycles, send STK reminders, suspend past grace.
     */
    public function runDailyCycle(): void
    {
        $today = now()->startOfDay();

        ISP::query()
            ->whereNotNull('saas_onboarding_completed_at')
            ->whereNotNull('platform_saas_plan_id')
            ->whereIn('saas_subscription_status', ['trialing', 'active', 'past_due'])
            ->where('is_active', true)
            ->with('saasPlan')
            ->chunkById(50, function ($isps) use ($today) {
                foreach ($isps as $isp) {
                    try {
                        $this->processIspBillingDay($isp, $today);
                    } catch (\Throwable $e) {
                        Log::error('Platform SaaS billing error', [
                            'isp_id' => $isp->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->suspendTenantsPastGrace();
    }

    protected function processIspBillingDay(ISP $isp, Carbon $today): void
    {
        if (! $isp->saas_next_billing_at) {
            return;
        }

        if ($today->lt($isp->saas_next_billing_at->copy()->startOfDay())) {
            return;
        }

        $pending = $isp->platformSaasInvoices()
            ->where('status', PlatformSaasInvoice::STATUS_PENDING)
            ->latest()
            ->first();

        if ($pending) {
            $meta = $pending->metadata ?? [];
            $sentDate = $meta['stk_sent_on'] ?? null;
            if ($sentDate !== $today->toDateString()) {
                $result = $this->initiateStkForInvoice($pending);
                $meta['stk_sent_on'] = $today->toDateString();
                $meta['last_stk_result'] = $result;
                $pending->update(['metadata' => $meta]);
            }

            $isp->update([
                'saas_subscription_status' => 'past_due',
                'saas_grace_ends_at' => $pending->due_date
                    ? Carbon::parse($pending->due_date)->endOfDay()
                    : $today->copy()->addDays($this->graceDays())->endOfDay(),
            ]);

            return;
        }

        $anchor = $isp->saas_next_billing_at->copy();
        $invoice = $this->createPendingInvoice($isp, $anchor);
        $result = $this->initiateStkForInvoice($invoice);
        $invoice->update([
            'metadata' => array_merge($invoice->metadata ?? [], [
                'stk_sent_on' => $today->toDateString(),
                'first_stk_result' => $result,
            ]),
        ]);

        $isp->update([
            'saas_subscription_status' => $isp->saas_subscription_status === 'trialing' ? 'past_due' : 'past_due',
            'saas_grace_ends_at' => $invoice->due_date
                ? Carbon::parse($invoice->due_date)->endOfDay()
                : $today->copy()->addDays($this->graceDays())->endOfDay(),
        ]);
    }

    protected function suspendTenantsPastGrace(): void
    {
        ISP::query()
            ->where('saas_subscription_status', 'past_due')
            ->whereNotNull('saas_grace_ends_at')
            ->where('saas_grace_ends_at', '<', now())
            ->where('is_active', true)
            ->chunkById(50, function ($isps) {
                foreach ($isps as $isp) {
                    $unpaid = $isp->platformSaasInvoices()
                        ->where('status', PlatformSaasInvoice::STATUS_PENDING)
                        ->exists();
                    if ($unpaid) {
                        $this->suspendIspForOverdueSaas($isp);
                    }
                }
            });
    }
}
