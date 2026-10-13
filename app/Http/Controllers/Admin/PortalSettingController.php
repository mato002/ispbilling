<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PortalSettingController extends Controller
{
    public function index(Request $request): View
    {
        $isp = $this->resolveIsp($request);

        if ($isp === null) {
            return view('admin.hotspot.portal-settings-picker', [
                'isps' => ISP::query()
                    ->orderBy('company_name')
                    ->orderBy('name')
                    ->get(['id', 'name', 'company_name']),
            ]);
        }

        $portalSetting = PortalSetting::getOrCreateForISP($isp->id);

        return view('admin.hotspot.portal-settings', compact('portalSetting', 'isp'));
    }

    public function update(Request $request): RedirectResponse
    {
        $isp = $this->resolveIsp($request, required: true);

        $request->validate([
            'welcome_message' => 'required|string|max:255',
            'logo_url' => 'nullable|url',
            'background_type' => 'required|in:gradient,image,color',
            'background_value' => 'required|string',
            'show_packages' => 'boolean',
            'show_payment_button' => 'boolean',
            'payment_button_text' => 'required|string|max:50',
            'mpesa_till_number' => 'nullable|string|max:20',
            'support_phone' => 'nullable|string|max:30',
            'support_whatsapp' => 'nullable|string|max:30',
            'theme_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $portalSetting = PortalSetting::getOrCreateForISP($isp->id);

        $data = $request->except(['logo', 'isp_id']);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $data['logo_url'] = Storage::url($logoPath);
        }

        $portalSetting->update($data);

        return redirect()
            ->route('admin.portal-settings.index', ['isp_id' => $isp->id])
            ->with('success', 'Portal settings updated successfully.');
    }

    private function resolveIsp(Request $request, bool $required = false): ?ISP
    {
        $user = $request->user();

        if ($user?->isp) {
            return $user->isp;
        }

        $ispId = $request->input('isp_id') ?? $request->query('isp_id');

        if ($ispId) {
            return ISP::query()->findOrFail($ispId);
        }

        if ($required) {
            abort(422, 'Select an ISP workspace to manage portal settings.');
        }

        if ($user?->isSuperAdmin()) {
            return null;
        }

        abort(403, 'No ISP workspace linked to your account.');
    }
}
