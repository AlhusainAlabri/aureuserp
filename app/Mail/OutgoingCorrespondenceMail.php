<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Correspondence\Models\Correspondence;

class OutgoingCorrespondenceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Correspondence $correspondence) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->correspondence->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'correspondence::mail.outgoing-correspondence',
            with: [
                'correspondence' => $this->correspondence,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return $this->correspondence
            ->attachments()
            ->get()
            ->map(fn ($attachment): Attachment => Attachment::fromStorageDisk('private', $attachment->file_path)
                ->as($attachment->file_name)
                ->withMime($attachment->mime_type))
            ->all();
    }
}
