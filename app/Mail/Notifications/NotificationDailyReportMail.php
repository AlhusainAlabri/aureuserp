<?php

namespace App\Mail\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationDailyReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $report
     */
    public function __construct(
        public readonly array $report,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->report['healthy']
            ? __('notifications.operations.report.mail.subject_ok')
            : __('notifications.operations.report.mail.subject_alert');

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.notifications.daily-report',
            with: [
                'report' => $this->report,
            ],
        );
    }
}
