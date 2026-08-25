<?php

namespace App\Mail\Operations;

use App\Enums\Operations\BackupType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemBackupFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public BackupType $type,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('operations.alerts.backup_failed_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.operations.backup-failed',
            with: [
                'type'   => $this->type,
                'reason' => $this->reason,
            ],
        );
    }
}
