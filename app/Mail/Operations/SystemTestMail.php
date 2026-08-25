<?php

namespace App\Mail\Operations;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemTestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('operations.notifications.test_email_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.operations.test-email',
        );
    }
}
