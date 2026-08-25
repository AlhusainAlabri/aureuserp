<?php

namespace App\Mail\Operations;

use App\Enums\Operations\HealthStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemHealthAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $component,
        public HealthStatus $status,
        public array $context = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('operations.alerts.health_subject', [
                'component' => $this->context['label'] ?? $this->component,
                'status'    => $this->status->getLabel(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.operations.health-alert',
            with: [
                'component' => $this->component,
                'status'    => $this->status,
                'context'   => $this->context,
            ],
        );
    }
}
