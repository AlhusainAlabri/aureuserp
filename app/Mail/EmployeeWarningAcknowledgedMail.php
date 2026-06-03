<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Employee\Models\EmployeeWarning;

class EmployeeWarningAcknowledgedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EmployeeWarning $warning,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('hr-extensions::warnings.mail.acknowledged_subject', [
                'employee' => $this->warning->employee?->name ?? '',
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.hr.warning-acknowledged',
            with: [
                'warning'  => $this->warning,
                'employee' => $this->warning->employee,
            ],
        );
    }
}
