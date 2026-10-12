<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Router;
use App\Models\Subscription;
use App\Services\MikroTikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ManageUserConnections implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting automatic user control job');

        try {
            // Disconnect expired users
            $this->disconnectExpiredUsers();

            // Suspend unpaid users
            $this->suspendUnpaidUsers();

            // Reconnect users after payment
            $this->reconnectPaidUsers();

            Log::info('Automatic user control job completed successfully');

        } catch (\Exception $e) {
            Log::error('Automatic user control job failed: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect users with expired subscriptions
     */
    private function disconnectExpiredUsers(): void
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where(function ($query) {
                $query->whereNotNull('ends_at')->where('ends_at', '<=', now())
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('ends_at')->whereDate('end_date', '<', today());
                    });
            })
            ->with(['customer.router', 'customer.hotspotServer'])
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            try {
                $customer = $subscription->customer;
                
                if (!$customer->router || !$customer->is_active) {
                    continue;
                }

                $mikrotikService = new MikroTikService($customer->router);
                
                // Remove from MikroTik hotspot users
                $this->removeHotspotUser($mikrotikService, $customer);
                
                // Remove from MikroTik PPPoE users
                $this->removePPPoEUser($mikrotikService, $customer);
                
                // Update subscription status
                $subscription->update([
                    'status' => 'expired',
                    'disconnected_at' => now(),
                ]);

                // Update customer status
                $customer->update([
                    'status' => 'expired',
                    'connection_status' => 'disconnected',
                ]);

                // Log the action
                Log::info("Disconnected expired user: {$customer->username} (Customer ID: {$customer->id})");

            } catch (\Exception $e) {
                Log::error("Failed to disconnect expired user {$subscription->customer->username}: " . $e->getMessage());
            }
        }
    }

    /**
     * Suspend users with unpaid invoices
     */
    private function suspendUnpaidUsers(): void
    {
        // Get customers with overdue unpaid invoices
        $unpaidCustomers = Customer::where('status', 'active')
            ->whereHas('invoices', function ($query) {
                $query->whereIn('status', ['pending', 'overdue'])
                    ->where('due_date', '<', now()->subDays(7)) // 7 days grace period
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

                $mikrotikService = new MikroTikService($customer->router);
                
                // Suspend hotspot user (disable but keep account)
                $this->suspendHotspotUser($mikrotikService, $customer);
                
                // Suspend PPPoE user
                $this->suspendPPPoEUser($mikrotikService, $customer);
                
                // Update customer status
                $customer->update([
                    'status' => 'suspended',
                    'connection_status' => 'suspended',
                    'suspended_at' => now(),
                ]);

                // Log the action
                Log::info("Suspended unpaid user: {$customer->username} (Customer ID: {$customer->id})");

            } catch (\Exception $e) {
                Log::error("Failed to suspend unpaid user {$customer->username}: " . $e->getMessage());
            }
        }
    }

    /**
     * Reconnect users who have made payments
     */
    private function reconnectPaidUsers(): void
    {
        // Get suspended customers with recent payments
        $paidCustomers = Customer::whereIn('status', ['suspended', 'expired'])
            ->whereHas('payments', function ($query) {
                $query->where('status', 'completed')
                    ->where('created_at', '>', now()->subHours(1)); // Recent payments
            })
            ->with(['router', 'hotspotServer', 'subscriptions'])
            ->get();

        foreach ($paidCustomers as $customer) {
            try {
                if (!$customer->router || !$customer->is_active) {
                    continue;
                }

                // Check if customer has active subscription
                $activeSubscription = $customer->subscriptions()
                    ->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>', now())
                    ->first();

                if (!$activeSubscription) {
                    continue;
                }

                $mikrotikService = new MikroTikService($customer->router);
                
                // Reconnect hotspot user
                $this->reconnectHotspotUser($mikrotikService, $customer, $activeSubscription);
                
                // Reconnect PPPoE user
                $this->reconnectPPPoEUser($mikrotikService, $customer, $activeSubscription);
                
                // Update customer status
                $customer->update([
                    'status' => 'active',
                    'connection_status' => 'connected',
                    'reconnected_at' => now(),
                ]);

                // Log the action
                Log::info("Reconnected paid user: {$customer->username} (Customer ID: {$customer->id})");

            } catch (\Exception $e) {
                Log::error("Failed to reconnect paid user {$customer->username}: " . $e->getMessage());
            }
        }
    }

    /**
     * Remove user from MikroTik hotspot
     */
    private function removeHotspotUser(MikroTikService $service, Customer $customer): void
    {
        if (!$customer->username) {
            return;
        }

        $url = $customer->router->getApiUrl();
        
        // Remove from hotspot users
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->withBasicAuth($customer->router->username, $customer->router->password)
            ->post($url, [
                '/ip/hotspot/user/remove' => '',
                '.id' => $customer->username,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to remove hotspot user: ' . $response->body());
        }
    }

    /**
     * Remove user from MikroTik PPPoE
     */
    private function removePPPoEUser(MikroTikService $service, Customer $customer): void
    {
        if (!$customer->username) {
            return;
        }

        $url = $customer->router->getApiUrl();
        
        // Remove from PPP secrets
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->withBasicAuth($customer->router->username, $customer->router->password)
            ->post($url, [
                '/ppp/secret/remove' => '',
                '.id' => $customer->username,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to remove PPPoE user: ' . $response->body());
        }
    }

    /**
     * Suspend hotspot user (disable but keep account)
     */
    private function suspendHotspotUser(MikroTikService $service, Customer $customer): void
    {
        if (!$customer->username) {
            return;
        }

        $url = $customer->router->getApiUrl();
        
        // Disable hotspot user
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->withBasicAuth($customer->router->username, $customer->router->password)
            ->post($url, [
                '/ip/hotspot/user/set' => '',
                '.id' => $customer->username,
                'disabled' => 'true',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to suspend hotspot user: ' . $response->body());
        }
    }

    /**
     * Suspend PPPoE user
     */
    private function suspendPPPoEUser(MikroTikService $service, Customer $customer): void
    {
        if (!$customer->username) {
            return;
        }

        $url = $customer->router->getApiUrl();
        
        // Disable PPP secret
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->withBasicAuth($customer->router->username, $customer->router->password)
            ->post($url, [
                '/ppp/secret/set' => '',
                '.id' => $customer->username,
                'disabled' => 'true',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to suspend PPPoE user: ' . $response->body());
        }
    }

    /**
     * Reconnect hotspot user
     */
    private function reconnectHotspotUser(MikroTikService $service, Customer $customer, Subscription $subscription): void
    {
        if (!$customer->username) {
            return;
        }

        $url = $customer->router->getApiUrl();
        
        // Enable hotspot user
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->withBasicAuth($customer->router->username, $customer->router->password)
            ->post($url, [
                '/ip/hotspot/user/set' => '',
                '.id' => $customer->username,
                'disabled' => 'false',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to reconnect hotspot user: ' . $response->body());
        }
    }

    /**
     * Reconnect PPPoE user
     */
    private function reconnectPPPoEUser(MikroTikService $service, Customer $customer, Subscription $subscription): void
    {
        if (!$customer->username) {
            return;
        }

        $url = $customer->router->getApiUrl();
        
        // Enable PPP secret
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->withBasicAuth($customer->router->username, $customer->router->password)
            ->post($url, [
                '/ppp/secret/set' => '',
                '.id' => $customer->username,
                'disabled' => 'false',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to reconnect PPPoE user: ' . $response->body());
        }
    }
}
