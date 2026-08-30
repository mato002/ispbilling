<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMessageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $body,
        public string $ispName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.campaign-message',
        );
    }
}
