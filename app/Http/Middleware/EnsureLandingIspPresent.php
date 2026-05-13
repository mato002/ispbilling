<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandingIspPresent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->get('landing_isp')) {
            abort(404);
        }

        return $next($request);
    }
}
