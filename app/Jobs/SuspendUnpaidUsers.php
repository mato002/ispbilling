<?php

namespace App\Jobs;

use App\Models\ISP;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\MikroTikService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SuspendUnpaidUsers implements ShouldQueue
{
    use Queueable;

    protected $isp;

    /**
     * Create a new job instance.
     */
    public function __construct(ISP $isp = null)
    {
        $this->isp = $isp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $isps = $this->isp ? collect([$this->isp]) : ISP::where('is_active', true)->get();

        foreach ($isps as $isp) {
            $this->suspendUnpaidUsersForIsp($isp);
        }
    }

    protected function suspendUnpaidUsersForIsp(ISP $isp): void
    {
        // Get customers with overdue invoices
        $overdueInvoices = Invoice::where('isp_id', $isp->id)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->with('customer')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $customer = $invoice->customer;

            if ($customer->status === 'active' && $customer->router) {
                try {
                    $mikrotik = new MikroTikService($customer->router);
                    
                    // Disconnect active session
                    $mikrotik->disconnectSession($customer);
                    
                    // Disable user
                    $mikrotik->disableUser($customer);
                    
                    // Update customer status
                    $customer->suspend();
                    
                    // Update invoice status
                    $invoice->update(['status' => 'overdue']);

                    Log::info("Suspended customer {$customer->id} due to unpaid invoice {$invoice->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to suspend customer {$customer->id}: " . $e->getMessage());
                }
            }
        }
    }
}
