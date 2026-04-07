<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\ISP;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $isps = ISP::all();

        if ($isps->isEmpty()) {
            $this->command->warn('No ISPs found. Please seed ISPs first.');
            return;
        }

        foreach ($isps as $isp) {
            // Payment notifications
            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'Payment Received',
                'message' => 'Payment of KES 2,500.00 received from customer John Doe for invoice INV-2024-000001',
                'type' => 'payment',
                'is_read' => false,
                'created_at' => now()->subHours(2),
            ]);

            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'Payment Failed',
                'message' => 'Payment attempt failed for customer Jane Smith. Transaction ID: PAY-ABC123XYZ',
                'type' => 'error',
                'is_read' => false,
                'created_at' => now()->subHours(5),
            ]);

            // Subscription notifications
            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'Subscription Expired',
                'message' => 'Subscription for customer Mike Johnson (Plan: Premium 10Mbps) has expired. Please renew to continue service.',
                'type' => 'subscription',
                'is_read' => false,
                'created_at' => now()->subDays(1),
            ]);

            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'Subscription Renewal Reminder',
                'message' => '5 subscriptions are expiring in the next 3 days. Review and send renewal reminders.',
                'type' => 'warning',
                'is_read' => true,
                'created_at' => now()->subDays(2),
            ]);

            // System notifications
            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'System Update',
                'message' => 'All systems are running normally. Last backup completed successfully at ' . now()->subHours(12)->format('H:i'),
                'type' => 'system',
                'is_read' => true,
                'created_at' => now()->subHours(12),
            ]);

            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'Invoice Generated',
                'message' => '15 new invoices have been automatically generated for upcoming subscription renewals.',
                'type' => 'info',
                'is_read' => false,
                'created_at' => now()->subHours(1),
            ]);

            // Hotspot notifications
            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'Hotspot Device Limit Reached',
                'message' => 'Customer Sarah Williams has reached the device limit (3 devices) on their hotspot connection.',
                'type' => 'hotspot',
                'is_read' => false,
                'created_at' => now()->subMinutes(30),
            ]);

            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'Hotspot Session Disconnected',
                'message' => 'Active hotspot session disconnected for customer Robert Brown due to payment overdue.',
                'type' => 'warning',
                'is_read' => true,
                'created_at' => now()->subDays(1),
            ]);

            // Success notifications
            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'Customer Activated Successfully',
                'message' => 'Customer David Lee has been successfully activated and connected to router Router-01.',
                'type' => 'success',
                'is_read' => false,
                'created_at' => now()->subHours(3),
            ]);

            Notification::create([
                'isp_id' => $isp->id,
                'title' => 'Bulk Invoice Generation Complete',
                'message' => 'Successfully generated 25 invoices for monthly subscriptions. Total amount: KES 125,000.00',
                'type' => 'success',
                'is_read' => true,
                'created_at' => now()->subDays(3),
            ]);
        }

        $this->command->info('Sample notifications created for all ISPs.');
    }
}





