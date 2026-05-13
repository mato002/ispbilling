<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform admin UI (/admin/*) is only for PrimeBilling super administrators.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            if ($user?->isp_id) {
                return redirect()
                    ->route('isp.dashboard')
                    ->with('warning', 'The admin console is for platform staff only. You were redirected to your ISP workspace.');
            }

            return redirect()
                ->route('dashboard')
                ->with('warning', 'The admin console is for platform staff only.');
        }

        return $next($request);
    }
}
