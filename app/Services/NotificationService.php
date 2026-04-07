<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\ISP;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
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
    public function paymentReceived($payment): Notification
    {
        $title = 'Payment Received';
        $message = "Payment of {$payment->isp->currency} " . number_format($payment->amount, 2) . 
                   " received from {$payment->customer->name}. Reference: {$payment->payment_reference}";
        
        return $this->createNotification($payment->isp_id, $title, $message, 'payment');
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
    public function newCustomer($customer): Notification
    {
        $title = 'New Customer Registration';
        $message = "New customer {$customer->name} has registered. " .
                  "Email: {$customer->email}, Phone: {$customer->phone_number}";
        
        return $this->createNotification($customer->isp_id, $title, $message, 'success');
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
        return Notification::forIsp($ispId)->unread()->count();
    }

    /**
     * Get recent notifications
     */
    public function getRecentNotifications($ispId, $limit = 10)
    {
        return Notification::forIsp($ispId)
            ->latest()
            ->take($limit)
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
        $total = Notification::forIsp($ispId)->count();
        $unread = Notification::forIsp($ispId)->unread()->count();
        
        $byType = Notification::forIsp($ispId)
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
}
