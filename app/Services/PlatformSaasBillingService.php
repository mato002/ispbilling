<?php

namespace App\Services;

use App\Jobs\RestoreEntireIspNetworkJob;
use App\Jobs\SuspendEntireIspNetworkJob;
use App\Models\ISP;
use App\Models\Payment;
use App\Models\PlatformSaasInvoice;
use App\Models\PlatformSaasPaymentAttempt;
use App\Models\PlatformSaasPlan;
use App\Models\PlatformSaasSubscription;
use App\Models\PlatformIntegration;
use App\Models\PlatformSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PlatformSaasBillingService
{
    public function graceDays(): int
    {
        return max(0, PlatformSetting::getInt('saas_payment_grace_days', 3));
    }

    public function defaultTrialDays(): int
    {
        return max(0, PlatformSetting::getInt('saas_default_trial_days', 14));
    }

    public function lockOnPastDue(): bool
    {
        // Platform access must be locked when the paid period expires; billing routes remain allowlisted.
        return true;
    }

    public function suspendCustomerServicesOnPlatformSuspend(): bool
    {
        return PlatformSetting::getBool('saas_suspend_customer_services', false);
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

    public function createPendingInvoice(ISP $isp, Carbon $billingAnchor, ?PlatformSaasPlan $plan = null, bool $planChange = false): PlatformSaasInvoice
    {
        $plan ??= $isp->saasPlan;
        if (! $plan) {
            throw new \RuntimeException('ISP has no SaaS plan assigned.');
        }

        $previousPlan = $isp->saasPlan;
        $isp->setRelation('saasPlan', $plan);
        $parts = $this->buildUpcomingInvoiceAmounts($isp);
        if ($previousPlan) {
            $isp->setRelation('saasPlan', $previousPlan);
        }

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
            'metadata' => array_filter([
                'billing_anchor' => $billingAnchor->toIso8601String(),
                'plan_change' => $planChange ?: null,
            ]),
        ]);
    }

    /**
     * @return array{applied: bool, invoice: ?PlatformSaasInvoice, message: string}
     */
    public function changePlan(ISP $isp, PlatformSaasPlan $newPlan): array
    {
        if (! $newPlan->is_active) {
            throw new \InvalidArgumentException('That plan is not available.');
        }

        if ((int) $isp->platform_saas_plan_id === (int) $newPlan->id) {
            return [
                'applied' => true,
                'invoice' => null,
                'message' => 'You are already on this plan.',
            ];
        }

        $currentCount = $isp->customers()->count();
        if (! $newPlan->allowsCustomerCount($currentCount)) {
            throw new \InvalidArgumentException(
                'Cannot switch to this plan: you currently have '.$currentCount.' subscribers, above its max of '.$newPlan->max_customers.'.'
            );
        }

        $requiresPayment = (float) $newPlan->monthly_price > 0
            && ! in_array($isp->saas_subscription_status, ['trialing'], true);

        if (! $requiresPayment) {
            $this->applyPlan($isp, $newPlan);

            return [
                'applied' => true,
                'invoice' => null,
                'message' => 'Plan updated to '.$newPlan->name.'.',
            ];
        }

        $isp->update(['saas_pending_platform_plan_id' => $newPlan->id]);

        $invoice = $this->createPendingInvoice($isp, now(), $newPlan, true);

        return [
            'applied' => false,
            'invoice' => $invoice,
            'message' => 'Plan change reserved. Pay the pending invoice to activate '.$newPlan->name.'.',
        ];
    }

    public function applyPlan(ISP $isp, PlatformSaasPlan $plan): void
    {
        $isp->update([
            'platform_saas_plan_id' => $plan->id,
            'saas_pending_platform_plan_id' => null,
            'saas_tier' => $plan->slug,
        ]);

        PlatformSaasSubscription::query()->updateOrCreate(
            ['isp_id' => $isp->id],
            [
                'platform_saas_plan_id' => $plan->id,
                'status' => $isp->saas_subscription_status === 'trialing'
                    ? PlatformSaasSubscription::STATUS_TRIALING
                    : PlatformSaasSubscription::STATUS_ACTIVE,
            ]
        );
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

    /** Start a Paystack-hosted checkout for an ISP's platform invoice. */
    public function initiatePaystackForInvoice(PlatformSaasInvoice $invoice): array
    {
        $secret = $this->paystackSecretKey();
        if ($secret === '') {
            return ['success' => false, 'message' => 'Paystack is not configured. Set PAYSTACK_SECRET_KEY on the server.'];
        }

        if ($invoice->status !== PlatformSaasInvoice::STATUS_PENDING) {
            return ['success' => false, 'message' => 'This platform invoice is not awaiting payment.'];
        }

        $amount = round($invoice->totalAmount(), 2);
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'The invoice amount must be greater than zero.'];
        }

        $email = filter_var($invoice->isp?->email, FILTER_VALIDATE_EMAIL)
            ? $invoice->isp->email
            : $invoice->isp?->users()->whereNotNull('email')->value('email');
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Add a valid billing email to the ISP profile before paying online.'];
        }

        $attempt = PlatformSaasPaymentAttempt::create([
            'isp_id' => $invoice->isp_id,
            'platform_saas_invoice_id' => $invoice->id,
            'reference' => 'PBSAAS-'.Str::upper(Str::random(24)),
            'amount' => $amount,
            'currency' => strtoupper($invoice->currency ?: 'KES'),
            'status' => 'pending',
        ]);

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->post(rtrim((string) config('services.paystack.base_url'), '/').'/transaction/initialize', [
                    'email' => $email,
                    'amount' => (int) round($amount * 100),
                    'currency' => $attempt->currency,
                    'reference' => $attempt->reference,
                    'callback_url' => route('payments.platform.paystack.callback'),
                    'metadata' => [
                        'purpose' => 'platform_saas_invoice',
                        'invoice_id' => $invoice->id,
                        'isp_id' => $invoice->isp_id,
                    ],
                ]);

            $payload = $response->json();
            $authorizationUrl = data_get($payload, 'data.authorization_url');
            if (! $response->successful() || ! data_get($payload, 'status') || ! is_string($authorizationUrl)) {
                $attempt->update(['status' => 'failed', 'metadata' => ['initialize_error' => data_get($payload, 'message', 'Paystack could not start checkout.')]]);
                return ['success' => false, 'message' => data_get($payload, 'message', 'Paystack could not start checkout.')];
            }

            $attempt->update(['authorization_url' => $authorizationUrl]);

            return ['success' => true, 'url' => $authorizationUrl];
        } catch (\Throwable $exception) {
            $attempt->update(['status' => 'failed', 'metadata' => ['initialize_error' => 'Paystack could not be reached.']]);
            Log::warning('Paystack platform checkout initialization failed', [
                'invoice_id' => $invoice->id,
                'isp_id' => $invoice->isp_id,
                'message' => $exception->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Paystack could not be reached. Please try again.'];
        }
    }

    /** Verify a Paystack reference directly with Paystack and apply only a matching successful payment. */
    public function verifyAndApplyPaystackPayment(string $reference): bool
    {
        $secret = $this->paystackSecretKey();
        if ($secret === '' || $reference === '') {
            return false;
        }

        $attempt = PlatformSaasPaymentAttempt::query()->where('reference', $reference)->first();
        if (! $attempt) {
            return false;
        }
        if ($attempt->status === 'paid') {
            return true;
        }
        if ($attempt->status !== 'pending') {
            return false;
        }

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->timeout(20)
                ->get(rtrim((string) config('services.paystack.base_url'), '/').'/transaction/verify/'.rawurlencode($reference));
        } catch (\Throwable $exception) {
            Log::warning('Paystack platform payment verification failed', ['reference' => $reference, 'message' => $exception->getMessage()]);
            return false;
        }

        $data = $response->json('data');
        if (! $response->successful()
            || ! $response->json('status')
            || data_get($data, 'status') !== 'success'
            || ! hash_equals($attempt->reference, (string) data_get($data, 'reference'))
            || (int) data_get($data, 'amount', 0) !== (int) round((float) $attempt->amount * 100)
            || strtoupper((string) data_get($data, 'currency')) !== strtoupper($attempt->currency)) {
            return false;
        }

        return DB::transaction(function () use ($attempt, $data): bool {
            $attempt = PlatformSaasPaymentAttempt::query()->lockForUpdate()->find($attempt->id);
            if (! $attempt) {
                return false;
            }
            if ($attempt->status === 'paid') {
                return true;
            }
            if ($attempt->status !== 'pending') {
                return false;
            }

            $invoice = PlatformSaasInvoice::query()->lockForUpdate()->find($attempt->platform_saas_invoice_id);
            $isp = $invoice?->isp;
            if (! $invoice || ! $isp) {
                return false;
            }

            $attempt->update([
                'status' => 'paid',
                'gateway_transaction_id' => (string) data_get($data, 'id'),
                'paid_at' => now(),
                'metadata' => ['channel' => data_get($data, 'channel'), 'paid_at' => data_get($data, 'paid_at')],
            ]);

            if ($invoice->status !== PlatformSaasInvoice::STATUS_PAID) {
                $invoice->update([
                    'status' => PlatformSaasInvoice::STATUS_PAID,
                    'paid_at' => now(),
                    'metadata' => array_merge($invoice->metadata ?? [], [
                        'paystack_reference' => $attempt->reference,
                        'paystack_transaction_id' => (string) data_get($data, 'id'),
                    ]),
                ]);

                $periodStart = now()->startOfDay();
                $periodEnd = $periodStart->copy()->addMonth()->subDay();
                $planId = $isp->saas_pending_platform_plan_id ?: $invoice->platform_saas_plan_id;
                $updates = [
                    'saas_subscription_status' => 'active',
                    'saas_current_period_start' => $periodStart->toDateString(),
                    'saas_current_period_end' => $periodEnd->toDateString(),
                    'saas_next_billing_at' => $periodStart->copy()->addMonth(),
                    'saas_grace_ends_at' => null,
                    'platform_access_locked' => false,
                    'platform_access_message' => null,
                    'is_active' => true,
                    'saas_setup_fee_paid_at' => (float) $invoice->setup_fee_amount > 0 ? now() : $isp->saas_setup_fee_paid_at,
                ];
                if ($planId && (int) $planId !== (int) $isp->platform_saas_plan_id) {
                    $plan = PlatformSaasPlan::query()->find($planId);
                    if ($plan) {
                        $updates['platform_saas_plan_id'] = $plan->id;
                        $updates['saas_tier'] = $plan->slug;
                        $updates['saas_pending_platform_plan_id'] = null;
                    }
                }
                $isp->update($updates);
                User::where('isp_id', $isp->id)->update(['is_active' => true]);
                PlatformSaasSubscription::query()->updateOrCreate(
                    ['isp_id' => $isp->id],
                    [
                        'platform_saas_plan_id' => $updates['platform_saas_plan_id'] ?? $isp->platform_saas_plan_id,
                        'status' => PlatformSaasSubscription::STATUS_ACTIVE,
                        'current_period_start' => $periodStart,
                        'current_period_end' => $periodEnd,
                    ]
                );
            }

            return true;
        });
    }

    protected function paystackSecretKey(): string
    {
        $vault = PlatformIntegration::current()->credentials_vault ?? [];

        return (string) ($vault['paystack_secret_key'] ?? config('services.paystack.secret_key', ''));
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

            $planUpdates = [
                'saas_subscription_status' => 'active',
                'saas_current_period_start' => $periodStart->toDateString(),
                'saas_current_period_end' => $periodEnd->toDateString(),
                'saas_next_billing_at' => $nextBill,
                'saas_grace_ends_at' => null,
                'platform_access_locked' => false,
                'platform_access_message' => null,
                'is_active' => true,
                'saas_setup_fee_paid_at' => $setupPaid ? now() : $isp->saas_setup_fee_paid_at,
            ];

            $pendingPlanId = $isp->saas_pending_platform_plan_id ?: $invoice->platform_saas_plan_id;
            if ($pendingPlanId && (int) $pendingPlanId !== (int) $isp->platform_saas_plan_id) {
                $pendingPlan = PlatformSaasPlan::query()->find($pendingPlanId);
                if ($pendingPlan) {
                    $planUpdates['platform_saas_plan_id'] = $pendingPlan->id;
                    $planUpdates['saas_tier'] = $pendingPlan->slug;
                    $planUpdates['saas_pending_platform_plan_id'] = null;
                }
            } elseif ($isp->saas_pending_platform_plan_id) {
                $planUpdates['saas_pending_platform_plan_id'] = null;
            }

            $isp->update($planUpdates);

            PlatformSaasSubscription::query()->updateOrCreate(
                ['isp_id' => $isp->id],
                [
                    'platform_saas_plan_id' => $planUpdates['platform_saas_plan_id'] ?? $isp->platform_saas_plan_id,
                    'status' => PlatformSaasSubscription::STATUS_ACTIVE,
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                ]
            );

            User::where('isp_id', $isp->id)->update(['is_active' => true]);
        });

        if ($this->suspendCustomerServicesOnPlatformSuspend()) {
            RestoreEntireIspNetworkJob::dispatch($isp->id);
        }
    }

    public function suspendIspForOverdueSaas(ISP $isp, ?string $message = null): void
    {
        $isp->update([
            'platform_access_locked' => true,
            'platform_access_message' => $message ?? 'Your platform subscription is suspended after the payment grace period. Pay your invoice to restore access.',
            'saas_subscription_status' => 'suspended',
        ]);

        if ($this->suspendCustomerServicesOnPlatformSuspend()) {
            SuspendEntireIspNetworkJob::dispatch($isp->id, markPlatformSuspension: true);
        }
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

    protected function applyPastDueState(ISP $isp, Carbon $graceEnds): void
    {
        $updates = [
            'saas_subscription_status' => 'past_due',
            'saas_grace_ends_at' => $graceEnds,
        ];

        if ($this->lockOnPastDue()) {
            $updates['platform_access_locked'] = true;
            $updates['platform_access_message'] = 'Your '.config('app.name').' platform invoice is unpaid. Pay from Platform subscription to restore console access.';
        }

        $isp->update($updates);
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
            if ($isp->saas_billing_method === 'mpesa' && $sentDate !== $today->toDateString()) {
                $result = $this->initiateStkForInvoice($pending);
                $meta['stk_sent_on'] = $today->toDateString();
                $meta['last_stk_result'] = $result;
                $pending->update(['metadata' => $meta]);
            }

            $graceEnds = $pending->due_date
                ? Carbon::parse($pending->due_date)->endOfDay()
                : $today->copy()->addDays($this->graceDays())->endOfDay();

            $this->applyPastDueState($isp, $graceEnds);

            return;
        }

        $anchor = $isp->saas_next_billing_at->copy();
        $invoice = $this->createPendingInvoice($isp, $anchor);
        if ($isp->saas_billing_method === 'mpesa') {
            $result = $this->initiateStkForInvoice($invoice);
            $invoice->update([
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'stk_sent_on' => $today->toDateString(),
                    'first_stk_result' => $result,
                ]),
            ]);
        }

        $graceEnds = $invoice->due_date
            ? Carbon::parse($invoice->due_date)->endOfDay()
            : $today->copy()->addDays($this->graceDays())->endOfDay();

        $this->applyPastDueState($isp, $graceEnds);
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
