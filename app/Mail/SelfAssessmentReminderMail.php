<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Employee\Models\Employee;

class SelfAssessmentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Employee $employee,
        public readonly int $year,
        public readonly int $month,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('hr-extensions::self_assessment.mail.reminder_subject', [
                'month' => __('hr-extensions::self_assessment.months.'.$this->month),
                'year'  => $this->year,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.hr.self-assessment-reminder',
            with: [
                'employee' => $this->employee,
                'year'     => $this->year,
                'month'    => $this->month,
            ],
        );
    }
}
