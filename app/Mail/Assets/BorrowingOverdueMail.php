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
use Webkul\Security\Models\User;

class BorrowingOverdueMail extends Mailable implements ShouldQueue
{
    use BuildsBorrowingViewUrl, Queueable, SerializesModels;

    public function __construct(
        public readonly AssetBorrowing $borrowing,
        public readonly User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('assets-extensions::mail.overdue.subject', [
                'asset' => $this->borrowing->asset?->name ?? '—',
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.assets.borrowing-overdue',
            with: [
                'borrowing' => $this->borrowing,
                'recipient' => $this->recipient,
                'viewUrl'   => $this->borrowingViewUrl($this->borrowing),
            ],
        );
    }
}
