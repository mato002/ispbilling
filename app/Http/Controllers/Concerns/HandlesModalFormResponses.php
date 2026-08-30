<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait HandlesModalFormResponses
{
    protected const WORKSPACE_FRAME = 'pb-main';

    protected function isModalFormRequest(?Request $request = null): bool
    {
        $request ??= request();

        return $request->header('Turbo-Frame') === 'pb-form-modal'
            || $request->boolean('_pb_modal');
    }

    protected function isWorkspaceFrameRequest(?Request $request = null): bool
    {
        $request ??= request();

        return $request->header('Turbo-Frame') === self::WORKSPACE_FRAME;
    }

    protected function modalOrRedirect(
        string $message,
        RedirectResponse $redirect,
        bool $refreshWorkspace = true,
    ): RedirectResponse|Response {
        if (! $this->isModalFormRequest()) {
            return $redirect->with('success', $message);
        }

        return response()->view('partials.modal-form-success', [
            'message' => $message,
            'refresh' => $refreshWorkspace,
        ]);
    }
}
