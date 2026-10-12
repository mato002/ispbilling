<?php

namespace App\Support;

use App\Models\ISP;
use Illuminate\Http\Request;

/** Session marker for a Super Admin temporarily authenticated as an ISP admin. */
final class SuperAdminWorkspaceContext
{
    public const SESSION_KEY = 'super_admin_impersonation';

    public static function ispId(?Request $request = null): ?int
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $request ??= request();
        $context = $request?->session()->get(self::SESSION_KEY);
        $id = is_array($context) ? ($context['isp_id'] ?? null) : null;

        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    public static function active(?Request $request = null): bool
    {
        if (self::ispId($request) === null) {
            return false;
        }

        $request ??= request();
        $context = $request?->session()->get(self::SESSION_KEY, []);

        return is_array($context)
            && isset($context['super_admin_id'], $context['target_user_id'])
            && (string) auth()->id() === (string) $context['target_user_id'];
    }

    public static function superAdminId(?Request $request = null): ?int
    {
        if (! self::active($request)) {
            return null;
        }

        $request ??= request();
        $id = $request?->session()->get(self::SESSION_KEY.'.super_admin_id');

        return is_numeric($id) ? (int) $id : null;
    }

    public static function isp(?Request $request = null): ?ISP
    {
        $id = self::ispId($request);

        return $id ? ISP::query()->find($id) : null;
    }
}
