<?php

namespace Database\Seeders;

use App\Models\ISP;
use App\Models\Plan;
use App\Models\PortalSetting;
use App\Support\PlanPackageTypes;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // This seeder supplies the demo ISP's public hotspot catalogue.
        $isp = ISP::query()->first();

        if (! $isp) {
            $this->command?->warn('No ISP found. Create an ISP before seeding hotspot plans.');

            return;
        }

        $portal = PortalSetting::query()->firstOrCreate(
            ['isp_id' => $isp->id],
            [
                'welcome_message' => 'Welcome to WiFi',
                'background_type' => 'gradient',
                'background_value' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'show_packages' => true,
                'show_payment_button' => true,
                'payment_button_text' => 'Buy Now',
                'theme_color' => '#3B82F6',
                'mpesa_till_number' => '5234330',
                'support_phone' => '0728883160',
                'support_whatsapp' => '0728883160',
            ]
        );
        $portal->fill(array_filter([
            'mpesa_till_number' => $portal->mpesa_till_number ?: '5234330',
            'support_phone' => $portal->support_phone ?: '0728883160',
            'support_whatsapp' => $portal->support_whatsapp ?: '0728883160',
        ]))->save();

        $legacyDemoNames = [
            'Starter Package', 'Basic Package', 'Standard Package',
            'Premium Package', 'Business Package', 'Ultimate Package',
        ];

        // Hide the exact sample plans from the previous demo seeder. Keep their
        // rows for invoices/subscriptions that may already reference them.
        Plan::query()->where('isp_id', $isp->id)
            ->whereIn('name', $legacyDemoNames)
            ->update(['is_active' => false, 'customer_visible' => false]);

        $tiers = [
            [
                'name' => 'Quick Pass', 'price' => 5, 'minutes' => 30,
                'mb' => 300, 'speed' => 3072,
                'description' => '30 minutes of hotspot access, up to 300 MB.',
                'popular' => false, 'best' => false,
            ],
            [
                'name' => 'Express Pass', 'price' => 10, 'minutes' => 60,
                'mb' => 700, 'speed' => 5120,
                'description' => 'One hour of hotspot access, up to 700 MB.',
                'popular' => true, 'best' => false,
            ],
            [
                'name' => 'Standard Pass', 'price' => 20, 'minutes' => 180,
                'mb' => 1536, 'speed' => 5120,
                'description' => 'Three hours of hotspot access, up to 1.5 GB.',
                'popular' => false, 'best' => false,
            ],
            [
                'name' => 'Daily Pass', 'price' => 50, 'minutes' => null,
                'days' => 1, 'mb' => 4096, 'speed' => 5120,
                'description' => '24 hours of hotspot access, up to 4 GB.',
                'popular' => false, 'best' => false,
            ],
            [
                'name' => 'Weekly Pass', 'price' => 250, 'minutes' => null,
                'days' => 7, 'mb' => 20480, 'speed' => 7168,
                'description' => 'Seven days of hotspot access, up to 20 GB.',
                'popular' => false, 'best' => true,
            ],
            [
                'name' => 'Monthly Pass', 'price' => 1000, 'minutes' => null,
                'days' => 30, 'mb' => 102400, 'speed' => 10240,
                'description' => 'Thirty days of hotspot access, up to 100 GB.',
                'popular' => false, 'best' => false,
            ],
        ];

        foreach ($tiers as $tier) {
            Plan::query()->updateOrCreate(
                ['isp_id' => $isp->id, 'name' => $tier['name']],
                [
                    'description' => $tier['description'],
                    'package_type' => PlanPackageTypes::HOTSPOT,
                    'access_type' => PlanPackageTypes::HOTSPOT,
                    'price' => $tier['price'],
                    'duration_days' => $tier['days'] ?? 1,
                    'time_limit_hours' => null,
                    'time_limit_minutes' => $tier['minutes'],
                    'download_speed_kbps' => $tier['speed'],
                    'upload_speed_kbps' => $tier['speed'],
                    'data_limit_mb' => $tier['mb'],
                    'billing_type' => 'data',
                    'is_active' => true,
                    'customer_visible' => true,
                    'is_popular' => $tier['popular'],
                    'is_best_value' => $tier['best'],
                    'is_one_time' => true,
                    'is_recurring' => false,
                    'archived_at' => null,
                ]
            );
        }

        $this->command?->info('Six hotspot tiers are ready for the first ISP.');
    }
}
