<?php

namespace Webkul\Chatter\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Support\Mail\Concerns\BuildsTransactionalEnvelope;

class FollowerMail extends Mailable implements ShouldQueue
{
    use BuildsTransactionalEnvelope;
    use Queueable, SerializesModels;

    public function __construct(
        public string $viewTemplate,
        public array $payload,
    ) {}

    public function envelope(): Envelope
    {
        return $this->transactionalEnvelope($this->payload);
    }

    public function content(): Content
    {
        return new Content(view: $this->viewTemplate);
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
