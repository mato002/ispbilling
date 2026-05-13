<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\PlatformIntegration;
use App\Models\User;
use App\Support\InvoiceTax;
use App\Support\IspCustomFields;
use App\Support\IspSettingsChangeTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.index', compact('isp'));
    }

    public function company()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.company', compact('isp'));
    }

    public function updateCompany(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:2000',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:64',
            'website' => 'nullable|string|max:500',
            'tax_pin' => 'nullable|string|max:64',
            'registration_number' => 'nullable|string|max:128',
            'business_type' => 'nullable|string|max:64',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,svg|max:1024',
            'remove_logo' => 'nullable|boolean',
            'remove_favicon' => 'nullable|boolean',
            'workspace_title' => 'nullable|string|max:80',
            'workspace_tagline' => 'nullable|string|max:160',
            'accent_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $isp = auth()->user()->isp;
        $settings = $isp->settings ?? [];
        $settings['company_profile'] = array_merge($settings['company_profile'] ?? [], [
            'website' => $request->input('website'),
            'tax_pin' => $request->input('tax_pin'),
            'registration_number' => $request->input('registration_number'),
            'business_type' => $request->input('business_type'),
        ]);

        $branding = $settings['branding'] ?? [];
        $branding['workspace_title'] = $request->input('workspace_title') ?: ($branding['workspace_title'] ?? config('app.name', 'PrimeBilling'));
        $branding['workspace_tagline'] = $request->input('workspace_tagline') ?: ($branding['workspace_tagline'] ?? '');
        if ($request->filled('accent_hex')) {
            $branding['accent_hex'] = $request->input('accent_hex');
        }

        if ($request->boolean('remove_logo') && $isp->logo) {
            Storage::disk('public')->delete($isp->logo);
            $isp->logo = null;
        }

        if ($request->hasFile('logo')) {
            if ($isp->logo) {
                Storage::disk('public')->delete($isp->logo);
            }
            $path = $request->file('logo')->store("isp-branding/{$isp->id}", 'public');
            $isp->logo = $path;
        }

        if ($request->boolean('remove_favicon')) {
            if (! empty($branding['favicon_path'])) {
                Storage::disk('public')->delete($branding['favicon_path']);
            }
            unset($branding['favicon_path']);
        }

        if ($request->hasFile('favicon')) {
            if (! empty($branding['favicon_path'])) {
                Storage::disk('public')->delete($branding['favicon_path']);
            }
            $branding['favicon_path'] = $request->file('favicon')->store("isp-branding/{$isp->id}", 'public');
        }

        $settings['branding'] = $branding;
        $settings['meta'] = array_merge($settings['meta'] ?? [], [
            'last_settings_change_at' => now()->toIso8601String(),
        ]);

        $isp->fill([
            ...$request->only(['company_name', 'email', 'phone', 'address', 'currency', 'timezone']),
            'settings' => $settings,
        ]);
        $isp->save();
        IspSettingsChangeTracker::touch($isp->id);

        return redirect()->route('isp.settings.company')->with('success', 'Company profile and branding saved successfully.');
    }

    public function customFields()
    {
        $isp = auth()->user()->isp;
        $definitions = IspCustomFields::definitionsFor($isp, 'customer');

        return view('isp.settings.custom-fields', compact('isp', 'definitions'));
    }

    public function updateCustomFields(Request $request)
    {
        $isp = auth()->user()->isp;
        $settings = $isp->settings ?? [];

        $request->validate([
            'cf_key' => 'nullable|array',
            'cf_key.*' => 'nullable|string|max:64',
            'cf_label' => 'nullable|array',
            'cf_type' => 'nullable|array',
            'cf_required' => 'nullable|array',
            'cf_rules' => 'nullable|array',
            'cf_options' => 'nullable|array',
        ]);

        $normalized = IspCustomFields::normalizePostedDefinitions($request->all(), 'customer');
        $settings['custom_field_definitions'] = array_merge(
            $settings['custom_field_definitions'] ?? [],
            ['customer' => $normalized]
        );
        $settings['meta'] = array_merge($settings['meta'] ?? [], [
            'last_settings_change_at' => now()->toIso8601String(),
        ]);

        $isp->update(['settings' => $settings]);
        IspSettingsChangeTracker::touch($isp->id);

        return redirect()->route('isp.settings.custom-fields')->with('success', 'Custom field definitions saved. They now apply to customer create/edit forms.');
    }

    public function paymentGateways()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.payment-gateways', compact('isp'));
    }

    public function updatePaymentGateways(Request $request)
    {
        $isp = auth()->user()->isp;
        $platform = PlatformIntegration::current();

        if ($request->boolean('mpesa_enabled') && ! $platform->isPaymentPipeEnabled('mpesa')) {
            return redirect()->route('isp.settings.payment-gateways')
                ->with('error', 'M-Pesa is disabled for this platform. Contact your administrator.');
        }
        if ($request->boolean('selcom_enabled') && ! $platform->isPaymentPipeEnabled('selcom')) {
            return redirect()->route('isp.settings.payment-gateways')
                ->with('error', 'Selcom is disabled for this platform. Contact your administrator.');
        }

        $settings = $isp->settings ?? [];

        $settings['payment_gateways'] = [
            'mpesa' => [
                'enabled' => $request->has('mpesa_enabled'),
                'consumer_key' => $request->input('mpesa_consumer_key'),
                'consumer_secret' => $request->input('mpesa_consumer_secret'),
                'shortcode' => $request->input('mpesa_shortcode'),
                'passkey' => $request->input('mpesa_passkey'),
            ],
            'selcom' => [
                'enabled' => $request->has('selcom_enabled'),
                'api_key' => $request->input('selcom_api_key'),
                'api_secret' => $request->input('selcom_api_secret'),
                'vendor_id' => $request->input('selcom_vendor_id'),
            ],
            'manual' => [
                'enabled' => $request->has('manual_enabled'),
            ],
        ];

        $settings['meta'] = array_merge($settings['meta'] ?? [], [
            'last_settings_change_at' => now()->toIso8601String(),
        ]);
        $isp->update(['settings' => $settings]);

        return redirect()->route('isp.settings.payment-gateways')->with('success', 'Payment gateway settings updated successfully');
    }

    public function notifications()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.notifications', compact('isp'));
    }

    public function updateNotifications(Request $request)
    {
        $isp = auth()->user()->isp;
        $settings = $isp->settings ?? [];
        $settings['notifications'] = [
            'email_notifications' => $request->has('email_notifications'),
            'notification_email' => $request->input('notification_email'),
            'sms_notifications' => $request->has('sms_notifications'),
            'sms_provider' => $request->input('sms_provider'),
            'invoice_reminders' => $request->has('invoice_reminders'),
            'reminder_days' => $request->input('reminder_days', 3),
        ];
        $settings['meta'] = array_merge($settings['meta'] ?? [], [
            'last_settings_change_at' => now()->toIso8601String(),
        ]);
        $isp->update(['settings' => $settings]);

        return redirect()->route('isp.settings.notifications')->with('success', 'Notification preferences updated successfully');
    }

    public function users()
    {
        $isp = auth()->user()->isp;
        $users = User::where('isp_id', $isp->id)->get();

        return view('isp.settings.users', compact('isp', 'users'));
    }

    public function billing()
    {
        $isp = auth()->user()->isp;
        $globalTaxEnforced = InvoiceTax::isGlobalTaxEnforced();
        $platformTaxRate = InvoiceTax::enforcedTaxRatePercent();

        return view('isp.settings.billing', compact('isp', 'globalTaxEnforced', 'platformTaxRate'));
    }

    public function updateBilling(Request $request)
    {
        $isp = auth()->user()->isp;
        $settings = $isp->settings ?? [];
        $settings['billing'] = [
            'invoice_prefix' => $request->input('invoice_prefix', 'INV'),
            'auto_generate_invoices' => $request->has('auto_generate_invoices'),
            'grace_period_days' => $request->input('grace_period_days', 3),
            'penalty_rate' => $request->input('penalty_rate', 5),
            'tax_rate' => $request->input('tax_rate', 0),
        ];
        $settings['meta'] = array_merge($settings['meta'] ?? [], [
            'last_settings_change_at' => now()->toIso8601String(),
        ]);
        $isp->update(['settings' => $settings]);

        return redirect()->route('isp.settings.billing')->with('success', 'Billing settings updated successfully');
    }
}
