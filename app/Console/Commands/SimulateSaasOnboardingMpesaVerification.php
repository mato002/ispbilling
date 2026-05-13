<?php

namespace App\Console\Commands;

use App\Models\ISP;
use App\Services\SaasOnboardingWizardService;
use Illuminate\Console\Command;

/**
 * Local development only: mark the latest SaaS onboarding M-Pesa verification payment
 * as completed and set the ISP's saas_payment_verified_at, as a successful STK callback would.
 */
class SimulateSaasOnboardingMpesaVerification extends Command
{
    protected $signature = 'mpesa:simulate-onboarding-verification
                            {isp_id? : ISP id (defaults to first ISP with pending verification)}
                            {--force : Required to apply changes}';

    protected $description = '[local only] Complete SaaS onboarding M-Pesa verification without a real Daraja callback';

    public function handle(SaasOnboardingWizardService $wizard): int
    {
        if (! app()->environment('local')) {
            $this->error('This command only runs when APP_ENV=local.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->warn('Refusing to run without --force (safety). Example:');
            $this->line('  php artisan mpesa:simulate-onboarding-verification 1 --force');

            return self::FAILURE;
        }

        $ispId = $this->argument('isp_id');
        if ($ispId !== null && $ispId !== '') {
            $isp = ISP::query()->find($ispId);
            if (! $isp) {
                $this->error("ISP id {$ispId} not found.");

                return self::FAILURE;
            }
        } else {
            $isp = ISP::query()
                ->whereNull('saas_payment_verified_at')
                ->where('saas_billing_method', 'mpesa')
                ->orderBy('id')
                ->first();

            if (! $isp) {
                $this->error('No ISP found with saas_billing_method=mpesa and saas_payment_verified_at=null.');

                return self::FAILURE;
            }
        }

        if (! $wizard->completePendingSaasOnboardingMpesaVerificationAsSimulated($isp)) {
            $this->error("No pending payment with purpose saas_onboarding_verification for ISP {$isp->id}.");

            return self::FAILURE;
        }

        $this->info("ISP {$isp->id} ({$isp->name}): saas_payment_verified_at set.");

        return self::SUCCESS;
    }
}
