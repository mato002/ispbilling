<?php

namespace App\Http\Middleware;

use App\Models\ISP;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveLandingIspFromSubdomain
{
    /**
     * Resolve tenant ISP from host ({slug}.{central_domain}) and attach to the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('landing_isp', null);

        $central = strtolower((string) config('app.central_domain', 'localhost'));

        if (in_array($central, ['localhost', '127.0.0.1', '::1'], true)) {
            return $next($request);
        }

        $host = strtolower($request->getHost());

        if ($host === $central || $host === 'www.'.$central) {
            return $next($request);
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $next($request);
        }

        $centralParts = explode('.', $central);
        $n = count($centralParts);
        $hostParts = explode('.', $host);

        if (count($hostParts) <= $n) {
            return $next($request);
        }

        $tail = implode('.', array_slice($hostParts, -$n));
        if ($tail !== $central) {
            return $next($request);
        }

        $subdomain = $hostParts[0];
        if (in_array($subdomain, ['www', 'mail', 'api', 'admin'], true)) {
            return $next($request);
        }

        $isp = ISP::query()->where('slug', $subdomain)->first();

        if (! $isp) {
            abort(404, 'This ISP portal could not be found.');
        }

        if (! $isp->is_active) {
            abort(403, 'This ISP portal is currently unavailable.');
        }

        $request->attributes->set('landing_isp', $isp);

        return $next($request);
    }
}
