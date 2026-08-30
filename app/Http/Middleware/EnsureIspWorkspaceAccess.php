<?php

namespace App\Http\Middleware;

use App\Models\ISP;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces ISP workspace maintenance mode and optional security IP allowlist.
 */
class EnsureIspWorkspaceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin() || ! $user->isp_id) {
            return $next($request);
        }

        /** @var ISP|null $isp */
        $isp = $request->attributes->get(LoadTenantIsp::ATTRIBUTE)
            ?? ISP::query()->find($user->isp_id);

        if (! $isp) {
            return $next($request);
        }

        if ($this->isAllowlistedSettingsRoute($request)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        $allowlist = $this->parseIpList(data_get($isp->settings, 'security.ip_allowlist'));
        if ($allowlist !== [] && ! $this->ipMatchesList($clientIp, $allowlist)) {
            abort(403, 'Your IP address is not allowed to access this workspace.');
        }

        $maintenance = data_get($isp->settings, 'maintenance', []);
        if (! empty($maintenance['maintenance_mode_enabled'])) {
            $maintenanceAllow = $this->parseIpList($maintenance['maintenance_allow_ip'] ?? '');
            if ($maintenanceAllow === [] || ! $this->ipMatchesList($clientIp, $maintenanceAllow)) {
                if ($user->isIspAdmin() && $request->routeIs('isp.settings.*', 'isp.platform-subscription.*')) {
                    return $next($request);
                }

                $message = trim((string) ($maintenance['maintenance_message'] ?? ''))
                    ?: 'This workspace is in maintenance mode. Try again later.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message, 'code' => 'isp_maintenance'], 503);
                }

                return response()->view('isp.maintenance-locked', [
                    'isp' => $isp,
                    'message' => $message,
                ], 503);
            }
        }

        return $next($request);
    }

    protected function isAllowlistedSettingsRoute(Request $request): bool
    {
        return $request->routeIs(
            'isp.settings.maintenance',
            'isp.settings.maintenance.update',
            'isp.settings.security',
            'isp.settings.security.update',
            'isp.platform-subscription.*',
        );
    }

    /**
     * @return list<string>
     */
    protected function parseIpList(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/[\s,;]+/', $raw) ?: [])
            ->map(fn ($ip) => trim((string) $ip))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $list
     */
    protected function ipMatchesList(?string $ip, array $list): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }

        foreach ($list as $entry) {
            if ($entry === $ip) {
                return true;
            }

            if (str_contains($entry, '/') && $this->ipInCidr($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    protected function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = array_pad(explode('/', $cidr, 2), 2, null);
        if ($subnet === null || $mask === null || ! is_numeric($mask)) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = (int) $mask;
        if ($mask < 0 || $mask > 32) {
            return false;
        }

        $maskLong = $mask === 0 ? 0 : (~((1 << (32 - $mask)) - 1) & 0xFFFFFFFF);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
