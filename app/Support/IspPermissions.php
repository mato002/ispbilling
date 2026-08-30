<?php

namespace App\Support;

use App\Models\ISP;
use App\Models\User;

class IspPermissions
{
    public static function allKeys(): array
    {
        $keys = [];

        foreach (config('isp_permissions.groups', []) as $group) {
            foreach (array_keys($group['permissions'] ?? []) as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public static function groups(): array
    {
        return config('isp_permissions.groups', []);
    }

    public static function roleMeta(string $role): ?array
    {
        return config("isp_permissions.roles.{$role}");
    }

    public static function configurableRoles(): array
    {
        return ['isp_staff'];
    }

    public static function defaultsForRole(string $role): array
    {
        if ($role === 'isp_admin') {
            return array_fill_keys(self::allKeys(), true);
        }

        if ($role === 'isp_technician') {
            return array_merge(
                array_fill_keys(self::allKeys(), false),
                config('isp_permissions.defaults.isp_technician', [])
            );
        }

        $defaults = config("isp_permissions.defaults.{$role}", []);

        return array_merge(
            array_fill_keys(self::allKeys(), false),
            $defaults
        );
    }

    public static function overridesForIsp(ISP $isp, string $role): array
    {
        $settings = $isp->settings ?? [];
        $stored = $settings['role_permissions'][$role] ?? [];

        if (! is_array($stored)) {
            return [];
        }

        $allowedKeys = self::allKeys();
        $normalized = [];

        foreach ($stored as $key => $value) {
            if (in_array($key, $allowedKeys, true)) {
                $normalized[$key] = (bool) $value;
            }
        }

        return $normalized;
    }

    public static function matrixForRole(ISP $isp, string $role): array
    {
        $defaults = self::defaultsForRole($role);
        $overrides = self::overridesForIsp($isp, $role);

        return array_merge($defaults, $overrides);
    }

    public static function saveStaffMatrix(ISP $isp, array $posted): void
    {
        $matrix = [];
        $allowed = self::allKeys();

        foreach ($allowed as $key) {
            $matrix[$key] = ! empty($posted[$key]);
        }

        $settings = $isp->settings ?? [];
        $settings['role_permissions'] = array_merge($settings['role_permissions'] ?? [], [
            'isp_staff' => $matrix,
        ]);
        $settings['meta'] = array_merge($settings['meta'] ?? [], [
            'last_settings_change_at' => now()->toIso8601String(),
        ]);

        $isp->update(['settings' => $settings]);
        IspSettingsChangeTracker::touch($isp->id);
    }

    public static function userCan(User $user, string $permission): bool
    {
        if ($user->isSuperAdmin() || $user->isIspAdmin()) {
            return true;
        }

        if ($user->isTechnician()) {
            if (! in_array($permission, self::allKeys(), true)) {
                return false;
            }

            return (bool) (self::defaultsForRole('isp_technician')[$permission] ?? false);
        }

        if (! $user->isIspStaff() || ! $user->isp_id) {
            return false;
        }

        if (! in_array($permission, self::allKeys(), true)) {
            return false;
        }

        $isp = $user->isp;
        if (! $isp) {
            return false;
        }

        $matrix = self::matrixForRole($isp, 'isp_staff');

        return (bool) ($matrix[$permission] ?? false);
    }
}
