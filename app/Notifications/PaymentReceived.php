<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Support\CustomerNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    protected Payment $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return array<int, string|class-string>
     */
    public function via(object $notifiable): array
    {
        return CustomerNotificationChannels::for($notifiable)
            ->withMail()
            ->withSms()
            ->resolve();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Received - '.$this->payment->payment_reference)
            ->line('Your payment of '.$this->payment->isp->currency.' '.number_format($this->payment->amount, 2).' has been received.')
            ->line('Payment Reference: '.$this->payment->payment_reference)
            ->line('Your internet service has been activated.')
            ->action('View Invoice', url('/invoices/'.$this->payment->invoice_id))
            ->line('Thank you for your payment!');
    }

    public function toSms(object $notifiable): string
    {
        return 'Payment of '.$this->payment->isp->currency.' '.number_format($this->payment->amount, 2)
            .' received. Ref: '.$this->payment->payment_reference.'. Service activated.';
    }

    /**
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
