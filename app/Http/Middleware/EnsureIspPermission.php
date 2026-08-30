<?php

namespace App\Http\Middleware;

use App\Support\IspPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIspPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! IspPermissions::userCan($user, $permission)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
