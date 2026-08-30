<?php

namespace Tests\Unit;

use App\Support\Navigation\ModuleWorkspaceNav;
use Tests\TestCase;

class ModuleWorkspaceNavTest extends TestCase
{
    public function test_isp_navigation_workspaces_are_defined(): void
    {
        $modules = config('navigation_workspaces.isp');

        $this->assertNotEmpty($modules);
        $this->assertSame('Dashboard', $modules[0]['label']);
        $this->assertSame('operations', $modules[1]['key']);
        $this->assertNotEmpty($modules[1]['workspace']);
    }

    public function test_admin_platform_module_groups_workspace_tabs(): void
    {
        $modules = config('navigation_workspaces.admin');
        $platform = collect($modules)->firstWhere('key', 'platform');

        $this->assertNotNull($platform);
        $this->assertGreaterThanOrEqual(10, count($platform['workspace']));
    }

    public function test_settings_workspace_nav_delegates_to_module_config(): void
    {
        $nav = app(ModuleWorkspaceNav::class);
        $settings = collect($nav->modules('isp'))->firstWhere('key', 'settings');

        $this->assertGreaterThanOrEqual(8, count($settings['workspace']));
        $this->assertSame('Operations hub', $settings['workspace'][0]['label']);
        $this->assertSame('Audit logs', $settings['workspace'][8]['label']);
    }
}
