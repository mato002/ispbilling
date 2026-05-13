<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformMarketingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $platform = PlatformIntegration::current();
        $messaging = $platform->messaging_channels ?? [];

        return view('admin.platform-marketing.index', compact('messaging'));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'sms_provider' => ['required', 'in:none,twilio,africastalking'],
            'twilio_account_sid' => ['nullable', 'string', 'max:255'],
            'twilio_auth_token' => ['nullable', 'string', 'max:255'],
            'twilio_from' => ['nullable', 'string', 'max:32'],
            'africastalking_username' => ['nullable', 'string', 'max:255'],
            'africastalking_api_key' => ['nullable', 'string', 'max:512'],
            'africastalking_from' => ['nullable', 'string', 'max:32'],
            'firebase_web_api_key' => ['nullable', 'string', 'max:255'],
            'firebase_project_id' => ['nullable', 'string', 'max:255'],
            'firebase_messaging_sender_id' => ['nullable', 'string', 'max:64'],
            'firebase_app_id' => ['nullable', 'string', 'max:255'],
            'firebase_vapid_public_key' => ['nullable', 'string', 'max:512'],
        ]);

        $platform = PlatformIntegration::current();
        $platform->messaging_channels = array_merge($platform->messaging_channels ?? [], $validated);
        $platform->save();

        return redirect()
            ->route('admin.marketing.index')
            ->with('success', 'Messaging & push settings saved (encrypted at rest). Wire these keys into your notification jobs when ready.');
    }
}
