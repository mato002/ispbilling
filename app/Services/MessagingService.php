<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\SmsPlatformLedger;
use App\Models\SmsMessageQueue;
use Illuminate\Support\Facades\DB;
use Exception;

class MessagingService
{
    protected $smsCost = 0.60;

    /**
     * Dispatch an outbound notification event through our system hierarchy.
     */
    public function dispatchNotification(Tenant $tenant, array $data)
    {
        // Data requirements: customer_id, phone, email, body, channel (sms/email/both), priority (high/medium/low)

        // 1. Check if email-only (Free Tier, bypass financial deductions)
        if ($data['channel'] === 'email') {
            $this->sendEmailBackend($data['email'], $data['body']);

            return $this->logMessageState($tenant, $data, 'sent', 0.00);
        }

        // 2. Perform Strict Pre-Paid Wallet Check for SMS dependencies
        if ($tenant->sms_balance < $this->smsCost) {
            // Drop communication event due to non-payment state, push alerting system event
            $this->logMessageState($tenant, $data, 'failed', 0.00, 'Insufficient pre-paid SMS balance.');
            throw new Exception("Tenant {$tenant->name} has run out of automated message credits.");
        }

        // 3. Process Financial Ledger Deductions Securely
        DB::beginTransaction();
        try {
            $tenant->decrement('sms_balance', $this->smsCost);

            SmsPlatformLedger::create([
                'tenant_id' => $tenant->id,
                'amount' => -$this->smsCost,
                'type' => 'deduction',
                'sms_count' => 1,
                'description' => 'Automated System dispatch. Priority Tier: '.strtoupper($data['priority']),
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // 4. Queue Router and Gateway Integration Execution based on priority metrics
        if ($data['priority'] === 'high') {
            // Push instantly using standard inline execution paths
            $this->executeGatewayTransmission($data['phone'], $data['body']);
        } else {
            // Push quietly to background dispatch tasks worker arrays
            SmsMessageQueue::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $data['customer_id'],
                'recipient_phone' => $data['phone'],
                'message_body' => $data['body'],
                'channel' => $data['channel'],
                'priority' => $data['priority'],
                'status' => 'pending',
            ]);
        }

        // 5. Fire off complementary email loops if configured as 'both'
        if ($data['channel'] === 'both') {
            $this->sendEmailBackend($data['email'], $data['body']);
        }
    }

    protected function executeGatewayTransmission($phone, $body)
    {
        // This is where you swap between drivers dynamically
        // Active Provider API payload injection (Africa's Talking / Onfon Media SDK routines hook here)
    }

    protected function sendEmailBackend($email, $body) { /* Standard SMTP / Mailgun driver */ }

    protected function logMessageState($tenant, $data, $status, $cost, $error = null) { /* Write to Logs */ }
}
