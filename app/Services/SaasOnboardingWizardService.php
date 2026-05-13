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

    public function processIspWizard(ISP $isp, Request $request): RedirectResponse
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
            default => redirect()->route('isp.saas-onboarding.show')->with('error', 'Invalid onboarding step.'),
        };
    }

    protected function goBackStep(ISP $isp): RedirectResponse
    {
        $current = min(4, max(1, (int) $isp->saas_onboarding_step));

        if ($current <= 1) {
            return redirect()->route('isp.saas-onboarding.show')->with('info', 'You are already on the first step.');
        }

        $next = $current - 1;

        $isp->update(['saas_onboarding_step' => $next]);

        return redirect()->route('isp.saas-onboarding.show')->with('info', 'Returned to the previous step.');
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

        return redirect()->route('isp.saas-onboarding.show')->with('success', 'Plan saved. Next: your business details and platform billing.');
    }

    protected function stepBusinessAndBilling(ISP $isp, Request $request): RedirectResponse
    {
        if ($request->input('wizard_action') === 'local_dev_skip_mpesa_verify') {
            abort_unless(app()->environment('local'), 403);

            if ($isp->saas_billing_method !== 'mpesa') {
                return back()->with('info', 'M-Pesa is not selected as your platform billing method.');
            }

            if ($isp->saas_payment_verified_at) {
                $isp->update(['saas_onboarding_step' => 3]);

                return redirect()->route('isp.saas-onboarding.show')->with('success', 'Already verified. Continue with network setup.');
            }

            if (! $this->completePendingSaasOnboardingMpesaVerificationAsSimulated($isp)) {
                return back()->with(
                    'error',
                    'No pending KES verification payment found. Save this step with M-Pesa first (that sends the STK), then use “Skip STK callback”, or run: php artisan mpesa:simulate-onboarding-verification '.$isp->id.' --force'
                );
            }

            $isp->refresh();
            $isp->update(['saas_onboarding_step' => 3]);

            return redirect()->route('isp.saas-onboarding.show')->with(
                'success',
                'Local dev only: M-Pesa verification was completed without a Daraja callback. Next: connect your router.'
            );
        }

        if ($request->input('wizard_action') === 'continue_after_verify') {
            if ($isp->saas_billing_method === 'mpesa' && ! $isp->saas_payment_verified_at) {
                $this->syncSaasPaymentVerifiedAtIfOnboardingPaymentCompleted($isp);
                $isp->refresh();

                if (! $isp->saas_payment_verified_at) {
                    $hasPendingVerification = Payment::query()
                        ->where('isp_id', $isp->id)
                        ->where('status', 'pending')
                        ->where('metadata->purpose', 'saas_onboarding_verification')
                        ->exists();

                    $amount = max(1, (int) ceil((float) config('services.mpesa.saas_onboarding_verification_amount', 1)));

                    $base = 'PrimeBilling still has no successful M-Pesa callback for your KES '.$amount.' verification, so your account is not marked as verified yet. '
                        .($hasPendingVerification
                            ? 'A verification payment is still pending in the database — approving on your phone is not enough until Safaricom can POST to your public callback URL. '
                            : '');

                    $fix = 'Fix the callback: keep ngrok pointed at your app, run scripts\\sync-ngrok-env.ps1, then php artisan config:clear, and submit step 2 again so a new STK uses the updated URLs. Check http://127.0.0.1:4040 for POST /api/payments/mpesa/callback. '
                        .'Or choose Bank or Card above to skip platform M-Pesa.';

                    if (app()->environment('local')) {
                        $fix .= ' On APP_ENV=local you can also click Skip STK callback (local only) next to Continue, or run: php artisan mpesa:simulate-onboarding-verification '.$isp->id.' --force';
                    }

                    return back()->with('error', $base.$fix);
                }
            }

            $isp->update(['saas_onboarding_step' => 3]);

            return redirect()->route('isp.saas-onboarding.show')->with('success', 'Payment verified. Connect your first router.');
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

            return redirect()->route('isp.saas-onboarding.show')->with('success', 'Billing profile updated. Continue to network setup.');
        }

        if ($method !== 'mpesa') {
            $isp->update([
                'saas_payment_verified_at' => now(),
                'saas_onboarding_step' => 3,
            ]);

            return redirect()->route('isp.saas-onboarding.show')->with('success', 'Billing profile saved. Connect your first router.');
        }

        if (! $normalizedPhone) {
            return back()->withErrors(['saas_billing_phone' => 'Enter a valid Kenyan M-Pesa number (2547XXXXXXXX).'])->withInput();
        }

        $isp->update(['saas_billing_phone' => $normalizedPhone]);

        $amount = max(1, (int) ceil((float) config('services.mpesa.saas_onboarding_verification_amount', 1)));

        $recentPending = Payment::query()
            ->where('isp_id', $isp->id)
            ->where('status', 'pending')
            ->where('metadata->purpose', 'saas_onboarding_verification')
            ->where('created_at', '>=', now()->subMinutes(2))
            ->latest()
            ->first();

        if ($recentPending && (string) $recentPending->phone_number === $normalizedPhone) {
            return redirect()->route('isp.saas-onboarding.show')->with(
                'success',
                'A verification prompt was already sent recently. Approve it on your phone, then use “Continue to network setup”.'
            );
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

        $mpesa = new MpesaService(null);
        $stk = $mpesa->initiateStkPush($payment);

        if (! ($stk['success'] ?? false)) {
            return back()->with('error', $stk['message'] ?? 'Could not start M-Pesa verification.')->withInput();
        }

        if ($stk['simulated'] ?? false) {
            $isp->update([
                'saas_payment_verified_at' => now(),
                'saas_onboarding_step' => 3,
            ]);

            return redirect()->route('isp.saas-onboarding.show')->with('success', 'Verification simulated (dev). Continue to connect your router.');
        }

        return redirect()->route('isp.saas-onboarding.show')->with(
            'success',
            'We sent an M-Pesa verification of KES '.$amount.'. Approve it on your phone, then click “Continue to network setup”.'
        );
    }

    protected function stepRouterAndPlans(ISP $isp, Request $request): RedirectResponse
    {
        if ($isp->saas_billing_method === 'mpesa' && ! $isp->saas_payment_verified_at) {
            return redirect()->route('isp.saas-onboarding.show')
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

        return redirect()->route('isp.saas-onboarding.show')->with(
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
            return redirect()->route('isp.saas-onboarding.show')->with('error', 'Add a router before linking M-Pesa.');
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
            return redirect()->route('isp.saas-onboarding.show')
                ->with('error', 'Your selected plan is no longer available. Please pick a plan again.');
        }

        if ($request->boolean('mpesa_active') && ! PlatformIntegration::current()->isPaymentPipeEnabled('daraja')) {
            return back()->with('error', 'M-Pesa is disabled at platform level. Save as inactive or contact support.')->withInput();
        }

        $credentials = [
            'consumer_key' => $data['mpesa_consumer_key'],
            'consumer_secret' => $data['mpesa_consumer_secret'],
            'shortcode' => $data['mpesa_shortcode'],
            'passkey' => $data['mpesa_passkey'],
            'environment' => $data['mpesa_environment'] ?? config('services.mpesa.environment', 'sandbox'),
        ];

        $paymentMethod = IspPaymentMethod::query()
            ->forIsp($isp->id)
            ->byProvider('daraja')
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
                'provider' => 'daraja',
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

        return redirect()->route('isp.dashboard')->with('success', 'Welcome to PrimeBilling — your network, plans, and M-Pesa C2B are configured.');
    }
}
