<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\ISP;
use App\Notifications\PaymentReminder;
use App\Support\IspNotificationPreferences;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders implements ShouldQueue
{
    use Queueable;

    public function __construct(protected ?ISP $isp = null) {}

    public function handle(): void
    {
        $isps = $this->isp
            ? collect([$this->isp])
            : ISP::query()->where('is_active', true)->get();

        foreach ($isps as $isp) {
            $this->sendRemindersForIsp($isp);
        }
    }

    protected function sendRemindersForIsp(ISP $isp): void
    {
        $preferences = IspNotificationPreferences::forIsp($isp);

        if (! $preferences->remindersEnabled()) {
            Log::debug('Skipping payment reminders for ISP; invoice_reminders disabled', [
                'isp_id' => $isp->id,
            ]);

            return;
        }

        $dueBefore = $preferences->reminderDueBefore();

        $invoices = Invoice::query()
            ->where('isp_id', $isp->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->where('due_date', '<=', $dueBefore)
            ->with(['customer', 'isp'])
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            try {
                $customer = $invoice->customer;

                if (! $customer || (! $customer->email && ! $customer->phone_number)) {
                    continue;
                }

                $lastReminder = $invoice->updated_at;
                if ($lastReminder && $lastReminder->diffInHours(now()) < 24) {
                    continue;
                }

                $customer->notify(new PaymentReminder($invoice));
                $invoice->touch();
                $sent++;

                Log::info('Payment reminder sent', [
                    'isp_id' => $isp->id,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_id' => $customer->id,
                    'reminder_days' => $preferences->reminderDays,
                    'due_before' => $dueBefore->toDateString(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send payment reminder', [
                    'isp_id' => $isp->id,
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($sent > 0) {
            Log::info('Payment reminder batch completed for ISP', [
                'isp_id' => $isp->id,
                'sent' => $sent,
                'reminder_days' => $preferences->reminderDays,
            ]);
        }
    }
}
