<?php

namespace App\Jobs;

use App\Models\ISP;
use App\Models\Invoice;
use App\Models\Customer;
use App\Notifications\PaymentReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders implements ShouldQueue
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
            $this->sendRemindersForIsp($isp);
        }
    }

    protected function sendRemindersForIsp(ISP $isp): void
    {
        // Get pending invoices that are due soon or overdue
        $invoices = Invoice::where('isp_id', $isp->id)
            ->where('status', 'pending')
            ->where(function ($query) {
                // Due in 3 days or overdue
                $query->where('due_date', '<=', now()->addDays(3))
                      ->orWhere('due_date', '<', now());
            })
            ->with('customer')
            ->get();

        foreach ($invoices as $invoice) {
            try {
                $customer = $invoice->customer;
                
                // Only send if customer has contact info
                if ($customer && ($customer->email || $customer->phone_number)) {
                    // Check if we've sent a reminder in the last 24 hours
                    $lastReminder = $invoice->updated_at;
                    if ($lastReminder && $lastReminder->diffInHours(now()) < 24) {
                        continue;
                    }

                    // Send notification
                    $customer->notify(new PaymentReminder($invoice));
                    
                    // Update invoice to track reminder sent
                    $invoice->touch();
                    
                    Log::info("Payment reminder sent for invoice {$invoice->invoice_number} to customer {$customer->id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to send payment reminder for invoice {$invoice->id}: " . $e->getMessage());
            }
        }
    }
}
