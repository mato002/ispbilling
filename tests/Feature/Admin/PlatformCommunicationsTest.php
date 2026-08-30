<?php

namespace Tests\Feature\Admin;

use App\Models\ISP;
use App\Models\SmsPlatformLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformCommunicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_sms_master_controller(): void
    {
        $isp = ISP::query()->create([
            'name' => 'TechConnect ISP',
            'company_name' => 'TechConnect Ltd',
            'email' => 'billing@techconnect.test',
            'phone' => '+254700000001',
            'slug' => 'techconnect',
            'sms_balance' => 25.00,
        ]);

        SmsPlatformLedger::query()->create([
            'tenant_id' => $isp->id,
            'amount' => 2500,
            'type' => SmsPlatformLedger::TYPE_TOPUP,
            'sms_count' => 4166,
            'description' => 'M-Pesa API transaction ledger',
        ]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.communications.index'));

        $response->assertOk();
        $response->assertSee('Global Communication &amp; Master SMS Controller', false);
        $response->assertSee('TechConnect ISP');
        $response->assertSee('techconnect');
        $response->assertSee('Recent deposits timeline');
        $response->assertSee('M-Pesa API transaction ledger');
    }

    public function test_super_admin_can_toggle_tenant_sms_dispatch(): void
    {
        $isp = ISP::query()->create([
            'name' => 'Toggle Test ISP',
            'company_name' => 'Toggle Test Ltd',
            'email' => 'toggle@test.isp',
            'phone' => '+254700000002',
            'settings' => ['sms' => ['service_enabled' => true]],
        ]);

        $response = $this->actingAs($this->superAdmin())->post(
            route('admin.communications.tenants.toggle', $isp),
            ['enabled' => 0],
        );

        $response->assertRedirect(route('admin.communications.index'));
        $isp->refresh();
        $this->assertFalse((bool) data_get($isp->settings, 'sms.service_enabled'));
    }

    protected function superAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }
}
