<?php

namespace Database\Seeders;

use App\Models\ISP;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first ISP (TechConnect ISP)
        $isp = ISP::where('email', 'admin@techconnect.co.ke')->first();
        
        if (!$isp) {
            $this->command->warn('ISP not found. Please run UserSeeder first.');
            return;
        }

        // Create a router (optional, can be null for testing)
        $router = Router::firstOrCreate(
            ['ip_address' => '192.168.1.1', 'isp_id' => $isp->id],
            [
                'name' => 'Main Router',
                'username' => 'admin',
                'password' => 'password',
                'port' => 8728,
                'use_ssl' => false,
                'location' => 'Nairobi Office',
                'is_active' => true,
            ]
        );

        // Create Plans
        $plans = [
            Plan::firstOrCreate(
                ['name' => 'Basic 5Mbps', 'isp_id' => $isp->id],
                [
                    'description' => 'Basic home internet plan',
                    'access_type' => 'pppoe',
                    'price' => 2500.00,
                    'duration_days' => 30,
                    'speed_profile' => '5mbps-profile',
                    'download_speed_kbps' => 5120,
                    'upload_speed_kbps' => 2560,
                    'billing_type' => 'unlimited',
                    'is_active' => true,
                ]
            ),
            Plan::firstOrCreate(
                ['name' => 'Premium 10Mbps', 'isp_id' => $isp->id],
                [
                    'description' => 'Premium high-speed plan',
                    'access_type' => 'pppoe',
                    'price' => 5000.00,
                    'duration_days' => 30,
                    'speed_profile' => '10mbps-profile',
                    'download_speed_kbps' => 10240,
                    'upload_speed_kbps' => 5120,
                    'billing_type' => 'unlimited',
                    'is_active' => true,
                ]
            ),
            Plan::firstOrCreate(
                ['name' => 'Business 20Mbps', 'isp_id' => $isp->id],
                [
                    'description' => 'Business plan with priority support',
                    'access_type' => 'pppoe',
                    'price' => 10000.00,
                    'duration_days' => 30,
                    'speed_profile' => '20mbps-profile',
                    'download_speed_kbps' => 20480,
                    'upload_speed_kbps' => 10240,
                    'billing_type' => 'unlimited',
                    'is_active' => true,
                ]
            ),
            Plan::firstOrCreate(
                ['name' => 'Hotspot Daily', 'isp_id' => $isp->id],
                [
                    'description' => 'Hotspot daily access plan',
                    'access_type' => 'hotspot',
                    'price' => 100.00,
                    'duration_days' => 1,
                    'speed_profile' => 'hotspot-profile',
                    'download_speed_kbps' => 2048,
                    'upload_speed_kbps' => 1024,
                    'billing_type' => 'data',
                    'data_limit_mb' => 5000,
                    'is_active' => true,
                ]
            ),
        ];

        // Customer 1: Active customer with paid invoice
        $customer1 = Customer::firstOrCreate(
            ['phone_number' => '254712345678', 'isp_id' => $isp->id],
            [
                'router_id' => $router->id,
                'full_name' => 'John Mwangi',
                'email' => 'john.mwangi@example.com',
                'location' => 'Westlands, Nairobi',
                'connection_type' => 'pppoe',
                'username' => 'johnmwangi',
                'password' => bcrypt('password123'), // Hashed for PPPoE
                'status' => 'active',
                'installation_date' => now()->subMonths(3),
                'notes' => 'Long-term customer, always pays on time',
            ]
        );
        
        // Ensure password and username are set (even if customer already exists)
        $customer1->update([
            'username' => 'johnmwangi',
            'password' => bcrypt('password123'),
        ]);

        $subscription1 = Subscription::firstOrCreate(
            ['customer_id' => $customer1->id, 'plan_id' => $plans[1]->id],
            [
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(20),
                'renewal_date' => now()->addDays(20),
                'status' => 'active',
                'auto_renew' => true,
            ]
        );

        $invoice1 = Invoice::firstOrCreate(
            ['invoice_number' => 'TEC-2024-000001', 'isp_id' => $isp->id],
            [
                'customer_id' => $customer1->id,
                'subscription_id' => $subscription1->id,
                'amount' => 5000.00,
                'tax' => 0.00,
                'total_amount' => 5000.00,
                'issue_date' => now()->subDays(5),
                'due_date' => now()->addDays(25),
                'paid_date' => now()->subDays(2),
                'status' => 'paid',
            ]
        );

        Payment::firstOrCreate(
            ['payment_reference' => 'PAY-TEST001', 'isp_id' => $isp->id],
            [
                'customer_id' => $customer1->id,
                'invoice_id' => $invoice1->id,
                'payment_method' => 'mpesa',
                'amount' => 5000.00,
                'transaction_id' => 'MPE123456789',
                'phone_number' => '254712345678',
                'status' => 'completed',
                'processed_at' => now()->subDays(2),
            ]
        );

        // Customer 2: Suspended customer with overdue invoice
        $customer2 = Customer::firstOrCreate(
            ['phone_number' => '254723456789', 'isp_id' => $isp->id],
            [
                'router_id' => $router->id,
                'full_name' => 'Mary Wanjiku',
                'email' => 'mary.wanjiku@example.com',
                'location' => 'Kasarani, Nairobi',
                'connection_type' => 'pppoe',
                'username' => 'marywanjiku',
                'password' => bcrypt('password123'), // Hashed for PPPoE
                'status' => 'suspended',
                'installation_date' => now()->subMonths(2),
                'notes' => 'Payment issues, suspended due to non-payment',
            ]
        );
        
        // Ensure password and username are set (even if customer already exists)
        $customer2->update([
            'username' => 'marywanjiku',
            'password' => bcrypt('password123'),
        ]);

        $subscription2 = Subscription::firstOrCreate(
            ['customer_id' => $customer2->id, 'plan_id' => $plans[0]->id],
            [
                'start_date' => now()->subDays(45),
                'end_date' => now()->subDays(15),
                'renewal_date' => now()->subDays(15),
                'status' => 'expired',
                'auto_renew' => false,
            ]
        );

        $invoice2 = Invoice::firstOrCreate(
            ['invoice_number' => 'TEC-2024-000002', 'isp_id' => $isp->id],
            [
                'customer_id' => $customer2->id,
                'subscription_id' => $subscription2->id,
                'amount' => 2500.00,
                'tax' => 0.00,
                'total_amount' => 2500.00,
                'issue_date' => now()->subDays(30),
                'due_date' => now()->subDays(15),
                'status' => 'overdue',
                'penalty' => 125.00,
            ]
        );

        // Customer 3: Active customer with pending invoice
        $customer3 = Customer::firstOrCreate(
            ['phone_number' => '254734567890', 'isp_id' => $isp->id],
            [
                'router_id' => $router->id,
                'full_name' => 'Peter Ochieng',
                'email' => 'peter.ochieng@example.com',
                'location' => 'Kilimani, Nairobi',
                'connection_type' => 'pppoe',
                'username' => 'peterochieng',
                'password' => bcrypt('password123'), // Hashed for PPPoE
                'status' => 'active',
                'installation_date' => now()->subMonths(1),
                'notes' => 'New customer, first invoice pending',
            ]
        );
        
        // Ensure password and username are set (even if customer already exists)
        $customer3->update([
            'username' => 'peterochieng',
            'password' => bcrypt('password123'),
        ]);

        $subscription3 = Subscription::firstOrCreate(
            ['customer_id' => $customer3->id, 'plan_id' => $plans[2]->id],
            [
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
                'renewal_date' => now()->addDays(25),
                'status' => 'active',
                'auto_renew' => true,
            ]
        );

        $invoice3 = Invoice::firstOrCreate(
            ['invoice_number' => 'TEC-2024-000003', 'isp_id' => $isp->id],
            [
                'customer_id' => $customer3->id,
                'subscription_id' => $subscription3->id,
                'amount' => 10000.00,
                'tax' => 0.00,
                'total_amount' => 10000.00,
                'issue_date' => now()->subDays(3),
                'due_date' => now()->addDays(27),
                'status' => 'pending',
            ]
        );

        Payment::firstOrCreate(
            ['payment_reference' => 'PAY-TEST003', 'isp_id' => $isp->id],
            [
                'customer_id' => $customer3->id,
                'invoice_id' => $invoice3->id,
                'payment_method' => 'mpesa',
                'amount' => 10000.00,
                'phone_number' => '254734567890',
                'status' => 'pending',
            ]
        );

        // Customer 4: Inactive customer (new, not yet activated) - HOTSPOT
        $customer4 = Customer::firstOrCreate(
            ['phone_number' => '254745678901', 'isp_id' => $isp->id],
            [
                'router_id' => $router->id,
                'full_name' => 'Sarah Kamau',
                'email' => 'sarah.kamau@example.com',
                'location' => 'Parklands, Nairobi',
                'connection_type' => 'hotspot',
                'username' => 'sarahkamau', // Username for hotspot login
                'password' => bcrypt('password123'), // Hashed password (same as PPPoE for security)
                'status' => 'inactive',
                'installation_date' => now()->subDays(2),
                'notes' => 'New installation, awaiting first payment - HOTSPOT customer',
            ]
        );
        
        // Ensure password and username are set (even if customer already exists)
        $customer4->update([
            'username' => 'sarahkamau',
            'password' => bcrypt('password123'),
            'router_id' => $router->id,
            'connection_type' => 'hotspot',
        ]);

        $subscription4 = Subscription::firstOrCreate(
            ['customer_id' => $customer4->id, 'plan_id' => $plans[3]->id],
            [
                'start_date' => now()->subDays(2),
                'end_date' => now()->subDays(1),
                'renewal_date' => now()->subDays(1),
                'status' => 'expired',
                'auto_renew' => false,
            ]
        );

        // Customer 5: Active customer with failed payment
        $customer5 = Customer::firstOrCreate(
            ['phone_number' => '254756789012', 'isp_id' => $isp->id],
            [
                'router_id' => $router->id,
                'full_name' => 'David Kipchoge',
                'email' => 'david.kipchoge@example.com',
                'location' => 'Runda, Nairobi',
                'connection_type' => 'pppoe',
                'username' => 'davidkipchoge',
                'password' => bcrypt('password123'), // Hashed for PPPoE
                'status' => 'active',
                'installation_date' => now()->subMonths(4),
                'notes' => 'Customer with recent payment failure',
            ]
        );
        
        // Ensure password and username are set (even if customer already exists)
        $customer5->update([
            'username' => 'davidkipchoge',
            'password' => bcrypt('password123'),
        ]);

        $subscription5 = Subscription::firstOrCreate(
            ['customer_id' => $customer5->id, 'plan_id' => $plans[1]->id],
            [
                'start_date' => now()->subDays(20),
                'end_date' => now()->addDays(10),
                'renewal_date' => now()->addDays(10),
                'status' => 'active',
                'auto_renew' => true,
            ]
        );

        $invoice5 = Invoice::firstOrCreate(
            ['invoice_number' => 'TEC-2024-000005', 'isp_id' => $isp->id],
            [
                'customer_id' => $customer5->id,
                'subscription_id' => $subscription5->id,
                'amount' => 5000.00,
                'tax' => 0.00,
                'total_amount' => 5000.00,
                'issue_date' => now()->subDays(7),
                'due_date' => now()->addDays(23),
                'status' => 'pending',
            ]
        );

        Payment::firstOrCreate(
            ['payment_reference' => 'PAY-TEST005', 'isp_id' => $isp->id],
            [
                'customer_id' => $customer5->id,
                'invoice_id' => $invoice5->id,
                'payment_method' => 'mpesa',
                'amount' => 5000.00,
                'phone_number' => '254756789012',
                'status' => 'failed',
            ]
        );

        // Add an active hotspot customer for testing
        $hotspotCustomer = Customer::firstOrCreate(
            ['phone_number' => '254767890123', 'isp_id' => $isp->id],
            [
                'router_id' => $router->id,
                'full_name' => 'Test Hotspot User',
                'email' => 'hotspot.test@example.com',
                'location' => 'Test Location',
                'connection_type' => 'hotspot',
                'username' => 'hotspottest', // Username for hotspot login
                'password' => bcrypt('test123'), // Hashed password (same as PPPoE for security)
                'status' => 'active',
                'installation_date' => now()->subDays(1),
                'notes' => 'Active hotspot customer for testing login functionality',
            ]
        );
        
        // Ensure password and username are set (even if customer already exists)
        $hotspotCustomer->update([
            'username' => 'hotspottest',
            'password' => bcrypt('test123'),
            'router_id' => $router->id,
            'connection_type' => 'hotspot',
            'status' => 'active',
        ]);

        $hotspotSubscription = Subscription::firstOrCreate(
            ['customer_id' => $hotspotCustomer->id, 'plan_id' => $plans[3]->id],
            [
                'start_date' => now()->subDays(1),
                'end_date' => now()->addDays(30),
                'renewal_date' => now()->addDays(30),
                'status' => 'active',
                'auto_renew' => true,
            ]
        );

        $this->command->info('Created 6 customers with subscriptions, invoices, and payments for testing!');
        $this->command->info('Customer 1: Active with paid invoice (PPPoE) - Username: johnmwangi');
        $this->command->info('Customer 2: Suspended with overdue invoice (PPPoE) - Username: marywanjiku');
        $this->command->info('Customer 3: Active with pending invoice (PPPoE) - Username: peterochieng');
        $this->command->info('Customer 4: Inactive (new customer) - HOTSPOT - Username: sarahkamau');
        $this->command->info('Customer 5: Active with failed payment (PPPoE) - Username: davidkipchoge');
        $this->command->info('Customer 6: Active HOTSPOT customer for testing - Username: hotspottest, Password: test123');
        $this->command->info('');
        $this->command->info('HOTSPOT TESTING:');
        $this->command->info('  Visit: http://127.0.0.1:8000/hotspot/login?router_ip=192.168.1.1');
        $this->command->info('  Username: hotspottest');
        $this->command->info('  Password: test123');
    }
}

