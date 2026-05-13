<?php

namespace App\View\Composers;

use App\Models\ISP;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class IspBrandingComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isp_id) {
            $view->with('ispBranding', null);

            return;
        }

        $isp = ISP::query()->find($user->isp_id);
        if (! $isp) {
            $view->with('ispBranding', null);

            return;
        }

        $settings = $isp->settings ?? [];
        $b = $settings['branding'] ?? [];

        $logoUrl = null;
        if (! empty($isp->logo) && Storage::disk('public')->exists($isp->logo)) {
            $logoUrl = Storage::disk('public')->url($isp->logo);
        }

        $faviconUrl = null;
        if (! empty($b['favicon_path']) && Storage::disk('public')->exists($b['favicon_path'])) {
            $faviconUrl = Storage::disk('public')->url($b['favicon_path']);
        }

        $accent = $b['accent_hex'] ?? '#6366f1';
        if (! is_string($accent) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) {
            $accent = '#6366f1';
        }

        $view->with('ispBranding', [
            'logo_url' => $logoUrl,
            'favicon_url' => $faviconUrl,
            'workspace_title' => (string) ($b['workspace_title'] ?? config('app.name', 'PrimeBilling')),
            'workspace_tagline' => (string) ($b['workspace_tagline'] ?? ($isp->name ?? '')),
            'accent_hex' => $accent,
            'isp' => $isp,
        ]);
    }
}
