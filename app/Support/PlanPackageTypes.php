<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PlanPackageTypes
{
    public const HOTSPOT = 'hotspot';

    public const PPPOE = 'pppoe';

    public const DATA_BUNDLE = 'data_bundle';

    public const UNLIMITED = 'unlimited';

    public const STATIC_IP_ADDON = 'static_ip_addon';

    public const INSTALLATION_SERVICE = 'installation_service';

    public const ADDON_SERVICE = 'addon_service';

    public const VALIDITY_DAILY = 'daily';

    public const VALIDITY_WEEKLY = 'weekly';

    public const VALIDITY_MONTHLY = 'monthly';

    public const VALIDITY_CUSTOM = 'custom';

    public const INSTALLATION_NEW = 'new_installation';

    public const INSTALLATION_ROUTER_SETUP = 'router_setup';

    public const INSTALLATION_ROUTER_CONFIG = 'router_configuration';

    public const INSTALLATION_SITE_VISIT = 'site_visit_fee';

    public const ADDON_BANDWIDTH = 'additional_bandwidth';

    public const ADDON_EXTRA_DEVICES = 'extra_device_connections';

    public const ADDON_PRIORITY_SUPPORT = 'priority_support';

    public const ADDON_STATIC_IP_UPGRADE = 'static_ip_upgrade';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::HOTSPOT => 'Hotspot plan',
            self::PPPOE => 'PPPoE plan',
            self::DATA_BUNDLE => 'Data bundle',
            self::UNLIMITED => 'Unlimited plan',
            self::STATIC_IP_ADDON => 'Static IP add-on',
            self::INSTALLATION_SERVICE => 'Installation service',
            self::ADDON_SERVICE => 'Add-on service',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function installationServiceTypes(): array
    {
        return [
            self::INSTALLATION_NEW => 'New installation',
            self::INSTALLATION_ROUTER_SETUP => 'Router setup',
            self::INSTALLATION_ROUTER_CONFIG => 'Router configuration',
            self::INSTALLATION_SITE_VISIT => 'Site visit fee',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function addonServiceTypes(): array
    {
        return [
            self::ADDON_BANDWIDTH => 'Additional bandwidth',
            self::ADDON_EXTRA_DEVICES => 'Extra device connections',
            self::ADDON_PRIORITY_SUPPORT => 'Priority support',
            self::ADDON_STATIC_IP_UPGRADE => 'Static IP upgrade',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function validityPeriods(): array
    {
        return [
            self::VALIDITY_DAILY => 'Daily',
            self::VALIDITY_WEEKLY => 'Weekly',
            self::VALIDITY_MONTHLY => 'Monthly',
            self::VALIDITY_CUSTOM => 'Custom (days)',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @return list<string>
     */
    public static function connectionPackages(): array
    {
        return [
            self::HOTSPOT,
            self::PPPOE,
            self::DATA_BUNDLE,
            self::UNLIMITED,
        ];
    }

    public static function label(?string $type): string
    {
        return self::labels()[$type] ?? ucfirst(str_replace('_', ' ', (string) $type));
    }

    public static function durationDaysForValidity(?string $period, ?int $customDays): int
    {
        return match ($period) {
            self::VALIDITY_DAILY => 1,
            self::VALIDITY_WEEKLY => 7,
            self::VALIDITY_MONTHLY => 30,
            default => max(1, (int) ($customDays ?? 30)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(Request $request): array
    {
        $packageType = $request->input('package_type', self::PPPOE);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'package_type' => ['required', Rule::in(self::all())],
            'price' => ['required', 'numeric', 'min:0'],
            'speed_profile' => ['nullable', 'string', 'max:255'],
            'upload_speed_kbps' => ['nullable', 'integer', 'min:0'],
            'download_speed_kbps' => ['nullable', 'integer', 'min:0'],
            'device_limit' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if (in_array($packageType, [self::HOTSPOT, self::PPPOE, self::DATA_BUNDLE, self::UNLIMITED], true)) {
            $rules['access_type'] = ['required', Rule::in([self::HOTSPOT, self::PPPOE])];
            $rules['billing_type'] = ['nullable', Rule::in(['time', 'data', 'unlimited'])];
            $rules['validity_period'] = ['nullable', Rule::in(array_keys(self::validityPeriods()))];
            $rules['duration_days'] = ['nullable', 'integer', 'min:1'];
            $rules['data_limit_mb'] = ['nullable', 'integer', 'min:1'];
            $rules['time_limit_hours'] = ['nullable', 'integer', 'min:0'];
            $rules['time_limit_minutes'] = ['nullable', 'integer', 'min:1', 'max:10080'];
        }

        if ($packageType === self::DATA_BUNDLE) {
            $rules['data_limit_mb'] = ['required', 'integer', 'min:1'];
        }

        if ($packageType === self::UNLIMITED) {
            $rules['download_speed_kbps'] = ['nullable', 'integer', 'min:1'];
            $rules['upload_speed_kbps'] = ['nullable', 'integer', 'min:1'];
        }

        if ($packageType === self::STATIC_IP_ADDON) {
            $rules['requires_pppoe_plan'] = ['nullable', 'boolean'];
            $rules['is_recurring'] = ['nullable', 'boolean'];
            $rules['duration_days'] = ['nullable', 'integer', 'min:1'];
        }

        if ($packageType === self::INSTALLATION_SERVICE) {
            $rules['service_type'] = ['required', Rule::in(array_keys(self::installationServiceTypes()))];
            $rules['is_one_time'] = ['nullable', 'boolean'];
        }

        if ($packageType === self::ADDON_SERVICE) {
            $rules['service_type'] = ['required', Rule::in(array_keys(self::addonServiceTypes()))];
            $rules['is_recurring'] = ['nullable', 'boolean'];
            $rules['duration_days'] = ['nullable', 'integer', 'min:1'];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function normalizePayload(array $validated): array
    {
        $packageType = $validated['package_type'] ?? self::PPPOE;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        if (in_array($packageType, [self::HOTSPOT, self::PPPOE], true)) {
            $validated['access_type'] = $packageType;
            $validated['billing_type'] = $validated['billing_type'] ?? 'unlimited';
            $validated['is_one_time'] = false;
            $validated['is_recurring'] = true;
            $validated['service_type'] = null;
            $validated['requires_pppoe_plan'] = false;
            $period = $validated['validity_period'] ?? self::VALIDITY_MONTHLY;
            $validated['duration_days'] = self::durationDaysForValidity($period, $validated['duration_days'] ?? null);
        }

        if ($packageType === self::DATA_BUNDLE) {
            $validated['access_type'] = $validated['access_type'] ?? self::PPPOE;
            $validated['billing_type'] = 'data';
            $validated['is_one_time'] = false;
            $validated['is_recurring'] = true;
            $validated['service_type'] = null;
            $validated['requires_pppoe_plan'] = false;
            $period = $validated['validity_period'] ?? self::VALIDITY_MONTHLY;
            $validated['duration_days'] = self::durationDaysForValidity($period, $validated['duration_days'] ?? null);
        }

        if ($packageType === self::UNLIMITED) {
            $validated['access_type'] = $validated['access_type'] ?? self::PPPOE;
            $validated['billing_type'] = 'unlimited';
            $validated['data_limit_mb'] = null;
            $validated['time_limit_hours'] = null;
            $validated['time_limit_minutes'] = null;
            $validated['is_one_time'] = false;
            $validated['is_recurring'] = true;
            $validated['service_type'] = null;
            $validated['requires_pppoe_plan'] = false;
            $period = $validated['validity_period'] ?? self::VALIDITY_MONTHLY;
            $validated['duration_days'] = self::durationDaysForValidity($period, $validated['duration_days'] ?? null);
        }

        if ($packageType === self::STATIC_IP_ADDON) {
            $validated['access_type'] = self::PPPOE;
            $validated['billing_type'] = 'unlimited';
            $validated['requires_pppoe_plan'] = (bool) ($validated['requires_pppoe_plan'] ?? true);
            $validated['is_one_time'] = false;
            $validated['is_recurring'] = (bool) ($validated['is_recurring'] ?? true);
            $validated['duration_days'] = max(1, (int) ($validated['duration_days'] ?? 30));
            $validated['service_type'] = self::ADDON_STATIC_IP_UPGRADE;
        }

        if ($packageType === self::INSTALLATION_SERVICE) {
            $validated['access_type'] = self::PPPOE;
            $validated['billing_type'] = 'unlimited';
            $validated['is_one_time'] = true;
            $validated['is_recurring'] = false;
            $validated['requires_pppoe_plan'] = false;
            $validated['duration_days'] = 1;
            $validated['data_limit_mb'] = null;
            $validated['time_limit_hours'] = null;
        }

        if ($packageType === self::ADDON_SERVICE) {
            $validated['access_type'] = self::PPPOE;
            $validated['billing_type'] = 'unlimited';
            $validated['is_one_time'] = ! (bool) ($validated['is_recurring'] ?? true);
            $validated['requires_pppoe_plan'] = false;
            $validated['duration_days'] = max(1, (int) ($validated['duration_days'] ?? 30));
        }

        unset($validated['validity_period']);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    public static function subscriptionDefaultsForPlan(\App\Models\Plan $plan): array
    {
        $defaults = [
            'data_used_mb' => 0,
            'data_exhausted_at' => null,
            'static_ip' => null,
            'static_ip_active' => false,
            'service_status' => null,
            'parent_subscription_id' => null,
        ];

        if ($plan->isInstallationService()) {
            $defaults['service_status'] = 'pending';
            $defaults['auto_renew'] = false;
        }

        if ($plan->isAddonService() || $plan->isStaticIpAddon()) {
            $defaults['auto_renew'] = $plan->is_recurring;
        }

        return $defaults;
    }
}
