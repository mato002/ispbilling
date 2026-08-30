<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts ISP console routes to administrators and staff operators.
 * Technicians use the dedicated field workflow under isp/technician/*.
 */
class EnsureIspWorkspaceOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->isTechnician()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Technicians may only access the field inventory workflow.',
                    'redirect' => route('isp.technician.inventory.index'),
                ], 403);
            }

            return redirect()
                ->route('isp.technician.inventory.index')
                ->with('error', 'Your account is limited to the technician field workflow.');
        }

        return $next($request);
    }
}
