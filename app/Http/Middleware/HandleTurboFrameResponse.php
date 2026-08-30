<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleTurboFrameResponse
{
    public const WORKSPACE_FRAME = 'pb-main';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->isWorkspaceFrameRequest($request)) {
            return $response;
        }

        if ($response instanceof RedirectResponse) {
            $response->setStatusCode(303);
        }

        return $response;
    }

    protected function isWorkspaceFrameRequest(Request $request): bool
    {
        return $request->header('Turbo-Frame') === self::WORKSPACE_FRAME;
    }
}
