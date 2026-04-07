<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalSetting;
use App\Models\ISP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortalSettingController extends Controller
{
    public function index()
    {
        $isp = auth()->user()->isp;
        $portalSetting = PortalSetting::getOrCreateForISP($isp->id);
        
        return view('admin.hotspot.portal-settings', compact('portalSetting', 'isp'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'welcome_message' => 'required|string|max:255',
            'logo_url' => 'nullable|url',
            'background_type' => 'required|in:gradient,image,color',
            'background_value' => 'required|string',
            'show_packages' => 'boolean',
            'show_payment_button' => 'boolean',
            'payment_button_text' => 'required|string|max:50',
            'theme_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $isp = auth()->user()->isp;
        $portalSetting = PortalSetting::getOrCreateForISP($isp->id);

        $data = $request->except(['logo']);

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoPath = $logo->store('logos', 'public');
            $data['logo_url'] = Storage::url($logoPath);
        }

        $portalSetting->update($data);

        return redirect()->route('admin.portal-settings.index')
            ->with('success', 'Portal settings updated successfully.');
    }
}
