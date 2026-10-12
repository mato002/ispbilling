<?php

namespace App\Services;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\ISP;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public const DROPDOWN_LIMIT = 5;

    /**
     * Lightweight payload for header dropdown + polling endpoint.
     *
     * @return array{notifications: \Illuminate\Support\Collection<int, Notification>, unread_count: int}
     */
    public function getDropdownSnapshot(int $ispId, int $limit = self::DROPDOWN_LIMIT, bool $criticalOnly = false): array
    {
        $query = Notification::withoutGlobalScope('isp_tenant')
            ->where('isp_id', $ispId);
        if ($criticalOnly) $query->whereIn('type', ['error', 'critical', 'warning']);

        $notifications = (clone $query)
            ->select(['id', 'isp_id', 'title', 'message', 'type', 'is_read', 'created_at'])
            ->latest('id')
            ->limit($limit)
            ->get();

        $unreadCount = (clone $query)
            ->unread()
            ->count();

        return [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForDropdown(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'is_read' => (bool) $notification->is_read,
            'time_ago' => $notification->created_at?->diffForHumans(),
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }
    /**
     * Create a new notification
     */
    public function createNotification($ispId, $title, $message, $type = 'info'): Notification
    {
        return Notification::create([
            'isp_id' => $ispId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);
    }

    /**
     * Payment received notification
     */
    public function paymentReceived(Payment $payment): Notification
    {
        $payment->loadMissing(['isp', 'customer']);

        $title = 'Payment Received';
        $customerName = $this->customerDisplayName($payment->customer);
        $message = "Payment of {$payment->isp->currency} ".number_format($payment->amount, 2)
            ." received from {$customerName}. Reference: {$payment->payment_reference}";

        return $this->createAndBroadcast($payment->isp_id, $title, $message, 'payment');
    }

    /**
     * Payment reminder notification
     */
    public function paymentReminder($subscription): Notification
    {
        $title = 'Payment Reminder';
        $daysUntilExpiry = Carbon::now()->diffInDays($subscription->end_date);
        
        if ($daysUntilExpiry <= 0) {
            $message = "Subscription for {$subscription->customer->name} has EXPIRED. " .
                      "Plan: {$subscription->plan->name}. Immediate payment required.";
            $type = 'error';
        } elseif ($daysUntilExpiry <= 3) {
            $message = "Subscription for {$subscription->customer->name} expires in {$daysUntilExpiry} days. " .
                      "Plan: {$subscription->plan->name}. Please renew soon.";
            $type = 'warning';
        } else {
            $message = "Subscription for {$subscription->customer->name} expires in {$daysUntilExpiry} days. " .
                      "Plan: {$subscription->plan->name}.";
            $type = 'info';
        }
        
        return $this->createNotification($subscription->isp_id, $title, $message, $type);
    }

    /**
     * New customer registration notification
     */
    public function newCustomer(Customer $customer): Notification
    {
        $title = 'New Customer Registration';
        $customerName = $this->customerDisplayName($customer);
        $message = "New customer {$customerName} has registered. "
            ."Email: ".($customer->email ?: '—').", Phone: {$customer->phone_number}";

        return $this->createAndBroadcast($customer->isp_id, $title, $message, 'success');
    }

    /**
     * Customer suspended on MikroTik due to non-payment.
     */
    public function userSuspended(Customer $customer, ?Invoice $invoice = null): Notification
    {
        $title = 'Customer Suspended';
        $message = "Customer {$this->customerDisplayName($customer)} ({$customer->username}) was deactivated on MikroTik due to non-payment.";

        if ($invoice) {
            $message .= " Invoice {$invoice->invoice_number} is overdue.";
        }

        return $this->createAndBroadcast($customer->isp_id, $title, $message, 'error');
    }

    /**
     * Subscription activated notification
     */
    public function subscriptionActivated($subscription): Notification
    {
        $title = 'Subscription Activated';
        $message = "Subscription activated for {$subscription->customer->name}. " .
                  "Plan: {$subscription->plan->name}. Valid until: {$subscription->end_date->format('M d, Y')}";
        
        return $this->createNotification($subscription->isp_id, $title, $message, 'success');
    }

    /**
     * Hotspot session notification
     */
    public function hotspotSession($customer, $action, $duration = null): Notification
    {
        $title = 'Hotspot Session ' . ucfirst($action);
        
        switch ($action) {
            case 'started':
                $message = "Hotspot session started for {$customer->name}. " .
                          "IP: {$customer->last_login_ip}";
                break;
            case 'ended':
                $message = "Hotspot session ended for {$customer->name}. " .
                          "Duration: {$duration} minutes";
                break;
            case 'timeout':
                $message = "Hotspot session timed out for {$customer->name}. " .
                          "Duration: {$duration} minutes";
                break;
            default:
                $message = "Hotspot activity for {$customer->name}.";
        }
        
        return $this->createNotification($customer->isp_id, $title, $message, 'hotspot');
    }

    /**
     * System maintenance notification
     */
    public function systemMaintenance($ispId, $message): Notification
    {
        $title = 'System Maintenance';
        return $this->createNotification($ispId, $title, $message, 'system');
    }

    /**
     * Payment recorded but RouterOS enable failed after all automatic retries.
     */
    public function provisioningRetriesExhausted(Customer $customer, ?int $paymentId = null): Notification
    {
        $title = 'Router provisioning failed';
        $message = "Customer {$customer->full_name} ({$customer->username}): payment was recorded but MikroTik could not enable the user after automatic retries.";
        if ($paymentId) {
            $message .= " Payment #{$paymentId}.";
        }
        $message .= ' Check router connectivity and the user record on RouterOS; manual enable may be required.';

        return $this->createAndBroadcast($customer->isp_id, $title, $message, 'error');
    }

    /**
     * Fleet router migration batch finished (completed, failed, or rolled back).
     */
    public function routerMigrationFinished(\App\Models\RouterMigrationBatch $batch): Notification
    {
        $from = $batch->oldRouter?->name ?? ('#'.$batch->old_router_id);
        $to = $batch->newRouter?->name ?? ('#'.$batch->new_router_id);

        $title = match ($batch->status) {
            \App\Models\RouterMigrationBatch::STATUS_FAILED => 'Router migration failed',
            \App\Models\RouterMigrationBatch::STATUS_ROLLED_BACK => 'Router migration rolled back',
            default => 'Router migration completed',
        };

        $message = "Batch #{$batch->id}: {$from} → {$to}. "
            ."Success {$batch->success_count}/{$batch->total_customers}"
            .($batch->failed_count ? ", failed {$batch->failed_count}" : '')
            .'.';

        if ($batch->status === \App\Models\RouterMigrationBatch::STATUS_ROLLED_BACK) {
            $message = "Batch #{$batch->id} rolled back to {$from}. Previously moved {$batch->success_count} subscriber(s).";
        }

        $type = match ($batch->status) {
            \App\Models\RouterMigrationBatch::STATUS_FAILED => 'error',
            \App\Models\RouterMigrationBatch::STATUS_ROLLED_BACK => 'warning',
            default => ($batch->failed_count > 0 ? 'warning' : 'success'),
        };

        return $this->createAndBroadcast($batch->isp_id, $title, $message, $type);
    }

    /**
     * Customer support ticket created / updated / replied.
     */
    public function supportTicketEvent(\App\Models\Ticket $ticket, string $event, ?string $detail = null): Notification
    {
        $kindLabel = $ticket->isPlatformTicket() ? 'Platform helpdesk' : 'Support ticket';
        $title = match ($event) {
            'created' => "{$kindLabel} opened #{$ticket->id}",
            'replied' => "{$kindLabel} reply #{$ticket->id}",
            'assigned' => "{$kindLabel} assigned #{$ticket->id}",
            'status' => "{$kindLabel} status #{$ticket->id}",
            default => "{$kindLabel} #{$ticket->id}",
        };

        $message = $ticket->subject;
        if ($detail) {
            $message .= ' — '.$detail;
        }

        $type = match ($event) {
            'created' => 'info',
            'replied' => 'info',
            'assigned' => 'warning',
            'status' => ($ticket->status === 'closed' || $ticket->status === 'resolved') ? 'success' : 'info',
            default => 'info',
        };

        return $this->createAndBroadcast($ticket->isp_id, $title, $message, $type);
    }

    /**
     * Router status notification
     */
    public function routerStatus($router, $status): Notification
    {
        $title = 'Router Status Change';
        $message = "Router '{$router->name}' is now {$status}. " .
                  "IP: {$router->ip_address}";
        
        $type = $status === 'online' ? 'success' : 'error';
        return $this->createNotification($router->isp_id, $title, $message, $type);
    }

    /**
     * Low balance warning
     */
    public function lowBalanceWarning($customer, $balance): Notification
    {
        $title = 'Low Balance Warning';
        $message = "Customer {$customer->name} has low balance: {$customer->isp->currency} " . 
                  number_format($balance, 2) . ". Email: {$customer->email}";
        
        return $this->createNotification($customer->isp_id, $title, $message, 'warning');
    }

    /**
     * Data usage warning
     */
    public function dataUsageWarning($subscription, $usagePercentage): Notification
    {
        $title = 'Data Usage Warning';
        $message = "Customer {$subscription->customer->name} has used {$usagePercentage}% " .
                  "of their data quota. Plan: {$subscription->plan->name}";
        
        $type = $usagePercentage >= 90 ? 'error' : 'warning';
        return $this->createNotification($subscription->isp_id, $title, $message, $type);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount($ispId): int
    {
        return Notification::withoutGlobalScope('isp_tenant')
            ->where('isp_id', $ispId)
            ->unread()
            ->count();
    }

    /**
     * Get recent notifications
     */
    public function getRecentNotifications($ispId, $limit = self::DROPDOWN_LIMIT)
    {
        return Notification::withoutGlobalScope('isp_tenant')
            ->where('isp_id', $ispId)
            ->select(['id', 'isp_id', 'title', 'message', 'type', 'is_read', 'created_at'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Send automatic payment reminders
     */
    public function sendPaymentReminders()
    {
        $expiringSubscriptions = Subscription::where('end_date', '<=', now()->addDays(7))
            ->where('end_date', '>', now())
            ->where('status', 'active')
            ->get();

        foreach ($expiringSubscriptions as $subscription) {
            $this->paymentReminder($subscription);
        }

        Log::info('Payment reminders sent', [
            'count' => $expiringSubscriptions->count(),
            'date' => now()->toDateString()
        ]);
    }

    /**
     * Cleanup old notifications (older than 90 days)
     */
    public function cleanupOldNotifications()
    {
        $deleted = Notification::where('created_at', '<', now()->subDays(90))->delete();
        
        Log::info('Old notifications cleaned up', [
            'count' => $deleted,
            'date' => now()->toDateString()
        ]);
        
        return $deleted;
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats($ispId)
    {
        $q = fn () => Notification::withoutGlobalScope('isp_tenant')->where('isp_id', $ispId);

        $total = $q()->count();
        $unread = $q()->unread()->count();

        $byType = $q()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $total - $unread,
            'by_type' => $byType,
        ];
    }

    private function createAndBroadcast(int $ispId, string $title, string $message, string $type = 'info'): Notification
    {
        $notification = $this->createNotification($ispId, $title, $message, $type);

        try {
            broadcast(new NewNotification($notification));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast in-app notification', [
                'notification_id' => $notification->id,
                'isp_id' => $ispId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }

        return $notification;
    }

    private function customerDisplayName(?Customer $customer): string
    {
        if (! $customer) {
            return 'Unknown customer';
        }

        return trim((string) ($customer->full_name ?: $customer->username ?: 'Unknown customer'));
    }
}
