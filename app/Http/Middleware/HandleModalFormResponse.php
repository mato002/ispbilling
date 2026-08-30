<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleModalFormResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->isModalFormRequest($request)) {
            return $response;
        }

        if ($response instanceof RedirectResponse && ! $request->session()->has('errors')) {
            $message = $request->session()->get('success')
                ?? $request->session()->get('status');

            if (is_string($message) && $message !== '') {
                return response()->view('partials.modal-form-success', [
                    'message' => $message,
                    'refresh' => true,
                ]);
            }
        }

        return $response;
    }

    protected function isModalFormRequest(Request $request): bool
    {
        return $request->header('Turbo-Frame') === 'pb-form-modal'
            || $request->boolean('_pb_modal');
    }
}
