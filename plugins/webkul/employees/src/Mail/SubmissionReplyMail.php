<?php

namespace Webkul\Employee\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Employee\Models\EmployeeSubmission;
use Webkul\Employee\Models\EmployeeSubmissionReply;

class SubmissionReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeSubmission $submission,
        public EmployeeSubmissionReply $reply
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('employees::mail/submission-reply.subject', [
                'type'   => __('employees::filament/resources/submission.types.'.$this->submission->type),
                'ticket' => $this->submission->ticket_number,
            ]),
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'employees::emails.submission-reply',
            with: [
                'submission' => $this->submission,
                'reply'      => $this->reply,
            ],
        );
    }
}
