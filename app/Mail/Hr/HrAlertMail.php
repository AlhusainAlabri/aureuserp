<?php

namespace App\Mail\Hr;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Security\Models\User;

class HrAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly string $subjectLine,
        public readonly string $body,
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionLabel = null,
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
            markdown: 'mail.hr.alert',
            with: [
                'recipient'   => $this->recipient,
                'body'        => $this->body,
                'actionUrl'   => $this->actionUrl,
                'actionLabel' => $this->actionLabel ?? __('notifications.preferences.actions.save'),
            ],
        );
    }
}
