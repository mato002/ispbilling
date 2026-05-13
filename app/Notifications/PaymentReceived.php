<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\VonageMessage;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    protected $payment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
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
        return (new MailMessage)
            ->subject('Payment Received - ' . $this->payment->payment_reference)
            ->line('Your payment of ' . $this->payment->isp->currency . ' ' . number_format($this->payment->amount, 2) . ' has been received.')
            ->line('Payment Reference: ' . $this->payment->payment_reference)
            ->line('Your internet service has been activated.')
            ->action('View Invoice', url('/invoices/' . $this->payment->invoice_id))
            ->line('Thank you for your payment!');
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     */
    public function toVonage(object $notifiable): VonageMessage
    {
        return (new VonageMessage)
            ->content('Payment of ' . $this->payment->isp->currency . ' ' . number_format($this->payment->amount, 2) . ' received. Ref: ' . $this->payment->payment_reference . '. Service activated.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'payment_reference' => $this->payment->payment_reference,
        ];
    }
}
