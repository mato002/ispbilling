<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        try {
            ActivityLogService::logLogin($user);
        } catch (\Throwable $e) {
            // Never block login on audit write failures.
        }

        // Redirect based on user role
        if ($user->isSuperAdmin()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        } elseif ($user->isIspAdmin() || $user->isIspStaff()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            try {
                ActivityLogService::logLogout($user);
            } catch (\Throwable $e) {
                // Never block logout on audit write failures.
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
