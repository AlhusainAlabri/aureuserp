<?php

namespace App\Mail\Assets;

use App\Mail\Assets\Concerns\BuildsBorrowingViewUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Assets\Models\AssetBorrowing;

class BorrowingRequestApprovedMail extends Mailable implements ShouldQueue
{
    use BuildsBorrowingViewUrl, Queueable, SerializesModels;

    public function __construct(
        public readonly AssetBorrowing $borrowing,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('assets-extensions::mail.approved.subject', [
                'asset' => $this->borrowing->asset?->name ?? '—',
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.assets.borrowing-request-approved',
            with: [
                'borrowing' => $this->borrowing,
                'viewUrl'   => $this->borrowingViewUrl($this->borrowing),
            ],
        );
    }
}
