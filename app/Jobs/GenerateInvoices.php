<?php

namespace App\Jobs;

use App\Models\ISP;
use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateInvoices implements ShouldQueue
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
            $this->generateInvoicesForIsp($isp);
        }
    }

    protected function generateInvoicesForIsp(ISP $isp): void
    {
        // Get subscriptions that have expired and need invoicing
        // This includes:
        // 1. Active subscriptions that expired today or in the past
        // 2. Expired subscriptions that don't have a pending invoice yet
        $subscriptions = Subscription::whereHas('customer', function ($query) use ($isp) {
            $query->where('isp_id', $isp->id);
        })
        ->where(function ($query) {
            // Active subscriptions that have expired
            $query->where(function ($q) {
                $q->where('status', 'active')
                  ->where('end_date', '<=', now());
            })
            // Or expired subscriptions without pending invoices
            ->orWhere(function ($q) {
                $q->where('status', 'expired')
                  ->whereDoesntHave('invoices', function ($invoiceQuery) {
                      $invoiceQuery->whereIn('status', ['pending', 'overdue']);
                  });
            });
        })
        ->whereDoesntHave('invoices', function ($query) {
            // Don't create duplicate invoices for the same subscription
            $query->whereIn('status', ['pending', 'overdue']);
        })
        ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $this->createInvoice($subscription);
            } catch (\Exception $e) {
                Log::error("Failed to generate invoice for subscription {$subscription->id}: " . $e->getMessage());
            }
        }
    }

    protected function createInvoice(Subscription $subscription): void
    {
        $plan = $subscription->plan;
        $customer = $subscription->customer;
        $isp = $customer->isp;

        $amount = $plan->price;
        $tax = 0; // Calculate tax if needed
        $totalAmount = $amount + $tax;

        // If subscription has expired, set due date to today
        // Otherwise use subscription end date
        $dueDate = $subscription->end_date < now() 
            ? now() 
            : $subscription->end_date;

        Invoice::create([
            'isp_id' => $isp->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'tax' => $tax,
            'total_amount' => $totalAmount,
            'issue_date' => now(),
            'due_date' => $dueDate,
            'status' => 'pending',
        ]);

        Log::info("Generated invoice for subscription {$subscription->id} (Customer: {$customer->id}, Amount: {$totalAmount})");
    }
}
