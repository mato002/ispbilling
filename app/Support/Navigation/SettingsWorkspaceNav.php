<?php

namespace App\Support\Navigation;

/**
 * @deprecated Use ModuleWorkspaceNav — kept for backward compatibility.
 */
class SettingsWorkspaceNav
{
    public function __construct(
        private readonly ModuleWorkspaceNav $moduleNav,
    ) {}

    public function scopeForCurrentRoute(): ?string
    {
        $module = $this->moduleNav->currentModule();

        if (! $module || ($module['key'] ?? '') !== 'settings') {
            return null;
        }

        return $this->moduleNav->scopeForUser();
    }

    /**
     * @return list<array{label: string, route: string, active: list<string>}>
     */
    public function items(string $scope): array
    {
        foreach ($this->moduleNav->modules($scope) as $module) {
            if (($module['key'] ?? '') === 'settings') {
                return $module['workspace'] ?? [];
            }
        }

        return config("settings_workspaces.{$scope}", []);
    }

    public function isActive(array $item): bool
    {
        return $this->moduleNav->isTabActive($item);
    }
}
