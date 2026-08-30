<?php

namespace Tests\Unit;

use App\Models\PlatformSetting;
use App\Services\PlatformSaasBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSaasBillingServicePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_grace_days_reads_platform_setting(): void
    {
        PlatformSetting::put('saas_payment_grace_days', '7');

        $service = app(PlatformSaasBillingService::class);

        $this->assertSame(7, $service->graceDays());
    }

    public function test_lock_on_past_due_setting(): void
    {
        PlatformSetting::put('saas_lock_on_past_due', '1');

        $service = app(PlatformSaasBillingService::class);

        $this->assertTrue($service->lockOnPastDue());
    }

    public function test_suspend_customer_services_setting(): void
    {
        PlatformSetting::put('saas_suspend_customer_services', '1');

        $service = app(PlatformSaasBillingService::class);

        $this->assertTrue($service->suspendCustomerServicesOnPlatformSuspend());
    }
}
