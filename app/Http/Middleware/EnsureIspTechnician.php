<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIspTechnician
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isTechnician() || ! $user->isp_id) {
            abort(403, 'Technician access required.');
        }

        if (! $user->is_active) {
            abort(403, 'Your account is inactive.');
        }

        return $next($request);
    }
}
