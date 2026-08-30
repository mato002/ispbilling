<?php

namespace Tests\Unit;

use App\Models\ISP;
use App\Support\IspPermissions;
use Tests\TestCase;

class IspPermissionsTest extends TestCase
{
    public function test_isp_admin_always_has_permission(): void
    {
        $user = new \App\Models\User(['role' => 'isp_admin', 'isp_id' => 1]);

        $this->assertTrue(IspPermissions::userCan($user, 'settings.roles'));
    }

    public function test_staff_permission_respects_tenant_matrix(): void
    {
        $isp = new ISP([
            'settings' => [
                'role_permissions' => [
                    'isp_staff' => [
                        'customers.view' => true,
                        'settings.roles' => false,
                    ],
                ],
            ],
        ]);

        $user = new \App\Models\User(['role' => 'isp_staff', 'isp_id' => 1]);
        $user->setRelation('isp', $isp);

        $this->assertTrue(IspPermissions::userCan($user, 'customers.view'));
        $this->assertFalse(IspPermissions::userCan($user, 'settings.roles'));
    }
}
