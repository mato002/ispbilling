<?php

namespace Database\Seeders;

use App\Models\ISP;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Get the first ISP (you may need to adjust this based on your setup)
        $isp = ISP::first();
        
        if (!$isp) {
            $this->command->warn('No ISP found. Please create an ISP first.');
            return;
        }

        $plans = [
            [
                'name' => 'Starter Package',
                'price' => 50.00,
                'access_type' => 'hotspot',
                'duration_days' => 1,
                'download_speed_kbps' => 2048, // 2 Mbps
                'upload_speed_kbps' => 1024, // 1 Mbps
                'data_limit_mb' => 1024, // 1 GB
                'billing_type' => 'data',
                'description' => 'Perfect for light browsing and social media',
                'is_active' => true,
            ],
            [
                'name' => 'Basic Package',
                'price' => 100.00,
                'access_type' => 'hotspot',
                'duration_days' => 3,
                'download_speed_kbps' => 5120, // 5 Mbps
                'upload_speed_kbps' => 2560, // 2.5 Mbps
                'data_limit_mb' => 3072, // 3 GB
                'billing_type' => 'data',
                'description' => 'Great for regular internet usage',
                'is_active' => true,
            ],
            [
                'name' => 'Standard Package',
                'price' => 200.00,
                'access_type' => 'hotspot',
                'duration_days' => 7,
                'download_speed_kbps' => 10240, // 10 Mbps
                'upload_speed_kbps' => 5120, // 5 Mbps
                'data_limit_mb' => 10240, // 10 GB
                'billing_type' => 'data',
                'description' => 'Ideal for streaming and downloads',
                'is_active' => true,
            ],
            [
                'name' => 'Premium Package',
                'price' => 500.00,
                'access_type' => 'hotspot',
                'duration_days' => 30,
                'download_speed_kbps' => 20480, // 20 Mbps
                'upload_speed_kbps' => 10240, // 10 Mbps
                'data_limit_mb' => 51200, // 50 GB
                'billing_type' => 'data',
                'description' => 'Unlimited entertainment and work',
                'is_active' => true,
            ],
            [
                'name' => 'Business Package',
                'price' => 1000.00,
                'access_type' => 'hotspot',
                'duration_days' => 30,
                'download_speed_kbps' => 51200, // 50 Mbps
                'upload_speed_kbps' => 25600, // 25 Mbps
                'data_limit_mb' => 102400, // 100 GB
                'billing_type' => 'data',
                'description' => 'High-speed connection for business needs',
                'is_active' => true,
            ],
            [
                'name' => 'Ultimate Package',
                'price' => 2000.00,
                'access_type' => 'hotspot',
                'duration_days' => 30,
                'download_speed_kbps' => 102400, // 100 Mbps
                'upload_speed_kbps' => 51200, // 50 Mbps
                'data_limit_mb' => null, // Unlimited
                'billing_type' => 'unlimited',
                'description' => 'Unlimited data with maximum speed',
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create(array_merge($planData, [
                'isp_id' => $isp->id,
            ]));
        }

        $this->command->info('Sample plans created successfully!');
    }
}
