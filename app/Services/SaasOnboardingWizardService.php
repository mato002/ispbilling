<?php

namespace App\Services;

use App\Jobs\PollRouterHealthJob;
use App\Models\HotspotServer;
use App\Models\IspPaymentMethod;
use App\Models\ISP;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlatformIntegration;
use App\Models\PlatformSaasPlan;
use App\Models\Router;
use App\Support\MpesaGatewayConfig;
use App\Support\MpesaVerificationFlash;
use App\Support\SaasOnboardingContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaasOnboardingWizardService
{
    public function __construct(
        private readonly PlatformSaasBillingService $platformBilling,
    ) {}

    /**
     * If a SaaS onboarding verification payment is already completed but the ISP flag was never set
     * (e.g. partial failure after the callback), align the ISP row.
     */
    public function syncSaasPaymentVerifiedAtIfOnboardingPaymentCompleted(ISP $isp): bool
    {
        if ($isp->saas_payment_verified_at) {
            return false;
        }

        $exists = Payment::query()
            ->where('isp_id', $isp->id)
            ->where('metadata->purpose', 'saas_onboarding_verification')
            ->where('status', 'completed')
            ->exists();

        if (! $exists) {
            return false;
        }

        $isp->update(['saas_payment_verified_at' => now()]);

        return true;
    }

    /**
     * Local only: complete the latest pending onboarding verification payment as if the STK callback succeeded.
     */
    public function completePendingSaasOnboardingMpesaVerificationAsSimulated(ISP $isp): bool
    {
        if (! app()->environment('local')) {
            return false;
        }

        $payment = Payment::query()
            ->where('isp_id', $isp->id)
            ->where('status', 'pending')
            ->where('metadata->purpose', 'saas_onboarding_verification')
            ->latest('id')
            ->first();

        if (! $payment) {
            return false;
        }

        DB::transaction(function () use ($payment, $isp): void {
            $payment->update([
                'transaction_id' => 'DEV-'.now()->format('YmdHis'),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'dev_simulated_verification' => true,
                    'simulated_at' => now()->toIso8601String(),
                ]),
            ]);
            $payment->markAsCompleted();

            $isp->update(['saas_payment_verified_at' => now()]);
        });

        return true;
    }

    /**
     * When the Safaricom callback is delayed or missing, query Daraja STK status and update the payment row.
     */
    public function reconcileOnboardingVerificationPayment(Payment $payment): void
    {
        if (! $payment->isSaasOnboardingVerification() || $payment->status !== 'pending') {
            return;
        }

        $checkoutId = $payment->metadata['mpesa_checkout_request_id']
            ?? ($payment->metadata['stk_push_response']['CheckoutRequestID'] ?? null)
            ?? $payment->transaction_id;

        if (! $checkoutId || ! str_starts_with((string) $checkoutId, 'ws_CO_')) {
            return;
        }

        if ($payment->created_at->greaterThan(now()->subSeconds(8))) {
            return;
        }

        $query = (new MpesaService(null))->verifyPayment((string) $checkoutId);
        $outcome = MpesaService::interpretStkQueryResult($query);

        if ($outcome === 'pending') {
            return;
        }

        $metadata = array_merge($payment->metadata ?? [], [
            'stk_query_result' => $query,
            'stk_query_at' => now()->toIso8601String(),
        ]);

        if ($outcome === 'paid') {
            $payment->update(['metadata' => $metadata]);
            $payment->markAsCompleted();

            if ($payment->isp_id) {
                ISP::query()->whereKey($payment->isp_id)->update([
                    'saas_payment_verified_at' => now(),
                ]);
            }

            return;
        }

        $payment->update([
            'status' => $outcome === 'cancelled' ? 'cancelled' : 'failed',
            'metadata' => $metadata,
        ]);
    }

    public function processIspWizard(ISP $isp, Request $request): RedirectResponse|JsonResponse
    {
        if ($request->input('wizard_action') === 'go_back') {
            return $this->goBackStep($isp);
        }

        $step = (int) $request->input('wizard_step', max(1, (int) $isp->saas_onboarding_step));

        return match ($step) {
            1 => $this->stepPlan($isp, $request),
            2 => $this->stepBusinessAndBilling($isp, $request),
            3 => $this->stepRouterAndPlans($isp, $request),
            4 => $this->stepPaybillAndFinish($isp, $request),
            default => redirect()->to(SaasOnboardingContext::showUrl($isp))->with('error', 'Invalid onboarding step.'),
        };
    }

    protected function goBackStep(ISP $isp): RedirectResponse
    {
        $current = min(4, max(1, (int) $isp->saas_onboarding_step));

        if ($current <= 1) {
            return redirect()->to(SaasOnboardingContext::showUrl($isp))->with('info', 'You are already on the first step.');
        }

        $next = $current - 1;

        $isp->update(['saas_onboarding_step' => $next]);

        return redirect()->to(SaasOnboardingContext::showUrl($isp))->with('info', 'Returned to the previous step.');
    }

    protected function stepPlan(ISP $isp, Request $request): RedirectResponse
    {
        $planId = $request->integer('platform_saas_plan_id');
        $plan = PlatformSaasPlan::query()->where('is_active', true)->whereKey($planId)->first();
        if (! $plan) {
            return back()->withErrors(['platform_saas_plan_id' => 'Choose a valid active plan.'])->withInput();
        }

        $isp->update([
            'saas_pending_platform_plan_id' => $plan->id,
            'saas_onboarding_step' => 2,
        ]);

        return redirect()->to(SaasOnboardingContext::showUrl($isp))->with('success', 'Plan saved. Next: your business details and platform billing.');
    }

    protected function stepBusinessAndBilling(ISP $isp, Request $request): RedirectResponse|JsonResponse
    {
        if ($request->input('wizard_action') === 'local_dev_skip_mpesa_verify') {
            abort_unless(app()->environment('local'), 403);

            if ($isp->saas_billing_method !== 'mpesa') {
                return back()->with('info', 'M-Pesa is not selected as your platform billing method.');
            }

            if ($isp->saas_payment_verified_at) {
                $isp->update(['saas_onboarding_step' => 3]);

                return redirect()->to(SaasOnboardingContext::showUrl($isp))->with('success', 'Already verified. Continue with network setup.');
            }

            if (! $this->completePendingSaasOnboardingMpesaVerificationAsSimulated($isp)) {
                return MpesaVerificationFlash::redirectBackNoPendingPayment($isp);
            }

            $isp->refresh();
            $isp->update(['saas_onboarding_step' => 3]);

            return redirect()->to(SaasOnboardingContext::showUrl($isp))->with(
                'success',
                'Local dev only: M-Pesa verification was completed without a Daraja callback. Next: connect your router.'
            );
        }

        $method = $request->input('saas_billing_method', 'mpesa');
        $normalizedPhone = PlatformSaasBillingService::normalizeKenyanMsisdn($request->input('saas_billing_phone'));

        $settings = $isp->settings ?? [];
        $settings['platform_billing'] = array_merge($settings['platform_billing'] ?? [], array_filter([
            'billing_email' => $request->input('billing_email'),
            'bank_name' => $request->input('bank_name'),
            'bank_account_name' => $request->input('bank_account_name'),
            'bank_account_number' => $request->input('bank_account_number'),
            'bank_branch' => $request->input('bank_branch'),
            'cardholder_name' => $request->input('cardholder_name'),
            'payment_reference_note' => $request->input('payment_reference_note'),
            'captured_at' => now()->toIso8601String(),
        ], static fn ($v) => $v !== null && $v !== ''));

        $isp->update([
            'company_name' => $request->input('company_name'),
            'address' => $request->input('address'),
            'support_phone' => $request->input('support_phone'),
            'saas_company_pin' => $request->input('saas_company_pin'),
            'saas_billing_method' => $method,
            'saas_billing_phone' => $normalizedPhone,
            'saas_white_label_opt_in' => $request->boolean('saas_white_label_opt_in'),
            'settings' => $settings,
        ]);

        if ($method === 'mpesa' && $isp->saas_payment_verified_at) {
            $isp->update(['saas_onboarding_step' => 3]);

            return $this->jsonOrRedirect($request, [
                'success' => true,
                'status' => 'paid',
                'redirect' => SaasOnboardingContext::showUrl($isp),
            ], redirect()->to(SaasOnboardingContext::showUrl($isp))->with('success', 'Billing profile updated. Continue to network setup.'));
        }

        if ($method !== 'mpesa') {
            $isp->update([
                'saas_payment_verified_at' => now(),
                'saas_onboarding_step' => 3,
            ]);

            return $this->jsonOrRedirect($request, [
                'success' => true,
                'status' => 'paid',
                'redirect' => SaasOnboardingContext::showUrl($isp),
            ], redirect()->to(SaasOnboardingContext::showUrl($isp))->with('success', 'Billing profile saved. Connect your first router.'));
        }

        if (! $normalizedPhone) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enter a valid Kenyan number (e.g. 0712345678 or digits after +254).',
                    'errors' => ['saas_billing_phone' => ['Enter a valid Kenyan number (e.g. 0712345678 or digits after +254).']],
                ], 422);
            }

            return back()->withErrors(['saas_billing_phone' => 'Enter a valid Kenyan number (e.g. 0712345678 or 0111296234 after the +254 prefix).'])->withInput();
        }

        return $this->sendOnboardingVerificationStk($isp, $request, forceResend: false);
    }

    /**
     * Send the SaaS onboarding M-Pesa verification STK push.
     */
    protected function sendOnboardingVerificationStk(ISP $isp, Request $request, bool $forceResend = false): RedirectResponse|JsonResponse
    {
        if ($isp->saas_payment_verified_at) {
            $isp->update(['saas_onboarding_step' => 3]);

            return $this->jsonOrRedirect($request, [
                'success' => true,
                'status' => 'paid',
                'redirect' => SaasOnboardingContext::showUrl($isp),
            ], redirect()->to(SaasOnboardingContext::showUrl($isp))->with('success', 'Already verified. Continue with network setup.'));
        }

        $normalizedPhone = PlatformSaasBillingService::normalizeKenyanMsisdn(
            $request->input('saas_billing_phone') ?: $isp->saas_billing_phone
        );

        if (! $normalizedPhone) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enter a valid Kenyan number.',
                    'errors' => ['saas_billing_phone' => ['Enter a valid Kenyan number (e.g. 0712345678 or digits after +254).']],
                ], 422);
            }

            return back()->withErrors([
                'saas_billing_phone' => 'Enter a valid Kenyan number (e.g. 0712345678 or 0111296234 after the +254 prefix).',
            ])->withInput();
        }

        $isp->update(['saas_billing_phone' => $normalizedPhone]);

        $amount = max(1, (int) ceil((float) config('services.mpesa.saas_onboarding_verification_amount', 1)));

        if (! $forceResend) {
            $recentPending = Payment::query()
                ->where('isp_id', $isp->id)
                ->where('status', 'pending')
                ->where('metadata->purpose', 'saas_onboarding_verification')
                ->where('phone_number', $normalizedPhone)
                ->where('created_at', '>=', now()->subSeconds(20))
                ->latest()
                ->first();

            if ($recentPending) {
                return $this->jsonOrRedirect($request, [
                    'success' => true,
                    'payment_id' => $recentPending->id,
                    'poll' => true,
                    'message' => 'Verification STK already in progress.',
                ], redirect()->to(SaasOnboardingContext::showUrl($isp))->with(
                    'success',
                    'Verification STK was just sent — check your phone.'
                ));
            }
        }

        $payment = Payment::create([
            'isp_id' => $isp->id,
            'customer_id' => null,
            'invoice_id' => null,
            'payment_method' => 'mpesa',
            'amount' => $amount,
            'phone_number' => $normalizedPhone,
            'status' => 'pending',
            'metadata' => [
                'purpose' => 'saas_onboarding_verification',
            ],
        ]);

        $stk = (new MpesaService(null))->initiateStkPush($payment);

        if (! ($stk['success'] ?? false)) {
            $message = $stk['message'] ?? 'Could not start M-Pesa verification.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message)->withInput();
        }

        if ($stk['simulated'] ?? false) {
            $isp->update([
                'saas_payment_verified_at' => now(),
                'saas_onboarding_step' => 3,
            ]);

            return $this->jsonOrRedirect($request, [
                'success' => true,
                'status' => 'paid',
                'redirect' => SaasOnboardingContext::showUrl($isp),
            ], redirect()->to(SaasOnboardingContext::showUrl($isp))->with('success', 'Verification simulated (dev). Continue to connect your router.'));
        }

        return $this->jsonOrRedirect($request, [
            'success' => true,
            'payment_id' => $payment->id,
            'poll' => true,
            'message' => 'STK Push sent. Enter your M-Pesa PIN on your phone.',
        ], redirect()->to(SaasOnboardingContext::showUrl($isp))->with(
            'success',
            'We sent an M-Pesa verification of KES '.$amount.' to '.$normalizedPhone.'.'
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function jsonOrRedirect(Request $request, array $payload, RedirectResponse $redirect): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return $redirect;
    }

    protected function stepRouterAndPlans(ISP $isp, Request $request): RedirectResponse
    {
        if ($isp->saas_billing_method === 'mpesa' && ! $isp->saas_payment_verified_at) {
            return redirect()->to(SaasOnboardingContext::showUrl($isp))
                ->with('error', 'Complete M-Pesa verification before connecting your router.');
        }

        $data = $request->only([
            'router_name',
            'router_ip_address',
            'router_port',
            'router_username',
            'router_password',
            'import_profile_names_csv',
            'new_plan_name',
            'new_plan_speed_profile',
            'new_plan_price',
        ]);

        $draft = new Router([
            'isp_id' => $isp->id,
            'name' => $data['router_name'],
            'ip_address' => $data['router_ip_address'],
            'port' => (int) $data['router_port'],
            'username' => $data['router_username'],
            'password' => $data['router_password'],
            'use_ssl' => false,
            'is_active' => true,
        ]);

        $mikrotik = new MikroTikService($draft);
        $result = $mikrotik->testConnection();

        if (! $result['success']) {
            return back()->withErrors(['router_ip_address' => $result['error'] ?? 'Router connection test failed.'])->withInput();
        }

        $profiles = $mikrotik->listHotspotUserProfiles();
        $known = collect($profiles)->pluck('name')->all();
        $csv = (string) ($data['import_profile_names_csv'] ?? '');
        $wanted = collect(preg_split('/[\n,]+/', $csv))
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($wanted as $profileName) {
            if (! in_array($profileName, $known, true)) {
                return back()->withErrors([
                    'import_profile_names_csv' => 'Unknown hotspot profile “'.$profileName.'”. Use names exactly as in MikroTik (comma or newline separated).',
                ])->withInput();
            }
        }

        $router = Router::query()->updateOrCreate(
            [
                'isp_id' => $isp->id,
                'ip_address' => $data['router_ip_address'],
            ],
            [
                'name' => $data['router_name'],
                'port' => (int) $data['router_port'],
                'username' => $data['router_username'],
                'password' => $data['router_password'],
                'use_ssl' => false,
                'is_active' => true,
            ]
        );

        $imported = $this->importHotspotServers($router, $result['hotspot_servers'] ?? []);
        PollRouterHealthJob::dispatch($router->id);

        foreach ($wanted as $profileName) {
            Plan::query()->updateOrCreate(
                [
                    'isp_id' => $isp->id,
                    'name' => $profileName,
                ],
                [
                    'description' => 'Imported from MikroTik',
                    'access_type' => 'hotspot',
                    'price' => 0,
                    'duration_days' => 30,
                    'speed_profile' => $profileName,
                    'billing_type' => 'time',
                    'is_active' => true,
                ]
            );
        }

        if ($request->filled('new_plan_name') && $request->filled('new_plan_speed_profile')) {
            Plan::create([
                'isp_id' => $isp->id,
                'name' => $request->input('new_plan_name'),
                'description' => 'Created during onboarding',
                'access_type' => 'hotspot',
                'price' => $request->input('new_plan_price', 0),
                'duration_days' => 30,
                'speed_profile' => $request->input('new_plan_speed_profile'),
                'billing_type' => 'time',
                'is_active' => true,
            ]);
        }

        if ($imported === 0 && count($wanted) === 0 && ! $request->filled('new_plan_name')) {
            // Allow proceed without plans — they can add later; soft nudge only
        }

        $isp->update(['saas_onboarding_step' => 4]);

        return redirect()->to(SaasOnboardingContext::showUrl($isp))->with(
            'success',
            'Router connected'.($imported > 0 ? " ({$imported} hotspot server records synced)." : '.').' Link your customer Paybill next.'
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $hotspotServers
     */
    protected function importHotspotServers(Router $router, array $hotspotServers): int
    {
        $importedCount = 0;
        foreach ($hotspotServers as $serverData) {
            try {
                HotspotServer::updateOrCreate(
                    ['router_id' => $router->id, 'name' => $serverData['name']],
                    [
                        'isp_id' => $router->isp_id,
                        'interface' => $serverData['interface'] ?? 'unknown',
                        'server_profile' => $serverData['server_profile'] ?? null,
                        'default_profile' => $serverData['default_profile'] ?? null,
                        'login_by' => $serverData['login_by'] ?? 'cookie',
                        'use_radius' => $serverData['use_radius'] ?? false,
                        'radius_accounting' => $serverData['radius_accounting'] ?? false,
                        'mac_cookie_timeout' => $serverData['mac_cookie_timeout'] ?? 300,
                        'idle_timeout' => $serverData['idle_timeout'] ?? 300,
                        'keepalive_timeout' => $serverData['keepalive_timeout'] ?? 60,
                        'status_autologout' => $serverData['status_autologout'] ?? false,
                        'allowed_users' => $serverData['allowed_users'] ?? 0,
                        'shared_users' => $serverData['shared_users'] ?? 0,
                        'mac_format' => $serverData['mac_format'] ?? null,
                        'mac_password' => $serverData['mac_password'] ?? null,
                        'radius_mac_format' => $serverData['radius_mac_format'] ?? null,
                        'radius_mac_password' => $serverData['radius_mac_password'] ?? null,
                        'trial_uptime' => $serverData['trial_uptime'] ?? 0,
                        'trial_uptime_reset' => $serverData['trial_uptime_reset'] ?? 0,
                        'trial_profile' => $serverData['trial_profile'] ?? null,
                        'trial_users' => $serverData['trial_users'] ?? 0,
                        'disabled' => $serverData['disabled'] ?? false,
                        'running' => $serverData['running'] ?? false,
                        'is_active' => ! ($serverData['disabled'] ?? false),
                    ]
                );
                $importedCount++;
            } catch (\Throwable) {
            }
        }

        return $importedCount;
    }

    protected function stepPaybillAndFinish(ISP $isp, Request $request): RedirectResponse
    {
        if (! Router::query()->where('isp_id', $isp->id)->exists()) {
            return redirect()->to(SaasOnboardingContext::showUrl($isp))->with('error', 'Add a router before linking M-Pesa.');
        }

        $data = $request->only([
            'mpesa_consumer_key',
            'mpesa_consumer_secret',
            'mpesa_shortcode',
            'mpesa_passkey',
            'mpesa_environment',
        ]);

        $plan = PlatformSaasPlan::query()->where('is_active', true)->find($isp->saas_pending_platform_plan_id);
        if (! $plan) {
            return redirect()->to(SaasOnboardingContext::showUrl($isp))
                ->with('error', 'Your selected plan is no longer available. Please pick a plan again.');
        }

        if ($request->boolean('mpesa_active') && ! PlatformIntegration::current()->isPaymentPipeEnabled('daraja')) {
            return back()->with('error', 'M-Pesa is disabled at platform level. Save as inactive or contact support.')->withInput();
        }

        $credentials = MpesaGatewayConfig::normalizeCredentials([
            'consumer_key' => $data['mpesa_consumer_key'],
            'consumer_secret' => $data['mpesa_consumer_secret'],
            'shortcode' => $data['mpesa_shortcode'],
            'passkey' => $data['mpesa_passkey'],
            'environment' => $data['mpesa_environment'] ?? config('services.mpesa.environment', 'sandbox'),
        ]);

        $paymentMethod = IspPaymentMethod::query()
            ->forIsp($isp->id)
            ->mpesaProvider()
            ->first();

        $webhookToken = $paymentMethod?->webhook_token ?: Str::random(48);
        $defaultCallback = url('/api/payments/mpesa/callback/'.$webhookToken);

        if ($paymentMethod) {
            $paymentMethod->update([
                'credentials' => $credentials,
                'callback_url' => $paymentMethod->callback_url ?: $defaultCallback,
                'webhook_token' => $paymentMethod->webhook_token ?: $webhookToken,
                'active' => $request->boolean('mpesa_active', true),
            ]);
        } else {
            $paymentMethod = IspPaymentMethod::create([
                'isp_id' => $isp->id,
                'provider' => MpesaGatewayConfig::STORAGE_PROVIDER,
                'credentials' => $credentials,
                'callback_url' => $defaultCallback,
                'webhook_token' => $webhookToken,
                'active' => $request->boolean('mpesa_active', true),
            ]);
        }

        $token = $paymentMethod->webhook_token;
        $validationUrl = url('/api/payments/mpesa/c2b/validation/'.$token);
        $confirmationUrl = url('/api/payments/mpesa/c2b/confirmation/'.$token);

        $c2b = MpesaService::registerC2BUrls(
            [
                'consumer_key' => $credentials['consumer_key'],
                'consumer_secret' => $credentials['consumer_secret'],
                'shortcode' => $credentials['shortcode'],
                'environment' => $credentials['environment'],
            ],
            $validationUrl,
            $confirmationUrl
        );

        if (! ($c2b['success'] ?? false)) {
            return back()->with('error', 'Saved credentials, but Safaricom C2B registration failed: '.($c2b['message'] ?? 'Unknown error').'. Fix keys or shortcode and try again.')->withInput();
        }

        $isp->update(['saas_c2b_registered_at' => now()]);

        $this->platformBilling->completeOnboarding(
            $isp->fresh(),
            $plan,
            $isp->fresh()->billingPayloadForSaasComplete(),
            (bool) $isp->saas_white_label_opt_in
        );

        return SaasOnboardingContext::completionRedirect($isp->fresh());
    }
}
