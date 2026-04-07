<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\VonageMessage;

class PaymentReminder extends Notification
{
    use Queueable;

    protected $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        
        // Add SMS if phone number is available
        if ($notifiable->phone_number) {
            $channels[] = 'vonage';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysUntilDue = now()->diffInDays($this->invoice->due_date, false);
        $message = (new MailMessage)
            ->subject('Payment Reminder - Invoice ' . $this->invoice->invoice_number)
            ->line('This is a reminder that you have an outstanding invoice.')
            ->line('Invoice Number: ' . $this->invoice->invoice_number)
            ->line('Amount Due: ' . $this->invoice->isp->currency . ' ' . number_format($this->invoice->total_amount, 2))
            ->line('Due Date: ' . $this->invoice->due_date->format('F d, Y'));

        if ($daysUntilDue < 0) {
            $message->line('⚠️ This invoice is overdue by ' . abs($daysUntilDue) . ' days.');
        } else {
            $message->line('This invoice is due in ' . $daysUntilDue . ' days.');
        }

        return $message
            ->action('Pay Now', url('/payments/initiate?invoice_id=' . $this->invoice->id))
            ->line('Thank you for your prompt payment!');
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     */
    public function toVonage(object $notifiable): VonageMessage
    {
        $daysUntilDue = now()->diffInDays($this->invoice->due_date, false);
        $message = 'Payment reminder: Invoice ' . $this->invoice->invoice_number . ' - ' . 
                   $this->invoice->isp->currency . ' ' . number_format($this->invoice->total_amount, 2);
        
        if ($daysUntilDue < 0) {
            $message .= '. Overdue by ' . abs($daysUntilDue) . ' days.';
        } else {
            $message .= '. Due in ' . $daysUntilDue . ' days.';
        }

        return (new VonageMessage)->content($message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date->toDateString(),
        ];
    }
}
