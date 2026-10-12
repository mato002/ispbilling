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

        if (! $user) {
            return $next($request);
        }

        // Super admins use the platform console. Letting them continue into a
        // tenant route leaves the ISP context empty and can trigger invalid
        // tenant-specific queries (for example, the connection statistics API).
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
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
