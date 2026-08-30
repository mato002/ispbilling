<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\PlatformIntegration;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\InvoiceTax;
use App\Support\IspCustomFields;
use App\Support\IspPermissions;
use App\Support\IspSettingsChangeTracker;
use App\Support\MpesaGatewayConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'portal_title' => 'nullable|string|max:120',
            'portal_welcome_message' => 'nullable|string|max:500',
            'portal_support_email' => 'nullable|email|max:255',
            'invoice_footer' => 'nullable|string|max:1000',
            'portal_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'remove_portal_logo' => 'nullable|boolean',
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
        $branding['portal_title'] = $request->input('portal_title') ?: ($branding['portal_title'] ?? $isp->company_name);
        $branding['portal_welcome_message'] = $request->input('portal_welcome_message') ?: '';
        $branding['portal_support_email'] = $request->input('portal_support_email') ?: ($isp->email ?? '');
        $branding['invoice_footer'] = $request->input('invoice_footer') ?: '';

        if ($request->boolean('remove_portal_logo')) {
            if (! empty($branding['portal_logo_path'])) {
                Storage::disk('public')->delete($branding['portal_logo_path']);
            }
            unset($branding['portal_logo_path']);
        }

        if ($request->hasFile('portal_logo')) {
            if (! empty($branding['portal_logo_path'])) {
                Storage::disk('public')->delete($branding['portal_logo_path']);
            }
            $branding['portal_logo_path'] = $request->file('portal_logo')->store("isp-branding/{$isp->id}", 'public');
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

        try {
            ActivityLogService::logAction('settings_update', 'Updated company profile and branding', [
                'isp_id' => $isp->id,
            ]);
        } catch (\Throwable $e) {
            // Best-effort audit.
        }

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

        $paystackMode = $request->input('paystack_mode', 'test');
        if (! in_array($paystackMode, ['test', 'live'], true)) {
            $paystackMode = 'test';
        }

        $settings['payment_gateways'] = [
            'paystack' => [
                'enabled' => $request->has('paystack_enabled'),
                'mode' => $paystackMode,
                'public_key' => $request->input('paystack_public_key'),
                'secret_key' => $request->input('paystack_secret_key'),
            ],
            MpesaGatewayConfig::SETTINGS_KEY => MpesaGatewayConfig::formatForSettings([
                'consumer_key' => $request->input('mpesa_consumer_key'),
                'consumer_secret' => $request->input('mpesa_consumer_secret'),
                'shortcode' => $request->input('mpesa_shortcode'),
                'passkey' => $request->input('mpesa_passkey'),
            ], $request->has('mpesa_enabled')),
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
        unset($settings['payment_gateways']['daraja']);

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

    public function topUpSmsWallet(Request $request, \App\Services\PlatformSmsWalletService $smsWallet): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:10'],
        ]);

        $digits = preg_replace('/\D/', '', $validated['phone']);
        if (str_starts_with($digits, '0')) {
            $digits = '254'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '254')) {
            $digits = '254'.$digits;
        }

        if (strlen($digits) !== 12) {
            return back()
                ->withInput()
                ->withErrors(['phone' => 'Enter a valid Kenyan mobile number after +254.']);
        }

        $isp = auth()->user()->isp;
        $result = $smsWallet->initiateTopUp($isp, $digits, (float) $validated['amount']);

        return redirect()
            ->route('isp.settings.notifications')
            ->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
    }

    public function users()
    {
        $isp = auth()->user()->isp;
        $users = User::where('isp_id', $isp->id)->orderBy('name')->get();
        $canManageUsers = auth()->user()->isIspAdmin();

        return view('isp.settings.users', compact('isp', 'users', 'canManageUsers'));
    }

    public function roles()
    {
        $isp = auth()->user()->isp;
        if (! auth()->user()->isIspAdmin()) {
            abort(403, 'Only ISP administrators can configure role permissions.');
        }

        $staffMatrix = IspPermissions::matrixForRole($isp, 'isp_staff');
        $permissionGroups = IspPermissions::groups();

        return view('isp.settings.roles', compact('isp', 'staffMatrix', 'permissionGroups'));
    }

    public function updateRoles(Request $request)
    {
        $isp = auth()->user()->isp;
        if (! auth()->user()->isIspAdmin()) {
            abort(403, 'Only ISP administrators can configure role permissions.');
        }

        $posted = $request->input('permissions', []);
        if (! is_array($posted)) {
            $posted = [];
        }

        IspPermissions::saveStaffMatrix($isp, $posted);

        return redirect()->route('isp.settings.roles')->with('success', 'Role permissions saved for staff operators.');
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

    public function security()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.security', compact('isp'));
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $request->validate([
            'session_timeout_minutes' => 'nullable|integer|min:15|max:1440',
            'ip_allowlist' => 'nullable|string|max:4000',
        ]);

        $isp = auth()->user()->isp;
        $this->persistSettings($isp, function (array &$settings) use ($request) {
            $settings['security'] = [
                'require_strong_passwords' => $request->has('require_strong_passwords'),
                'session_timeout_minutes' => (int) $request->input('session_timeout_minutes', 120),
                'login_alerts' => $request->has('login_alerts'),
                'force_logout_on_password_change' => $request->has('force_logout_on_password_change'),
                'ip_allowlist' => $request->input('ip_allowlist'),
            ];
        });

        return redirect()->route('isp.settings.security')->with('success', 'Security preferences saved.');
    }

    public function branches()
    {
        $isp = auth()->user()->isp;
        $branches = $isp->settings['branches'] ?? [];

        return view('isp.settings.branches', compact('isp', 'branches'));
    }

    public function updateBranches(Request $request): RedirectResponse
    {
        $isp = auth()->user()->isp;
        $normalized = $this->normalizeBranches($request);

        $this->persistSettings($isp, function (array &$settings) use ($normalized) {
            $settings['branches'] = $normalized;
        });

        return redirect()->route('isp.settings.branches')->with('success', 'Branch footprint updated.');
    }

    public function routerDefaults()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.router-defaults', compact('isp'));
    }

    public function updateRouterDefaults(Request $request): RedirectResponse
    {
        $request->validate([
            'default_api_port' => 'nullable|integer|min:1|max:65535',
            'default_poll_interval_seconds' => 'nullable|integer|min:30|max:3600',
            'default_connection_timeout' => 'nullable|integer|min:3|max:120',
            'lifecycle_default' => 'nullable|in:live,stock,maintenance,retired',
        ]);

        $isp = auth()->user()->isp;
        $this->persistSettings($isp, function (array &$settings) use ($request) {
            $network = $settings['network'] ?? [];
            $network['router_defaults'] = [
                'default_api_port' => (int) $request->input('default_api_port', 8728),
                'use_ssl_by_default' => $request->has('use_ssl_by_default'),
                'default_poll_interval_seconds' => (int) $request->input('default_poll_interval_seconds', 60),
                'auto_sync_on_provision' => $request->has('auto_sync_on_provision'),
                'default_connection_timeout' => (int) $request->input('default_connection_timeout', 10),
                'lifecycle_default' => $request->input('lifecycle_default', 'live'),
            ];
            $settings['network'] = $network;

            $sms = $settings['sms'] ?? [];
            $sms['automation'] = [
                'hotspot_vouchers' => $request->boolean('sms_automation_hotspot_vouchers'),
                'pppoe_invoice_reminders' => $request->boolean('sms_automation_pppoe_invoice_reminders'),
                'disconnect_alerts' => $request->boolean('sms_automation_disconnect_alerts'),
            ];
            $settings['sms'] = $sms;
        });

        return redirect()->route('isp.settings.router-defaults')->with('success', 'Router and messaging defaults saved.');
    }

    public function pppoeDefaults()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.pppoe-defaults', compact('isp'));
    }

    public function updatePppoeDefaults(Request $request): RedirectResponse
    {
        $request->validate([
            'max_sessions_per_user' => 'nullable|integer|min:1|max:50',
            'idle_timeout_minutes' => 'nullable|integer|min:0|max:10080',
        ]);

        $isp = auth()->user()->isp;
        $this->persistSettings($isp, function (array &$settings) use ($request) {
            $network = $settings['network'] ?? [];
            $network['pppoe_defaults'] = [
                'default_profile_name' => $request->input('default_profile_name'),
                'local_address_pool' => $request->input('local_address_pool'),
                'remote_address_pool' => $request->input('remote_address_pool'),
                'service_name' => $request->input('service_name'),
                'max_sessions_per_user' => (int) $request->input('max_sessions_per_user', 1),
                'use_radius' => $request->has('use_radius'),
                'idle_timeout_minutes' => (int) $request->input('idle_timeout_minutes', 0),
                'disconnect_on_suspend' => $request->has('disconnect_on_suspend'),
            ];
            $settings['network'] = $network;
        });

        return redirect()->route('isp.settings.pppoe-defaults')->with('success', 'PPPoE defaults saved.');
    }

    public function hotspotSettings()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.hotspot-settings', compact('isp'));
    }

    public function updateHotspotSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'default_login_by' => 'nullable|in:mac,cookie,http-chap,http-pap',
            'default_session_timeout' => 'nullable|string|max:32',
            'trial_duration_minutes' => 'nullable|integer|min:0|max:10080',
            'redirect_url' => 'nullable|url|max:500',
        ]);

        $isp = auth()->user()->isp;
        $this->persistSettings($isp, function (array &$settings) use ($request) {
            $network = $settings['network'] ?? [];
            $network['hotspot'] = [
                'default_login_by' => $request->input('default_login_by', 'mac'),
                'default_session_timeout' => $request->input('default_session_timeout', '1h'),
                'use_radius' => $request->has('use_radius'),
                'walled_garden_enabled' => $request->has('walled_garden_enabled'),
                'redirect_url' => $request->input('redirect_url'),
                'trial_enabled' => $request->has('trial_enabled'),
                'trial_duration_minutes' => (int) $request->input('trial_duration_minutes', 30),
                'shared_users' => $request->has('shared_users'),
            ];
            $settings['network'] = $network;

            $sms = $settings['sms'] ?? [];
            $sms['automation'] = [
                'hotspot_vouchers' => $request->boolean('sms_automation_hotspot_vouchers'),
                'pppoe_invoice_reminders' => $request->boolean('sms_automation_pppoe_invoice_reminders'),
                'disconnect_alerts' => $request->boolean('sms_automation_disconnect_alerts'),
            ];
            $settings['sms'] = $sms;
        });

        return redirect()->route('isp.settings.hotspot')->with('success', 'Hotspot and messaging settings saved.');
    }

    public function backups()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.backups', compact('isp'));
    }

    public function updateBackups(Request $request): RedirectResponse
    {
        $request->validate([
            'retention_days' => 'nullable|integer|min:1|max:365',
            'backup_frequency' => 'nullable|in:daily,weekly,monthly',
        ]);

        $isp = auth()->user()->isp;
        $this->persistSettings($isp, function (array &$settings) use ($request) {
            $settings['backups'] = [
                'auto_backup_enabled' => $request->has('auto_backup_enabled'),
                'backup_frequency' => $request->input('backup_frequency', 'weekly'),
                'retention_days' => (int) $request->input('retention_days', 30),
                'include_customer_exports' => $request->has('include_customer_exports'),
                'notify_on_backup' => $request->has('notify_on_backup'),
            ];
        });

        return redirect()->route('isp.settings.backups')->with('success', 'Backup preferences saved.');
    }

    public function exportBackup(): StreamedResponse
    {
        $isp = auth()->user()->isp;
        $isp->loadCount(['customers', 'routers', 'plans', 'users', 'invoices', 'payments']);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'isp' => [
                'id' => $isp->id,
                'name' => $isp->name,
                'company_name' => $isp->company_name,
                'email' => $isp->email,
                'counts' => [
                    'customers' => $isp->customers_count,
                    'routers' => $isp->routers_count,
                    'plans' => $isp->plans_count,
                    'users' => $isp->users_count,
                    'invoices' => $isp->invoices_count,
                    'payments' => $isp->payments_count,
                ],
            ],
            'settings' => $isp->settings,
        ];

        $filename = 'workspace-backup-'.$isp->id.'-'.now()->format('Ymd-His').'.json';

        $this->persistSettings($isp, function (array &$settings) {
            $settings['backups'] = array_merge($settings['backups'] ?? [], [
                'last_backup_at' => now()->toIso8601String(),
            ]);
        });

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function maintenance()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.maintenance', compact('isp'));
    }

    public function updateMaintenance(Request $request): RedirectResponse
    {
        $request->validate([
            'maintenance_message' => 'nullable|string|max:2000',
            'maintenance_allow_ip' => 'nullable|string|max:500',
            'scheduled_maintenance_start' => 'nullable|date',
            'scheduled_maintenance_end' => 'nullable|date|after_or_equal:scheduled_maintenance_start',
        ]);

        $isp = auth()->user()->isp;
        $this->persistSettings($isp, function (array &$settings) use ($request) {
            $settings['maintenance'] = [
                'maintenance_mode_enabled' => $request->has('maintenance_mode_enabled'),
                'maintenance_message' => $request->input('maintenance_message'),
                'maintenance_allow_ip' => $request->input('maintenance_allow_ip'),
                'scheduled_maintenance_start' => $request->input('scheduled_maintenance_start'),
                'scheduled_maintenance_end' => $request->input('scheduled_maintenance_end'),
                'pause_new_signups' => $request->has('pause_new_signups'),
            ];
        });

        return redirect()->route('isp.settings.maintenance')->with('success', 'Maintenance settings saved.');
    }

    public function apiIntegrations()
    {
        $isp = auth()->user()->isp;

        return view('isp.settings.api-integrations', compact('isp'));
    }

    public function updateApiIntegrations(Request $request): RedirectResponse
    {
        $request->validate([
            'webhook_url' => 'nullable|url|max:500',
            'rate_limit_per_minute' => 'nullable|integer|min:10|max:1000',
            'allowed_origins' => 'nullable|string|max:4000',
        ]);

        $isp = auth()->user()->isp;
        $this->persistSettings($isp, function (array &$settings) use ($request) {
            $existing = $settings['api'] ?? [];
            $webhookSecret = $request->input('webhook_secret');
            $settings['api'] = [
                'api_enabled' => $request->has('api_enabled'),
                'webhook_url' => $request->input('webhook_url'),
                'webhook_secret' => filled($webhookSecret) ? $webhookSecret : ($existing['webhook_secret'] ?? null),
                'allowed_origins' => $request->input('allowed_origins'),
                'rate_limit_per_minute' => (int) $request->input('rate_limit_per_minute', 120),
                'expose_customer_api' => $request->has('expose_customer_api'),
            ];
        });

        return redirect()->route('isp.settings.api-integrations')->with('success', 'API integration settings saved.');
    }

    /**
     * @param  callable(array&): void  $mutator
     */
    private function persistSettings(ISP $isp, callable $mutator): void
    {
        $settings = $isp->settings ?? [];
        $mutator($settings);
        $settings['meta'] = array_merge($settings['meta'] ?? [], [
            'last_settings_change_at' => now()->toIso8601String(),
        ]);
        $isp->update(['settings' => $settings]);
        IspSettingsChangeTracker::touch($isp->id);

        try {
            ActivityLogService::logAction(
                'settings_update',
                'Updated ISP settings',
                ['isp_id' => $isp->id, 'route' => optional(request()->route())->getName()]
            );
        } catch (\Throwable $e) {
            // Best-effort audit.
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeBranches(Request $request): array
    {
        $names = $request->input('branch_name', []);
        if (! is_array($names)) {
            return [];
        }

        $codes = $request->input('branch_code', []);
        $locations = $request->input('branch_location', []);
        $phones = $request->input('branch_phone', []);
        $active = array_map('intval', (array) $request->input('branch_active', []));

        $branches = [];
        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $branches[] = [
                'name' => $name,
                'code' => trim((string) ($codes[$index] ?? '')),
                'location' => trim((string) ($locations[$index] ?? '')),
                'phone' => trim((string) ($phones[$index] ?? '')),
                'is_active' => in_array($index, $active, true),
            ];
        }

        return $branches;
    }
}
