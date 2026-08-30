<?php

namespace Tests\Unit;

use Tests\TestCase;

class SettingsWorkspaceNavTest extends TestCase
{
    public function test_settings_workspace_config_is_defined(): void
    {
        $admin = config('settings_workspaces.admin');
        $isp = config('settings_workspaces.isp');

        $this->assertNotEmpty($admin);
        $this->assertNotEmpty($isp);
        $this->assertSame('General', $admin[0]['label']);
        $this->assertSame('Operations hub', $isp[0]['label']);
        $this->assertSame('Audit logs', $isp[8]['label']);
    }
}
