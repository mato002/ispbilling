<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\UserConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckUnpaidInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Checking for unpaid invoices');

        // Get customers with unpaid invoices overdue by 7+ days
        $unpaidCustomers = Customer::where('status', 'active')
            ->whereHas('invoices', function ($query) {
                $query->where('status', 'unpaid')
                    ->where('due_date', '<', now()->subDays(7))
                    ->whereDoesntHave('payments', function ($paymentQuery) {
                        $paymentQuery->where('status', 'completed');
                    });
            })
            ->with(['router', 'hotspotServer'])
            ->get();

        foreach ($unpaidCustomers as $customer) {
            try {
                if (!$customer->router || !$customer->is_active) {
                    continue;
                }

                $connectionService = new UserConnectionService($customer->router);
                
                // Suspend user
                $connectionService->suspendUser($customer);
                
                // Update customer status
                $customer->update([
                    'status' => 'suspended',
                    'connection_status' => 'suspended',
                    'suspended_at' => now(),
                ]);

                Log::info("Suspended customer for unpaid invoices: {$customer->username}");

            } catch (\Exception $e) {
                Log::error("Failed to suspend customer {$customer->username} for unpaid invoices: " . $e->getMessage());
            }
        }

        Log::info("Unpaid invoices check completed. Processed {$unpaidCustomers->count()} customers.");
    }
}
