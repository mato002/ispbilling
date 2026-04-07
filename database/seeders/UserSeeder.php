<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ISP;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a sample ISP first (or get existing)
        $isp = ISP::firstOrCreate(
            ['email' => 'admin@techconnect.co.ke'],
            [
                'name' => 'TechConnect ISP',
                'company_name' => 'TechConnect Internet Services Ltd',
                'phone' => '+254712345678',
                'address' => 'Nairobi, Kenya',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'is_active' => true,
            ]
        );

        // Create Super Admin (no ISP association)
        User::firstOrCreate(
            ['email' => 'superadmin@ispbilling.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'isp_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create ISP Admin
        User::firstOrCreate(
            ['email' => 'admin@techconnect.co.ke'],
            [
                'name' => 'ISP Administrator',
                'password' => Hash::make('password'),
                'role' => 'isp_admin',
                'isp_id' => $isp->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create ISP Staff members
        User::firstOrCreate(
            ['email' => 'john.doe@techconnect.co.ke'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'role' => 'isp_staff',
                'isp_id' => $isp->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'jane.smith@techconnect.co.ke'],
            [
                'name' => 'Jane Smith',
                'password' => Hash::make('password'),
                'role' => 'isp_staff',
                'isp_id' => $isp->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create another ISP and its admin
        $isp2 = ISP::firstOrCreate(
            ['email' => 'info@fastnet.co.ke'],
            [
                'name' => 'FastNet ISP',
                'company_name' => 'FastNet Communications',
                'phone' => '+254798765432',
                'address' => 'Mombasa, Kenya',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@fastnet.co.ke'],
            [
                'name' => 'FastNet Admin',
                'password' => Hash::make('password'),
                'role' => 'isp_admin',
                'isp_id' => $isp2->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'mike.johnson@fastnet.co.ke'],
            [
                'name' => 'Mike Johnson',
                'password' => Hash::make('password'),
                'role' => 'isp_staff',
                'isp_id' => $isp2->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}

