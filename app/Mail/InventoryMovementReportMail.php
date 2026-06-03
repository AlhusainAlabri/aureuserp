<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class InventoryMovementReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('inventory-extensions.mail.movement_report_subject', [
                'from' => $this->from->format('d M Y'),
                'to'   => $this->to->format('d M Y'),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.inventory.movement-report',
            with: [
                'from' => $this->from,
                'to'   => $this->to,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! Storage::disk('private')->exists($this->pdfPath)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('private', $this->pdfPath)
                ->as(basename($this->pdfPath))
                ->withMime('application/pdf'),
        ];
    }
}
