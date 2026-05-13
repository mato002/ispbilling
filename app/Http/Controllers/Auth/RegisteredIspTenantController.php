<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredIspTenantController extends Controller
{
    public function create(): View
    {
        return view('auth.register-isp');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'isp_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, 'unique:i_s_p_s,email'],
            'phone' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $base = Str::slug($validated['isp_name']);
            $slug = $base !== '' ? $base : 'isp';
            $suffix = 0;
            while (ISP::query()->where('slug', $slug)->exists()) {
                $suffix++;
                $slug = $base.'-'.$suffix;
            }

            $isp = ISP::create([
                'name' => $validated['isp_name'],
                'slug' => $slug,
                'company_name' => $validated['company_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'is_active' => true,
                'saas_subscription_status' => 'none',
            ]);

            return User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'isp_id' => $isp->id,
                'role' => 'isp_admin',
                'is_active' => true,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('isp.saas-onboarding.show');
    }
}
