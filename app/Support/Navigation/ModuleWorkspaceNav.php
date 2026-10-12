<?php

namespace App\Support\Navigation;

use Illuminate\Support\Arr;
use App\Support\SuperAdminWorkspaceContext;

class ModuleWorkspaceNav
{
    public function scopeForUser(?\App\Models\User $user = null): ?string
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return SuperAdminWorkspaceContext::active() ? 'isp' : 'admin';
        }

        if ($user->isTechnician()) {
            return 'technician';
        }

        if ($user->isIspAdmin() || $user->isIspStaff()) {
            return 'isp';
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function modules(?string $scope = null): array
    {
        $scope ??= $this->scopeForUser();

        if (! $scope) {
            return [];
        }

        return config("navigation_workspaces.{$scope}", []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function currentModule(?string $scope = null): ?array
    {
        if (! request()->route()) {
            return null;
        }

        foreach ($this->modules($scope) as $module) {
            if ($this->routeMatchesPatterns(Arr::wrap($module['active'] ?? []))) {
                return $module;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function workspaceTabs(?string $scope = null): array
    {
        $module = $this->currentModule($scope);

        if (! $module) {
            return [];
        }

        return $module['workspace'] ?? [];
    }

    public function moduleLabel(?string $scope = null): ?string
    {
        return $this->currentModule($scope)['label'] ?? null;
    }

    public function isModuleActive(array $module): bool
    {
        return $this->routeMatchesPatterns(Arr::wrap($module['active'] ?? []));
    }

    public function isTabActive(array $item): bool
    {
        $patterns = Arr::wrap($item['active'] ?? [$item['route'] ?? '']);

        if ($patterns === [''] || ! $this->routeMatchesPatterns($patterns)) {
            return false;
        }

        if (array_key_exists('query', $item)) {
            foreach ($item['query'] as $key => $expected) {
                $actual = request()->query($key);

                if ($expected === null) {
                    if ($actual !== null && $actual !== '') {
                        return false;
                    }

                    continue;
                }

                if ((string) $actual !== (string) $expected) {
                    return false;
                }
            }
        } elseif (! empty($item['params']) || ! empty($item['hash'])) {
            return $this->hrefForItem($item) === request()->fullUrl();
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function hrefForItem(array $item): string
    {
        $params = $item['params'] ?? [];
        $url = route($item['route'], $params);

        if (! empty($item['hash'])) {
            $url .= '#'.$item['hash'];
        }

        return $url;
    }

    /**
     * @param  list<string>  $patterns
     */
    private function routeMatchesPatterns(array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern !== '' && request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}
