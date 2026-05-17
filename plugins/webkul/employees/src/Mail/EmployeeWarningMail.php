<?php

namespace Webkul\Employee\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Employee\Models\EmployeeWarning;

class EmployeeWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeWarning $warning
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('employees::filament/resources/employee/relation-manager/warnings.mail.subject', ['subject' => $this->warning->subject]),
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'employees::emails.employee-warning',
            with: [
                'warning'     => $this->warning,
                'employee'    => $this->warning->employee,
                'warningType' => $this->warning->warningType,
            ],
        );
    }
}
