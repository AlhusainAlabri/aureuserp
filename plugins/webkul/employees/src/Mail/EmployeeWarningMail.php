<?php

namespace Webkul\Employee\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Models\EmployeeWarning;

class EmployeeWarningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeWarning $warning,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = [];

        $sender = Auth::user();

        if ($sender !== null) {
            $replyTo[] = new Address($sender->email, $sender->name);
        }

        return new Envelope(
            subject: __('employees::filament/resources/employee/relation-manager/warnings.mail.subject', ['subject' => $this->warning->subject]),
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name'),
            ),
            replyTo: $replyTo,
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
