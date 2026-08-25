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
use Webkul\Employee\Models\EmployeeSubmission;
use Webkul\Employee\Models\EmployeeSubmissionReply;

class SubmissionReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeSubmission $submission,
        public EmployeeSubmissionReply $reply,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = [];

        $sender = Auth::user();

        if ($sender !== null) {
            $replyTo[] = new Address($sender->email, $sender->name);
        }

        return new Envelope(
            subject: __('employees::mail/submission-reply.subject', [
                'type'   => __('employees::filament/resources/submission.types.'.$this->submission->type),
                'ticket' => $this->submission->ticket_number,
            ]),
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
            view: 'employees::emails.submission-reply',
            with: [
                'submission' => $this->submission,
                'reply'      => $this->reply,
            ],
        );
    }
}
